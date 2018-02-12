import urllib2
from bs4 import BeautifulSoup


class Band:

    def __init__(self, name):
        self.name = name

    def discography(self):
        url = 'https://en.wikipedia.org/wiki/Special:Search?search=%s_discography' % self.name
        page = urllib2.urlopen(url)
        soup = BeautifulSoup(page, 'html.parser')
        table = soup.find('table', attrs={'class': 'wikitable plainrowheaders'})
        titles = table.findAll(attrs={'scope': 'row'})


        def getAlbumTitles(url):
            albumTitles = [title.text for title in titles]
            return albumTitles

        def getDiscogLinks(url):
            album_href = [row.findAll('a') for row in titles]
            clean_links = []

            for i in album_href:
                for href in i:
                    if href.parent.name == 'i':
                        clean_links.append('https://en.wikipedia.org' + href.get('href'))

            return clean_links

        return dict(zip(getAlbumTitles(url), getDiscogLinks(url)))

name = raw_input('Enter a band name: ')
artist = Band(name)
print(artist.discography())

#beatles = Band('beatles')
#print(beatles.discography())