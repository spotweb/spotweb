<?php

class Services_Retriever_SpotsArticleParser
{
    private $_spotParseUtil;
    private $_spotSigning;

    public function __construct()
    {
        $this->_spotParseUtil = new Services_Format_Util();
        $this->_spotSigning = Services_Signing_Base::factory();
    }

    public function parse($messageId, array $article)
    {
        $spot = [
            'fullxml'        => '',
            'user-signature' => '',
            'user-key'       => ['exponent' => '', 'modulo' => ''],
            'verified'       => false,
            'messageid'      => $messageId,
            'spotterid'      => '',
            'xml-signature'  => '',
            'moderated'      => 0,
            'user-avatar'    => '',
            'newsreader'     => '',
        ];

        $spot = $this->parseHeader($article['header'], $spot);
        $spot['verified'] = $this->_spotSigning->verifyFullSpot($spot);

        if ($spot['verified']) {
            $spot['spotterid'] = $this->_spotParseUtil->calculateSpotterId($spot['user-key']['modulo']);
        }

        if (strlen($spot['fullxml']) > (1024 * 50)) {
            return null;
        }

        $spotParser = new Services_Format_Parsing();

        return array_merge($spotParser->parseFull($spot['fullxml']), $spot);
    }

    private function parseHeader($headerList, $tmpAr)
    {
        foreach ($headerList as $hdr) {
            $keys = explode(':', $hdr);

            switch (strtolower($keys[0])) {
                case 'from':
                    $fromEnd = strpos($hdr, '<');
                    if ($fromEnd === false) {
                        $fromEnd = strlen($hdr);
                    }
                    $tmpAr['fromhdr'] = mb_convert_encoding(trim(substr($hdr, strlen('From: '), $fromEnd - 1 - strlen('From: '))), 'ISO-8859-1', 'UTF-8');
                    break;
                case 'date':
                    $tmpAr['stamp'] = strtotime(substr($hdr, strlen('Date: ')));
                    break;
                case 'x-xml':
                    $tmpAr['fullxml'] .= substr($hdr, 7);
                    break;
                case 'x-user-signature':
                    $tmpAr['user-signature'] = $this->_spotParseUtil->spotUnprepareBase64(substr($hdr, 18));
                    break;
                case 'x-xml-signature':
                    $tmpAr['xml-signature'] = $this->_spotParseUtil->spotUnprepareBase64(substr($hdr, 17));
                    break;
                case 'x-newsreader':
                    $tmpAr['newsreader'] = substr($hdr, 14);
                    break;
                case 'x-user-avatar':
                    $tmpAr['user-avatar'] .= substr($hdr, 15);
                    break;
                case 'x-user-key':
                    $xml = simplexml_load_string(substr($hdr, 12));
                    if ($xml !== false) {
                        $tmpAr['user-key']['exponent'] = (string) $xml->Exponent;
                        $tmpAr['user-key']['modulo'] = (string) $xml->Modulus;
                    }
                    break;
            }
        }

        if ((!empty($tmpAr['fullxml'])) && (!empty($tmpAr['newsreader']))) {
            @$xml = simplexml_load_string($tmpAr['fullxml']);
            if ($xml == false) {
                $tmpAr['fullxml'] = preg_replace("/'([a-z,A-Z])/", "' $1", $tmpAr['fullxml']);
                @$xml = simplexml_load_string($tmpAr['fullxml']);
            }
            if ($xml !== false) {
                $extra = $xml->addChild('Extra');
                $extra->addchild('Newsreader', $tmpAr['newsreader']);
                $tmpAr['fullxml'] = (string) $xml->asXML();
            }
        }

        return $tmpAr;
    }
}
