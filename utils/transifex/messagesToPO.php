<?php

/**
 * messagesToPO.php.
 *
 * Takes the currently translated Dutch messages.po file
 * and creates a template ready for translation
 *
 * Tested on OS X 10.8.2 PHP 5.4.10 and WinXP PHP 5.4.12
 *
 * @usage      messagesToPO.php [-o]
 *             -o convert from old format header, probably won't be needed
 *              as this messages.po has the new header
 *
 * @version     1.0 First commit Sun Mar 10 03:50:52 HKT 2013
 *
 * @author      James Stout <james@stouty.me>
 * @license     https://github.com/spotweb/spotweb/blob/master/LICENSE
 */
$oldFormat = false;

// quick check of opts
$opts = getopt('ho', ['h', 'help']);

foreach (array_keys($opts) as $opt) {
    switch ($opt) {
  case 'o':
    echo "Input file is using old format header, we will double check...\n";
    $oldFormat = true;
    break;

  case 'h':
  case 'help':
    usage();
}
}

// create a proper gettext po header
// check with msgfmt -c messages.po
$headerStr = <<<'EOF'
# This file is distributed under the same license as the SpotWeb package.
# Copyright (c) 2013, Spotweb
# All rights reserved.
# 
msgid ""
msgstr ""

"Project-Id-Version: SpotWeb\n"
"Report-Msgid-Bugs-To: Test <test@gmail.com>\n"
"POT-Creation-Date: 2013-01-20 06:10+0800\n"
"PO-Revision-Date: 2013-01-20 06:10+0800\n"
"Last-Translator: Test <test@gmail.com>\n"
"Language-Team: English\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Language: en\n"
"Plural-Forms: nplurals=2; plural=(n!=1);\n"

EOF;

$path = dirname(__FILE__);

$outputFile = $path.'/messages_template.po';

$currentPOFile = $path.'/../../locales/nl_NL/LC_MESSAGES/messages.po';    // does this work on Windows?
                                                                            // it does with the dirname(__FILE__)
                                                                            // you can't use a relative path like
                                                                            // "../../locales/nl_NL/LC_MESSAGES/messages.po"

echo "Input file is $currentPOFile\n";
echo "Output File is $outputFile\n";

// open output file for writing, truncate and in binary mode
// binary mode preserves the line endings in the input file
// \n for Unix, \r\n for Windows.
if (!$handle = fopen($outputFile, 'w+b')) {
    exit("Cannot open $outputFile");
}

// open file and read contents into lines array
if (!$lines = file($currentPOFile)) {
    exit("Cannot open $currentPOFile");
} else {

    // double check that we are really using an old format file
    if ($oldFormat == true) {
        $oldFormat = checkFileIsNotNewFormat($lines);
    }

    // for testing line endings
    //dump($line;

    if ($oldFormat == true) {
        // write new format header to file
        wrap_fwrite($handle, $headerStr, $outputFile);
    }

    $linecount = 0;

    foreach ($lines as $line) {
        $linecount++;

        if ($oldFormat == true) {
            // skip first 8 lines of old file
            if ($linecount < 8) {
                continue;
            }
        }

        parseAndWriteLine($line, $handle, $outputFile);
    }
    echo "Template file written successfully\n";
    fclose($handle);
}

/**
 * checks the line, determines what to write to the template file
 * then writes line to the output file.
 *
 * @param string   $line       line to write to the file
 * @param resource $handle     file pointer resource
 * @param string   $outputFile path and filename that handle points to
 */
function parseAndWriteLine($line, $handle, $outputFile)
{

    // if the line starts with (#|"|msgid_plural |msgid )
    // then we just write the line to the output file verbatim
    $pattern = '/^(#|"|msgid_plural |msgid )/';

    preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE);
    //var_dump($matches);

    if (count($matches) > 0) {
        // $matches array[0][1] is the offset where the pattern
        // mtached, should be zero
        if ($matches[0][1] === 0) {
            wrap_fwrite($handle, $line, $outputFile);
            //return; -- could return here but we need the nl after the header
        }
    }

    // need to add a new line after the header
    $findMe = '"Plural-Forms';

    $pos = strpos($line, $findMe);

    // triple === as could be false
    if ($pos === 0) {
        wrap_fwrite($handle, "\n", $outputFile);

        return; // -- so we return here
    }

    // if line starts with msgstr[0]
    // write msgstr[0] "" with ONE \n
    $findMe = 'msgstr[0]';

    $pos = strpos($line, $findMe);

    // triple === as could be false
    if ($pos === 0) {
        wrap_fwrite($handle, 'msgstr[0] ""'."\n", $outputFile);

        return;
    }

    // if line starts with msgstr[1]
    // write msgstr[1] "" with TWO \n
    $findMe = 'msgstr[1]';

    $pos = strpos($line, $findMe);

    // triple === as could be false
    if ($pos === 0) {
        wrap_fwrite($handle, 'msgstr[1] ""'."\n\n", $outputFile);

        return;
    }

    // if line starts with 'msgstr ' -- note the space
    // write msgstr "" with TWO \n
    $findMe = 'msgstr ';

    $pos = strpos($line, $findMe);

    // triple === as could be false
    if ($pos === 0) {
        wrap_fwrite($handle, 'msgstr ""'."\n\n", $outputFile);

        return;
    }
}

/**
 * check that we are really using an old format file.
 *
 * @param array $lines contents of the current messages.po file
 *
 * @return bool true if the mo file is really the old format, else false
 */
function checkFileIsNotNewFormat($lines)
{
    $isNew = false;

    foreach ($lines as $line) {

        // this header is only in the new format mo file
        if (strpos($line, 'Project-Id-Version') == true) {
            echo "Input file is in fact using the new format header\n";
            $isNew = false;
            break;
        }
    }

    return $isNew;
}

/**
 * simple wrapper to avoid repeat error check and die typing.
 *
 * @param resource $handle     file pointer resource
 * @param string   $line       line to write to the file
 * @param string   $outputFile path and filename that handle points to
 */
function wrap_fwrite($handle, $line, $outputFile)
{
    if (!fwrite($handle, $line)) {
        fclose($handle);
        exit("Cannot write to $outputFile");
    }
}

function usage()
{
    echo 'Usage: messagesToPO.php [-o]
        -o convert from old format header, probably won\'t be needed as this messages.po has the new header';
    exit(1);
}

/**
 * dump a variable, translates different line endings into visible/understandable chars.
 *
 * @param mixed $value Base-64 encoded data
 * @param int   $level Optional. Defaults to 0. 0 outputs translated HTML
 *                     -1 returns the translated string
 *
 * @return string Only if $level is set to -1
 */
function dump($value, $level = 0)
{
    if ($level == -1) {
        $trans[' '] = '&there4;';
        $trans["\t"] = '&rArr;';
        $trans["\n"] = '&para;;';
        $trans["\r"] = '&lArr;';
        $trans["\0"] = '&oplus;';

        return strtr(htmlspecialchars($value), $trans);
    }
    if ($level == 0) {
        echo '<pre>';
    }
    $type = gettype($value);
    echo $type;
    if ($type == 'string') {
        echo '('.strlen($value).')';
        $value = dump($value, -1); //calls itself
    } elseif ($type == 'boolean') {
        $value = ($value ? 'true' : 'false');
    } elseif ($type == 'object') {
        $props = get_class_vars(get_class($value));
        echo '('.count($props).') <u>'.get_class($value).'</u>';
        foreach ($props as $key=>$val) {
            echo "\n".str_repeat("\t", $level + 1).$key.' => ';
            dump($value->$key, $level + 1);
        }
        $value = '';
    } elseif ($type == 'array') {
        echo '('.count($value).')';
        foreach ($value as $key=>$val) {
            echo "\n".str_repeat("\t", $level + 1).dump($key, -1).' => ';
            dump($val, $level + 1);
        }
        $value = '';
    }
    echo " <b>$value</b>";
    if ($level == 0) {
        echo '</pre>';
    }
}
