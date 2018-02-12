# import libraries
import urllib2
from bs4 import BeautifulSoup
from datetime import datetime, timedelta

# specify the url
#quote_page = 'http://shopfloor-apj.sfng.int.hpe.com/sfweb/OpenOrdersReport?operations_m=none&fromBirthStamp=2018-01-21-17-55&toBirthStamp=2018-01-23-17-55&coaStatus=All&shipPoint=BF40&&queryType=openOrders&sortBy=Sales+Order'

todate = datetime.now()
todate = todate.strftime("%Y-%m-%d-%H-%M")

fromdate = datetime.now() - timedelta(days=1)
fromdate = fromdate.strftime("%Y-%m-%d-%H-%M")

quote_page = 'http://shopfloor-apj.sfng.int.hpe.com/sfweb/OpenOrdersReport?operations_m=none&fromBirthStamp='+fromdate+'&toBirthStamp='+todate+'&coaStatus=All&shipPoint=BF40&&queryType=openOrders&sortBy=Sales+Order'

# query the website and return the html to the variable ‘page’
# page = urllib2.urlopen(quote_page)
request = urllib2.Request(quote_page, headers={"Cookie": "timezoneOffset=480"})
page = urllib2.urlopen(request)

# parse the html using beautiful soup and store in variable `soup`
soup = BeautifulSoup(page, 'html.parser')

# Take out the <div> of name and get its value
# name_box = soup.find(‘h1’, attrs={‘class’: ‘name’})
# name = name_box.text.strip() # strip() is used to remove starting and trailing
# print name

# table = soup.find('div', attrs={'class': 'content'})

# rows = table.findAll('tr')

rows = soup.findAll('tr')
for tr in rows:
    cols = tr.findAll('td')
    if 'cell_c' in cols[0]['class']:
        # currency row
        digital_code, letter_code, units, name, rate = [c.text for c in cols]
        print digital_code, letter_code, units, name, rate