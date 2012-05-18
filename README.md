#SpotWeb
Spotweb is a webbased usenet binary resource indexer based on the protocol and development done by Spotnet (http://github.com/spotnet).

Spotweb requires an operational webserver with PHP5 installed, it uses either an MySQL or an PostgreSQL database to store it's contents in. 

## Features
Spotweb is one of the most-featured Spotnet clients currently available, featuring among other things:

* Fast
* Customizable filter system from within the system
* Posting of comments and spots
* Showing and filtering on new spots since the last view
* Watchlist
* Easy to download multiple files
* Runs on NAS devices like Synology and qnap
* Rating of spots
* Integration with [Sick beard](http://www.sickbeard.com) and [CouchPotato](http://couchpotatoapp.com/) as a 'newznab' provider
* Platform independent (reported to work on Linux, *BSD and Windows)
* Both central as user-specific blacklist support built-in
* Spam reporting
* Easy layout customization by providing custom CSS
* Boxcar/Growl/Notify My Android/Notify/Prowl and Twitter integration (*)
* Spot statistics on your system
* Sabnzbd and nzbget(*) integration
* Multi-language (*) 
* Multiple-user ready (*)
* Opensource and open development model (*)

(*) Unique feature among all known Spotnet clients

## Installation
Installation is the toughest part of Spotweb. Depending on your platform you should look at the different tutorials available on the [Spotweb wiki](https://github.com/spotweb/spotweb/wiki), but the basic steps are:

1. Ensure you have an database server installed (either MySQL or PostgreSQL)
2. Create an empty 'spotweb' database
3. Ensure you have a webserver running and PHP is configured for this webserver
3. Download Spotweb 
4. Unpack Spotweb to a directory of your choosing
5. Open 'install.php' in your browser until everything is 'OK'. Fix the parts which aren't OK.
6. Follow the wizard and perform the instructions as given by the wizard.
