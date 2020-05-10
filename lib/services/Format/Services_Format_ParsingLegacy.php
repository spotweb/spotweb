<?php

/*
 * Parser for the older and no longer used format (FTD spots)
 */
class Services_Format_ParsingLegacy
{
    private function parseEncodedWord($inputStr)
    {
        $builder = '';

        if (substr($inputStr, 0, 1) !== '=') {
            return $inputStr;
        } // if

        if (substr($inputStr, strlen($inputStr) - 2) !== '?=') {
            return $inputStr;
        } // if

        $name = substr($inputStr, 2, strpos($inputStr, '?', 2) - 2);
        if (strtoupper($name) == 'UTF8') {
            $name = 'UTF-8';
        } // if

        $c = $inputStr[strlen($name) + 3];
        $startIndex = strlen($name) + 5;

        switch (strtolower($c)) {
            case 'q':

                while ($startIndex < strlen($inputStr)) {
                    $ch2 = $inputStr[$startIndex];
                    $chArray = null;

                    switch ($ch2) {
                        case '=':
                            if ($startIndex >= (strlen($inputStr) - 2)) {
                                $chArray = substr($inputStr, $startIndex + 1, 2);
                            } // if

                            if ($chArray == null) {
                                echo 'Untested code path!';
                                $builder .= $chArray.chr(10);
                                $startIndex += 3;
                            } // if

                            break;
                         // case '='

                        case '?':
                            if ($inputStr[$startIndex + 1] == '=') {
                                $startIndex += 2;
                            } // if

                            break;
                         // case '?'
                    } // switch

                    $builder .= $ch2;
                    $startIndex++;
                } // while
                break;
             // case 'q'

            case 'b':

                $builder .= base64_decode(substr($inputStr, $startIndex, ((strlen($inputStr) - $startIndex) - 2)));
                break;
             // case 'b'
        } // switch

        return $builder;
    }

    // parseEncodedWord

    public function oldEncodingParse($inputStr)
    {
        $builder = '';
        $builder2 = '';
        $encodedWord = false;
        $num = 0;

        while ($num < strlen($inputStr)) {
            $bliep = false;
            $ch = $inputStr[$num];

            switch ($ch) {
                case '=':

                        if (($num != (strlen($inputStr) - 1)) && ($inputStr[$num + 1] == '?')) {
                            $encodedWord = true;
                        } // if
                        break;
                 // case '='

                case '?':

                        $ch2 = ' ';

                        if ($num != (strlen($inputStr) - 1)) {
                            $ch2 = $inputStr[$num + 1];
                        } // if

                        if ($ch2 != '=') {
                            break;
                        } // if

                        $encodedWord = false;
                        $builder .= $ch.$ch2;
                        $builder2 .= $this->parseEncodedWord($builder);
                        $builder = '';
                        $num += 2;
                        $bliep = true;
                        break;
                 // case '?'
            } // switch

            if (!$bliep) {
                if ($encodedWord) {
                    $builder .= $ch;
                    $num++;
                } else {
                    $builder2 .= $ch;
                    $num++;
                } // else
            } // if
        } // while

        return $builder2;
    }

    // oldEncodingParse
} // Services_Format_ParsingLegacy
