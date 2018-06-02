# Spotweb
Spotweb is a decentralized usenet community based on the [Spotnet](/spotnet/spotnet/wiki) protocol.

Spotweb requires an operational webserver with PHP5 installed, it uses either an MySQL or an PostgreSQL database to store it's contents in. 

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
* Boxcar/Growl/Notify My Android/Notify/Prowl and Twitter integration. (*)
* Spot statistics on your system.
* Sabnzbd and nzbget(*) integration.
* Multi-language. (*)
* Multiple-user ready. (*)
* Opensource and open development model. (*)

(*) Unique feature among all known Spotnet clients.

## Installation requirements
Spotweb has been regulary tested on several different systems. Spotweb is mostly used on:

* Unix-based (Linux, FreeBSD) operating systems or small NAS systems like Synology and QNAP.
* Apache Webserver.
* PHP v5.3 or higher, with at least these modules:
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

Please run 'install.php' from within your browser before attempting anything further with Spotweb and make sure
all items are checked 'OK'.

## Installation
Installation is the toughest part of Spotweb. Depending on your platform you should look at the different tutorials available on the [Spotweb wiki](https://github.com/spotweb/spotweb/wiki), but the basic steps are:

1. Ensure you have an database server installed (MySQL, PostgreSQL or SQLite).
2. Create an empty 'spotweb' database.
3. Ensure you have a webserver running and PHP is configured for this webserver.
4. [Download the Spotweb zip file.](https://github.com/spotweb/spotweb/archive/master.zip)
5. Unpack the zip file to a directory of choice.
6. Open 'install.php' in your browser until everything is 'OK'. Fix the parts which aren't OK.
7. Follow the wizard and perform the instructions as given by the wizard.

## Troubleshooting
When a white page appears instead of your Spotweb installation, this usually indicates an typing error in either
your ownsettings.php, dbsettings.inc.php or a configuration error in your webserver.

Please consult your Apache's errorlog for the exact error and fix it.
