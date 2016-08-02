from sqlalchemy import create_engine
import sqlalchemy
import pymysql
import datetime
import re
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker
from sqlalchemy import ForeignKey
from sqlalchemy.orm import relationship

engine = create_engine('mysql+pymysql://root:chrome21@localhost/csrdb?unix_socket=/var/run/mysqld/mysqld.sock',echo=False)
Base = declarative_base()

from sqlalchemy import Column, Integer, String, DateTime, Float, Text

class PO_line(Base):
    __tablename__ = 'po_lines'
    id = Column(Integer, primary_key=True)
    po_number = Column(String(10), ForeignKey('pos.po_number'), index=True)
    po = relationship("PO", back_populates="po_lines")
    ln = Column(Integer, default=0)
    pn = Column(String(32))
    serie_id = Column(Integer, ForeignKey('series.id'), index=True)
    serie = relationship("Serie", back_populates="po_lines")
    req_rev_level = Column(String(2))
    req_ship_date = Column(DateTime)
    req_qty = Column(Integer, default=1)
    req_unit_price = Column(Float)
    upc = Column(String(11))
    our_ship_date = Column(DateTime,nullable=True, default=None)
    our_rev_level = Column(String(2),nullable=True, default=None)
    our_unit_price = Column(Float, default=0)
    def __repr__(self):
        return "id: {}; PO# {}; ln: {}; pn: {}; req_rev_level: {}; req_ship_date: {}; req_qty: {};".format(self.id, self.po_number, self.ln, self.pn, self.req_rev_level, self.req_ship_date, self.req_qty)

class PO(Base):
    __tablename__ = 'pos'
    po_number = Column(String(10), unique=True, primary_key=True)
    customer_id = Column(String(5), ForeignKey('customers.id'),index=True)
    customer = relationship("Customer", back_populates="pos")
    date_received = Column(DateTime,default=datetime.datetime.utcnow)
    ship_to = Column(Integer,default=0)
    planner = Column(String(64))
    comments = Column(Text)
    csr = Column(String(2),default='CS')
    status = Column(Integer,default=0)
    total = Column(Float,default=0)
    po_lines = relationship("PO_line", order_by=PO_line.ln, back_populates="po")
    def __repr__(self):
        return "PO# {}; date_received: {}; ship_to: {}; planner: {}; comments: {}; csr: {}; status: {}; total: {}".format(self.po_number, self.date_received, self.ship_to, self.planner, self.comments, self.csr, self.status, self.total)

class Price(Base):
    __tablename__='prices'
    id=Column(Integer, primary_key=True)
    serie_id = Column(Integer, ForeignKey('series.id'), index=True)
    serie = relationship("Serie", back_populates='prices')
    length = Column(Float,nullable=False, default=1)
    price = Column(Float, nullable=False, default=0)

class Serie(Base):
    __tablename__='series'
    id=Column(Integer,primary_key=True)
    customer_id = Column(String(5), ForeignKey('customers.id'),index=True)
    customer = relationship("Customer", back_populates="series")
    prices = relationship("Price",order_by=Price.id, back_populates='serie')
    po_lines = relationship("PO_line",order_by=PO_line.id, back_populates="serie")
    pn_format = Column(String(64), nullable=False)
    regex = Column(String(128), nullable=False)
    description = Column(String(64))
    rev_level = Column(String(2),default='00')
    def __repr__(self):
        return "id: {}; customer_id: {}; customer.name: {}; pn_format: {}; regex: {}; description: {}; rev_level: {};".format(self.id, self.customer_id, self.customer.name, self.pn_format, self.regex, self.description, self.rev_level)

class Customer(Base):
    __tablename__ = 'customers'
    id = Column(String(5), unique=True, primary_key=True)
    name = Column(String(16), unique=True, nullable=False)
    series = relationship("Serie", order_by=Serie.pn_format, back_populates="customer")
    pos = relationship("PO", order_by=PO.po_number, back_populates="customer")
    def __repr__(self):
        return "id: {}; name: {};".format(self.id, self.name)

class SNAP_pricing(Base):
    __tablename__ = 'snap_pricing'
    id = Column(String(16),unique=True, primary_key=True)
    regex = Column(String(160), nullable=False)
    base = Column(Float, nullable=False, default=0)
    per_ft_adder = Column(Float, nullable=False, default=0)
    rev_level = Column(String(2),nullable=False, default='00')

    def __repr__(self):
        return "id: {}, regex: {}, base_price: ${}, per ft adder: ${}.".format(self.id, self.regex, self.base, self.per_ft_adder)
    

Session = sessionmaker(bind=engine,autoflush=False)
session = Session()
