#!/var/www/csrs/venv/bin/python
import os, time, re, sys
from datetime import date, datetime
from models import PO, Customer, Price, PO_line, Serie, session

def convertPdf2Text(full_path):
    os.system('/var/www/csrs/pdf2txt.sh {} {}.txt'.format(full_path, full_path))
    print("File converted: {}.txt".format(full_path))
    return full_path+'.txt'

def verify_item(pn, customer, series):    
    info = [None, None, None]
    for s in series:
        regex = re.compile(s.regex)
        mo = regex.search(pn)
        if mo!=None:
            d = mo.groupdict()
            length = d.get('length')
            color = d.get('color')
            boxqty = d.get('boxqty')
            p = session.query(Price).filter_by(serie_id=s.id).filter_by(length=length).first()
            info[0]=p.price
            info[1]=s.rev_level
            info[2]=s
            return info
    unknown = session.query(Serie).filter_by(customer_id=customer).filter(Serie.pn_format == 'UNKNOWN').first()
    info[2]=unknown
    return info
            
def process_po(po_file,customer):
    series = session.query(Serie).filter_by(customer_id=customer).filter(Serie.pn_format != 'UNKNOWN').all()
    if os.path.isfile(po_file):
        f = open(po_file,'r')
        lines = f.readlines()        
        f.close()
        itemregex = re.compile(r' +([0-9]+) +([A-Z0-9.-]+) +(\d{1,2}) +(\d{1,2}\/\d{2}\/\d{4}) +(\d+) +([0-9.]+)')
        poregex = re.compile(r'PO# +(\d+)$')
        shiptoregex = re.compile(r' +Fax: +([\w ]+)')
        plannerregex = re.compile(r' +Planner:(?P<planner>[\w ]+)')
        totalregex = re.compile(r'Grand Total +(?P<total>[\d,]+\.\d{2})$')
        po = PO()
        
        ship_to = None
        planner = None
        total=0
        l = 0
        for l in range(len(lines)):
            mo = poregex.search(lines[l])
            if mo != None:
                if po!= mo.group(1):
                    po.po_number = mo.group(1)
                    po.customer_id = 'ORT'
                    print ("\nPO# {}\n".format(po.po_number))
                continue
            
            mo = shiptoregex.search(lines[l])
            if mo != None:
                if ship_to!=mo.group(1):
                    ship_to = mo.group(1)
                    po.ship_to = 1
                    print("Ship to: {}\n".format(ship_to))
                continue
                
            mo = plannerregex.search(lines[l])
            if mo != None:
                if po.planner!=mo.group(1):
                    po.planner = mo.group(1)
                    print("Planner: {}\n".format(po.planner))
                continue             

            mo = itemregex.search(lines[l])            
            if mo!= None:
                line = PO_line()
                line.po_number = po.po_number
                line.ln = int(mo.group(1))
                line.pn = mo.group(2)
                line.req_rev_level = str(mo.group(3))
                line.req_ship_date = datetime.strptime(mo.group(4),'%m/%d/%Y')
                line.req_qty = int(mo.group(5))
                line.req_unit_price = float(mo.group(6))
                line.our_ship_date = None
                
                line.our_unit_price, line.our_rev_level, line.serie = verify_item(mo.group(2),'ORT',series)
                line.serie_id = line.serie.id
                po.po_lines.append(line)
                ext_price = (line.req_qty * line.req_unit_price)
                total+=ext_price
                
                print("Ln# {}".format(line.ln))
                print("P/N: {}".format(line.pn))
                if line.our_rev_level is None:
                    po.status =-1                      #Invalid Order, problem must be corrected.
                    print("***SERIE NOT FOUND, Please notify SysAdmin.****")
                else:
                    print("Rev: {}\tOur Revision: {}".format(line.req_rev_level,line.our_rev_level))
                    print("Delivery Date: {}".format(line.req_ship_date.strftime('%m/%d/%Y')))
                      
                print("Qty: {}".format(line.req_qty))
                print("Unit price: ${}\tOur price: ${}".format(line.req_unit_price, line.our_unit_price))
                print("Extended Price: {}".format(ext_price))
                print()

            mo = totalregex.search(lines[l])
            if mo!=None:
                po.total = float(mo.group(1).replace(',',''))
                try:
                    session.add(po)
                    session.commit()
                except:
                    print('Purchase Order already exists!')
                po = PO()
                print("Total: ${}".format(po.total))
                print("Calculated Total: {}".format(total))
                total = 0
                print()

if len(sys.argv)==2:
    po_file = sys.argv[1]
else:
    po_file = str(input('Please enter Purchase Order file name: '))

if os.path.isfile(po_file):
    txtfile = convertPdf2Text(po_file)
    process_po(txtfile,'ORT')

