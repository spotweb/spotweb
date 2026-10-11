# Spotweb
Spotweb is a decentralized usenet community based on the [Spotnet](https://github.com/spotnet/spotnet/wiki) protocol.

Spotweb requires an operational web server running PHP. It uses MySQL, PostgreSQL or SQLite to store its contents. 
<table align="center"><tr><td align="center" width="9999">
 
[![Travis-CI Status](https://travis-ci.org/spotweb/spotweb.svg?branch=develop)](https://travis-ci.org/spotweb/spotweb)
[![Coverage Status](https://coveralls.io/repos/github/spotweb/spotweb/badge.svg?branch=develop)](https://coveralls.io/github/spotweb/spotweb?branch=develop)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spotweb/spotweb/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/spotweb/spotweb/?branch=develop)

[![Scrutinzer Status](https://scrutinizer-ci.com/g/spotweb/spotweb/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/spotweb/spotweb/build-status/develop)
[![Code consistency](https://squizlabs.github.io/PHP_CodeSniffer/analysis/squizlabs/PHP_CodeSniffer/grade.svg)](https://squizlabs.github.io/PHP_CodeSniffer/analysis/squizlabs/PHP_CodeSniffer)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/spotweb/spotweb/badges/code-intelligence.svg?b=develop)](https://scrutinizer-ci.com/code-intelligence)
[![StyleCI](https://github.styleci.io/repos/21400663/shield?branch=develop)](https://github.styleci.io/repos/21400663)

[![Maintainability](https://api.codeclimate.com/v1/badges/c31daabe77475f15e11d/maintainability)](https://codeclimate.com/github/spotweb/spotweb/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/c31daabe77475f15e11d/test_coverage)](https://codeclimate.com/github/spotweb/spotweb/test_coverage)

[![PHP versions checked by Scrutinizer](https://img.shields.io/badge/PHP%20analysis-8.1%20%7C%208.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-blue.svg)](https://scrutinizer-ci.com/g/spotweb/spotweb/build-status/develop)
</td></tr></table>

## Features
Spotweb is one of the most-featured Spotnet clients currently available, featuring among other things:

* Fast.
* Customizable filter system from within the system.
* Posting of comments and spots.
* Showing and filtering on new spots since the last view.
* Watchlist.
* Easy to download multiple files.
* Runs on NAS devices like Synology and QNAP.
* Rating of spots.
* Integration with [Sick Gear](https://github.com/SickGear/SickGear/wiki) , [Sick beard](http://www.sickbeard.com) and [CouchPotato](http://couchpotatoapp.com/) as a 'newznab' provider.
* Platform independent (reported to work on Linux, *BSD and Windows).
* Both central as user-specific blacklist support built-in.
* Spam reporting.
* Easy layout customization by providing custom CSS.
* Growl/Notify My Android/Notify/Prowl and Twitter integration. (*)
* Spot statistics on your system.
* Sabnzbd and nzbget(*) integration.
* Multi-language. (*)
* Multiple-user ready. (*)
* Opensource and open development model. (*)

(*) Unique feature among all known Spotnet clients.

## Installation requirements

Spotweb has been tested on several different systems. It is commonly used on:

* Unix-based (Linux, FreeBSD) operating systems or small NAS systems like Synology and QNAP.
* Apache Webserver.
* PHP with at least the following modules:
  * curl
  * dom
  * gettext
  * mbstring
  * xml
  * zip
  * zlib
  * gd
  * openssl
* MySQL, PostgreSQL and SQLite, where SQLite is the least supported and tested database engine.

Refer to the current Spotweb compatibility and CI results for information about PHP version compatibility.

Before using Spotweb, open `install.php` in your browser and ensure all installation checks show `OK`.

## Installation — Master branch
Installation can vary by platform. Consult the tutorials available on the [Spotweb wiki](https://github.com/spotweb/spotweb/wiki). The basic steps are:

1. Ensure you have a database server installed (MySQL, PostgreSQL or SQLite).
2. Create an empty 'spotweb' database.
3. Ensure you have a web server running and PHP is configured for this web server.
4. [Download the Spotweb ZIP file](https://github.com/spotweb/spotweb/archive/master.zip), or run `git clone -b master https://github.com/spotweb/spotweb.git .` from your public web directory.
5. Unpack the ZIP file into a directory of your choice, if applicable.
6. Open `install.php` in your browser and resolve any checks that are not marked `OK`.
7. Follow the wizard and perform the instructions as given by the wizard.

## Installation — Development branch

1. Ensure you have a database server installed (MySQL, PostgreSQL or SQLite).
2. Create an empty 'spotweb' database.
3. Ensure you have a web server running and PHP is configured for this web server.
4. [Download the Spotweb development ZIP file](https://github.com/spotweb/spotweb/archive/develop.zip), or run `git clone -b develop https://github.com/spotweb/spotweb.git .` from your public web directory.
5. Unpack the ZIP file into a directory of your choice, if applicable.
6. Open `install.php` in your browser and resolve any checks that are not marked `OK`.
7. Follow the wizard and perform the instructions as given by the wizard.

## Troubleshooting
When a blank page appears instead of your Spotweb installation, this usually indicates a syntax error in `ownsettings.php` or `dbsettings.inc.php`, or a configuration error in the web server.

Consult your web server's error log to identify the exact error and resolve it.
