#!/var/www/csrs/venv/bin/python
import os, time, re, sys
from datetime import date, datetime
from models import PO, Customer, Price, PO_line, Serie, session, SNAP_pricing

def get_serie_index(pn,snap_series, snap_regex):
    i = 0
    for regex in snap_regex:
        mo = regex.search(pn)
        if mo is not None:
            return i
        i+=1
    return None

def get_unit_price_rev_level(pn, snap_series, snap_regex):
    i=0
    length = 0
    num_cables=1
    prefix=''
    for regex in snap_regex:
        mo = regex.search(pn)
        if mo is not None:
            prefix = mo.group(1)
            num_cables = 1
            if len(mo.groups())==7:                
                num_cables = int(mo.group(3))
                length = int(mo.group(6))
            elif len(mo.groups())==5:
                length = int(mo.group(4))
            break
        i+=1

    if length>0:
        return round(snap_series[i].base + (snap_series[i].per_ft_adder * length),2),snap_series[i].rev_level           
        

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
    series = session.query(SNAP_pricing).filter(SNAP_pricing.id != 'UNKNOWN').all()
    if os.path.isfile(po_file):
        f = open(po_file,'r')
        lines = f.readlines()        
        f.close()        
        poregex = re.compile(r' *Purchase Order Number +(?P<po>(\d{8}))')
        shiptoregex = re.compile(r' *Ship To Name +(?P<ship_to>[\x20-\x7E]+)')

        po_lineregex = re.compile(r' *Purchase Order Line Number +(?P<line>\d+)')
        qtyregex = re.compile(r' *Quantity +(?P<line>[0-9,]+)')
        unit_priceregex = re.compile(r' *Unit Price +(?P<price>[0-9.]+)')        
        itemregex = re.compile(r' *Ortronics Item Number +(?P<pn>\w+)')
        rev_lvlregex = re.compile(r' *Revision Level +(?P<revlvl>\d\d)')
        req_shipregex = re.compile(r' *Requested Ship Date +(?P<req_ship_date>\d\d/\d\d/\d\d\d\d)')
        index = 0
        po = ""
        ship_to = ""
        purchase_order = PO()
        while True:
            mo = poregex.search(lines[index])
            if mo is not None:
                po = mo.group(1)
                purchase_order.po_number = po
                purchase_order.customer_id= customer
                purchase_order.date_received = datetime.now()
                purchase_order.ship_to = 1
                purchase_order.planner = "EDI"
                purchase_order.csr = "CS"
                purchase_order.status = 0
                purchase_order.total = 0
                session.add(purchase_order)
                print("PO# {}".format(po))
                break
            index+=1
        index+=1
        while True:            
            mo = shiptoregex.search(lines[index])
            if mo is not None:
                ship_to = mo.group(1)
                print("Ship to: {}".format(ship_to))
                break
            index+=1
        index+=1
        while index<len(lines):
            po_line = PO_line()
            po_line.po_number = purchase_order.po_number
            po_line.serie_id = 16
            mo = po_lineregex.search(lines[index])
            if mo is not None:
                po_line.ln = mo.group(1)
                print("Line# {}".format(po_line.ln))
                mo = None
                while mo is None:
                    index+=1
                    mo = qtyregex.search(lines[index])
                po_line.req_qty = int(mo.group(1))
                print("Qty: {:,}".format(po_line.req_qty))
                mo = None
                while mo is None:
                    index+=1
                    mo = unit_priceregex.search(lines[index])
                po_line.req_unit_price = float(mo.group(1))
                print("Unit Price: ${:,}".format(po_line.req_unit_price))
                purchase_order.total+=(po_line.req_unit_price * po_line.req_qty)
                mo = None
                while mo is None:
                    index+=1
                    mo = itemregex.search(lines[index])
                po_line.pn = mo.group(1)
                po_line.our_unit_price, po_line.our_rev_level=get_unit_price_rev_level(po_line.pn, snap_series, compiled_regex)
                print("P/N: {}".format(po_line.pn))
                print("Our unit price: ${:,}".format(po_line.our_unit_price))
                print("Our rev. level: {}".format(po_line.our_rev_level))
                mo = None
                while mo is None:
                    index+=1
                    mo = rev_lvlregex.search(lines[index])
                po_line.req_rev_level = mo.group(1)
                print("Rev. Level: {}".format(po_line.req_rev_level))
                mo = None
                while mo is None:
                    index+=1
                    mo = req_shipregex.search(lines[index])
                po_line.req_ship_date = datetime.strptime(mo.group(1),'%m/%d/%Y')
                session.add(po_line)
                session.commit()
                print("Req. Ship Date: {}".format(po_line.req_ship_date))
            else:
                index+=1
        print("Total: ${:,}".format(purchase_order.total))
        session.commit()
                
snap_series = session.query(SNAP_pricing).all()

compiled_regex = []
for serie in snap_series:
    compiled_regex.append(re.compile(serie.regex))
         
if len(sys.argv)==2:
    po_file = sys.argv[1]
else:
    po_file = str(input('Please enter Purchase Order file name: '))

if os.path.isfile(po_file):
    txtfile = convertPdf2Text(po_file)
    process_po(txtfile,'ORT')

