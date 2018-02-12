import urllib2
from bs4 import BeautifulSoup


class KDS:

    def __init__(self, name):
        self.name = name

    def discography(self):
        url = 'https://wap.kdslife.com/f_15.html'
        page = urllib2.urlopen(url)
        soup = BeautifulSoup(page, 'html.parser')
        post = soup.findAll('article', attrs={'class': 'forum-wrap clearfix'})
        titles = soup.findAll('a', attrs={'class': 'ui-link'})


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
artist = KDS(name)
print(artist.discography())

#beatles = Band('beatles')
#print(beatles.discography())