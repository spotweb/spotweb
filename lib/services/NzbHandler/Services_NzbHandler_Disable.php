<?php

class Services_NzbHandler_Disable extends Services_NzbHandler_abs
{
    public function __construct(Services_Settings_Container $settings, array $nzbHandling)
    {
        parent::__construct($settings, 'Disable', 'Disable', $nzbHandling);
    }

    // __construct

    public function processNzb($fullspot, $nzblist)
    {
        // do nothing
    }

    // processNzb

    public function generateNzbHandlerUrl($spot, $spotwebApiParam)
    {
        return '';
    }

    // generateNzbHandlerUrl
} // class Services_NzbHandler_Disable
