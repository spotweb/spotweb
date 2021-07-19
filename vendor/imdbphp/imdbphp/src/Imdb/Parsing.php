<?php

namespace Imdb;

class Parsing
{

    /**
     * Parse a HTML table into an array of rows which are an array of cells containing the string in each <td></td>
     * @param string $html The HTML to parse
     * @param string $xpath XPath to the table
     * @return array
     */
    public static function table($html, $xpath)
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xp = new \DOMXPath($doc);
        $table = $xp->query($xpath)->item(0);

        if (!$table) {
            return array();
        }

        $resultTable = array();
        foreach ($table->childNodes as $row) {
            if ($row->nodeType === XML_ELEMENT_NODE) {
                $resultRow = array();
                foreach ($row->getElementsByTagName('td') as $cell) {
                    $resultRow[] = trim($cell->textContent);
                }
                if (!empty($resultRow)) {
                    $resultTable[] = $resultRow;
                }
            }
        }

        return $resultTable;
    }
}
