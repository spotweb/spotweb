<?php

class Services_NzbHandler_Display extends Services_NzbHandler_abs
{
    public function __construct(Services_Settings_Container $settings, array $nzbHandling)
    {
        parent::__construct($settings, 'Display', 'Show', $nzbHandling);
    }

    // __construct

    public function processNzb($fullspot, $nzblist)
    {
        $nzb = $this->prepareNzb($fullspot, $nzblist);

        header('Content-Type: '.$nzb['mimetype']);

        switch ($this->_nzbHandling['prepare_action']) {
            case 'zip': header('Content-Disposition: attachment; filename="'.$nzb['filename'].'"'); break;
            default: header('Content-Disposition: inline; filename="'.$nzb['filename'].'"');
        } // switch
        echo $nzb['nzb'];
    }

    // processNzb
} // class Services_NzbHandler_Display
