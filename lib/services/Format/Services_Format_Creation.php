<?php

class Services_Format_Creation
{
    /*
     * Creates XML out of the Spot information array
     */
    public function convertSpotToXml($spot, $imageInfo, $nzbSegments)
    {
        // XML
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = false;

        $mainElm = $doc->createElement('Spotnet');
        $postingElm = $doc->createElement('Posting');
        $postingElm->appendChild($doc->createElement('Key', $spot['key']));
        if (array_key_exists('created', $spot) && strlen($spot['created']) > 0) {
            $postingElm->appendChild($doc->createElement('Created', $spot['created']));
        } else {
            $postingElm->appendChild($doc->createElement('Created', time()));
        } // else
        $postingElm->appendChild($doc->createElement('Poster', $spot['poster']));
        $postingElm->appendChild($doc->createElement('Size', $spot['filesize']));

        if (strlen($spot['tag']) > 0) {
            $postingElm->appendChild($doc->createElement('Tag', $spot['tag']));
        } // if

        /*
         * Title element is enclosed in CDATA
         */
        $titleElm = $doc->createElement('Title');
        $titleElm->appendChild($doc->createCDATASection(htmlentities($spot['title'], ENT_NOQUOTES, 'UTF-8')));
        $postingElm->appendChild($titleElm);

        /*
         * Description element is enclosed in CDATA
         */
        $descrElm = $doc->createElement('Description');
        $descrElm->appendChild($doc->createCDATASection(htmlentities(str_replace(["\r\n", "\r", "\n"], '[br]', $spot['body']), ENT_NOQUOTES, 'UTF-8')));
        $postingElm->appendChild($descrElm);

        /*
         * Website element ins enclosed in cdata section
         */
        $websiteElm = $doc->createElement('Website');
        $websiteElm->appendChild($doc->createCDATASection($spot['website']));
        $postingElm->appendChild($websiteElm);

        /*
         * Category contains both an textelement as nested elements, so
         * we do it somewhat different
         *   <Category>01<Sub>01a09</Sub><Sub>01b04</Sub><Sub>01c00</Sub><Sub>01d11</Sub></Category>
         */
        $categoryElm = $doc->createElement('Category');
        $categoryElm->appendChild($doc->createTextNode(str_pad($spot['category'] + 1, 2, '0', STR_PAD_LEFT)));

        foreach ($spot['subcatlist'] as $subcat) {
            if (!empty($subcat)) {
                $categoryElm->appendChild($doc->createElement(
                    'Sub',
                    str_pad($spot['category'] + 1, 2, '0', STR_PAD_LEFT).
                        $subcat[0].
                        str_pad(substr($subcat, 1), 2, '0', STR_PAD_LEFT)
                ));
            } // if
        } // foreach
        $postingElm->appendChild($categoryElm);

        /*
         * We only support embedding the image on usenet, so
         * we always use that
         *
         * 		<Image Width='1500' Height='1500'><Segment>4lnDJqptSMMifJpTgAc52@spot.net</Segment><Segment>mZgAC888A6EkfJpTgAJEX@spot.net</Segment></Image>
         */
        $imgElm = $doc->createElement('Image');
        $imgElm->setAttribute('Width', $imageInfo['width']);
        $imgElm->setAttribute('Height', $imageInfo['height']);
        foreach ($imageInfo['segments'] as $segment) {
            $imgElm->appendChild($doc->createElement('Segment', $segment));
        } // foreach
        $postingElm->appendChild($imgElm);

        /*
         * Add the segments to the nzb file
         */
        $nzbElm = $doc->createElement('NZB');
        foreach ($nzbSegments as $segment) {
            $nzbElm->appendChild($doc->createElement('Segment', $segment));
        } // foreach
        $postingElm->appendChild($nzbElm);

        $mainElm->appendChild($postingElm);
        $doc->appendChild($mainElm);

        return $doc->saveXML($mainElm);
    }

    // spotToXml
} // Services_Format_Creation
