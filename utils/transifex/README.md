# Transifex Translation for SpotWeb #
Transifex is a modern, open-source localization platform. It’s a web system which automates the translation workflow for complex international projects.

##Transifex Setup ##
1. Sign up for free at [Transifex.com](https://www.transifex.com/signup/).
2. Go to the [SpotWeb project](https://www.transifex.com/projects/p/spotweb/).
3. [Request a language](https://www.transifex.com/projects/p/spotweb/languages/add/) or request to be added to an existing Language Team.
4. Click on the language you wish to help translate.
5. Click on messages.po
	*  Select "Download for translation" to download the .po file to your computer. See [Editing .po files][] for details.
	* Or select "Translate Now" or "Try out our new editor" to translate online.
6. Save your online edits or upload your updated .po file.


##Updating Transifex ##
When new Dutch translations are added to `spotweb/locales/nl_NL/LC_MESSAGES/messages.po` a new English template file needs to be created and uploaded to Transifex, followed by the updated Dutch `messages.po` file. If we want English to be the *source language* then we have to have this slightly inefficient two-step method.

1. Create a new English template file using `spotweb/utils/transifex/messagesToPO.py`. This requires [Python](http://www.python.org/download/releases/2.7.3/) - I used 2.7.3 on a Mac.

	`$ pwd`
	
	`/Users/james/tmp/spotweb`
	
	`$ cd utils/transifex/`
	
	`$ python messagesToPO.py`
	
	`input file = /Users/james/tmp/spotweb/utils/transifex/../../locales/nl_NL/LC_MESSAGES/messages.po`
	
	`output file = /Users/james/tmp/spotweb/utils/transifex/messages_template.po`
	
	`Template file written successfully`
	

2. Check the format of the template file:

	`msgfmt -c ./messages_template.po -o - -v`

	There should be no errors. See [Installing gettext][] for info on `msgfmt`.

3. Go to the [SpotWeb resources page](https://www.transifex.com/projects/p/spotweb/resource/messagespo/) on Transifex.

4. Select "Edit resource", choose the new messages_template.po as the "Source File" and save.

5. Back to the [SpotWeb resources page](https://www.transifex.com/projects/p/spotweb/resource/messagespo/) on Transifex, select Dutch.

6. Select "Upload file" and upload the latest `spotweb/locales/nl_NL/LC_MESSAGES/messages.po` file.

7. Both English and Dutch should show up as 100% translated.



## Installing gettext ##
### Mac ###
1. Install [MacPorts](http://www.macports.org/).
2. Install gettext: `sudo port install gettext`
3. `msgfmt` should be in your path.

### Windows ###
1. Download [gettext for Win32](http://sourceforge.net/projects/gettext/files/).
2. Extract these 3 files in the same folder:
	* gettext-runtime-0.13.1.bin.woe32.zip
	* gettext-tools-0.13.1.bin.woe32.zip
	* libiconv-1.9.1.bin.woe32.zip
3. Run `msgfmt` from the command line in the directory where you extracted the files. [More info](https://github.com/spotweb/spotweb/issues/1703#issuecomment-12044181).


## Creating New Locales ##
1. Create the new folders, e.g. `spotweb/locales/fr_FR/LC_MESSAGES/`
2. Add the new/updated `messages.po` to the folder.
3. Run `msgfmt messages.po` in the new folder.
4. Delete your browser cache, reload and test.


##Editing .po files ##
Simply add the translation for a msgid on the msgstr row below it. So, this:

	msgid "Cancel"
	
	msgstr ""
	
	
	msgid "Change"
	
	msgstr ""

Would become:

	msgid "Cancel"
	
	msgstr "Afbreken"
	
	
	msgid "Change"
	
	msgstr "Wijzig"









