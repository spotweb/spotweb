<?php

class Services_NzbHandler_Factory
{
    public static function build(Services_Settings_Container $settings, $action, array $nzbHandling)
    {
        /*
         * We explicitly add new handlers, because we cannot be sure
         * what to load with __autoload
         */
        switch ($action) {
            case 'disable': $handler = new Services_NzbHandler_Disable($settings, $nzbHandling); break;
            case 'save': $handler = new Services_NzbHandler_Save($settings, $nzbHandling); break;
            case 'runcommand': $handler = new Services_NzbHandler_Runcommand($settings, $nzbHandling); break;
            case 'push-sabnzbd': $handler = new Services_NzbHandler_Pushsabnzbd($settings, $nzbHandling); break;
            case 'client-sabnzbd': $handler = new Services_NzbHandler_Clientsabnzbd($settings, $nzbHandling); break;
            case 'nzbget': $handler = new Services_NzbHandler_Nzbget($settings, $nzbHandling); break;
            case 'nzbvortex': $handler = new Services_NzbHandler_NZBVortex($settings, $nzbHandling); break;
            default: $handler = new Services_NzbHandler_Display($settings, $nzbHandling); break;
        } // switch

        if (!$handler instanceof Services_NzbHandler_Disable && $handler->isAvailable() !== true) {
            $handler = new Services_NzbHandler_Disable($settings, $nzbHandling);
        }

        return $handler;
    }

    // build()
} // class Services_NzbHandler_Factory
