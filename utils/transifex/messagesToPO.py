#!/usr/bin/python                                                                                                                                                                  

# takes the currently translated Dutch messages.po file 
# and creates an template ready for translation 
#
# Usage: messagesToPO.po
#

import sys
import os.path

# create a proper gettext po header
# check with msgfmt -c messages.po
headerStr = """# This file is distributed under the same license as the SpotWeb package. 
# Copyright (c) 2011, Spotweb
# All rights reserved.
# 
msgid ""
msgstr ""

"Project-Id-Version: SpotWeb\\n"
"Report-Msgid-Bugs-To: Test <test@gmail.com>\\n"
"POT-Creation-Date: 2013-01-20 06:10+0800\\n"
"PO-Revision-Date: 2013-01-20 06:10+0800\\n"
"Last-Translator: Test <test@gmail.com>\\n"
"Language-Team: English\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Language: en\\n"
"Plural-Forms: nplurals=2; plural=(n!=1);\\n"

"""


currentPOFile = "../../locales/nl_NL/LC_MESSAGES/messages.po"

if  __name__ == '__main__':

    try:
        input = open(currentPOFile)
    except IOError, error:
        print str(error)
        sys.exit(1)

    cwd =  os.getcwd()

    outputFilename = cwd + '/messages_template.po'
    
    print "input file = " + os.getcwd() + "/" + currentPOFile
    print "output file = " + outputFilename

    try:

        output = open(outputFilename, 'w')
    
        output.write(headerStr)
        
        lineNum = 0
        for line in input:
            lineNum = lineNum + 1
            # skip the first 7 lines of the current messages.po
            if lineNum < 8:
                continue

            if line.startswith('#'):
                output.write(line)       

            elif line.startswith('msgid_plural '):
                output.write(line)  

            elif line.startswith('msgid '):
                output.write(line)           

            elif line.startswith('msgstr[0]'):
                output.write('msgstr[0] ""\n')

            elif line.startswith('msgstr[1]'):
                output.write('msgstr[1] ""\n\n')

            elif line.startswith('msgstr '):
                output.write('msgstr ""\n\n')
    except IOError, error:
        print str(error)
        sys.exit(1)
    else:
        input.close()
        output.close()
        print "Template file written successfully"
        sys.exit(0)

