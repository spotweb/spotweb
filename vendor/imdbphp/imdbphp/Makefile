# Makefile for imdbphp
# $Id$

DESTDIR=
prefix=/usr/local
libdir=$(DESTDIR)/usr/share/php
datarootdir=$(DESTDIR)$(prefix)/share
datadir=$(datarootdir)/imdbphp
docdir=$(datarootdir)/doc/imdbphp
INSTALL=install -p
INSTALL_DATA=$(INSTALL) -m 644
WEBROOT=$(DESTDIR)/var/www
LINKTO=$(WEBROOT)/imdbphp

install: installdirs
	cp -pr doc/* $(docdir)
	cp -p README.md $(docdir)
	$(INSTALL_DATA) src/*.php $(libdir)
	$(INSTALL_DATA) src/Imdb/*.php $(libdir)/Imdb
	$(INSTALL_DATA) src/Imdb/Exception/* $(libdir)/Imdb/Exception
	$(INSTALL_DATA) src/MoviePosterDb/* $(libdir)/MoviePosterDb
	$(INSTALL_DATA) demo/*.* $(datadir)/demo
	$(INSTALL_DATA) demo/imgs/showtimes/*.* $(datadir)/demo/imgs/showtimes
	$(INSTALL_DATA) conf/* $(libdir)/conf
	$(INSTALL_DATA) tests/cache/.placeholder $(datadir)/tests/cache
	$(INSTALL_DATA) tests/resources/* $(datadir)/tests/resources
	$(INSTALL_DATA) tests/*.* $(datadir)/tests
	$(INSTALL_DATA) tests/cache_nonwriteable $(datadir)/tests
	$(INSTALL_DATA) bootstrap.php $(datadir)
	if [ ! -e $(LINKTO) ]; then ln -s $(datadir) $(LINKTO); fi

installdirs:
	mkdir -p $(libdir)/conf
	mkdir -p $(libdir)/Imdb/Exception
	mkdir -p $(libdir)/MoviePosterDb
	mkdir -p $(datadir)/cache
	mkdir -p $(datadir)/demo
	mkdir -p $(datadir)/images
	mkdir -p $(datadir)/tests/cache
	mkdir -p $(datadir)/tests/resources
	mkdir -p $(datadir)/demo/imgs/showtimes
	chmod 0777 $(datadir)/cache $(datadir)/images
	mkdir -p $(libdir)
	mkdir -p $(docdir)
	if [ ! -d $(WEBROOT) ]; then mkdir -p $(WEBROOT); fi

uninstall:
	if [ "`readlink $(LINKTO)`" = "$(datadir)" ]; then rm -f $(LINKTO); fi
	rmdir --ignore-fail-on-non-empty $(datadir)/cache
	rmdir --ignore-fail-on-non-empty $(datadir)/images
	rm -rf $(datadir)/demo
	rm -rf $(datadir)/tests
	rm -f  $(datadir)/bootstrap.php
	rm -rf $(libdir)/Imdb
	rm -rf $(libdir)/MoviePosterDb
	rm -f  $(libdir)/BrowserEmulator.php
	rm -f  $(libdir)/conf/900_localconf.sample
	rmdir --ignore-fail-on-non-empty $(libdir)/conf
	rmdir --ignore-fail-on-non-empty $(datadir)
	rm -rf $(docdir)


