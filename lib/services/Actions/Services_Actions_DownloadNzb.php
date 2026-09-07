<?php

class Services_Actions_DownloadNzb
{
    private $_settings;
    private $_daoFactory;

    public function __construct(Services_Settings_Container $settings, Dao_Factory $daoFactory)
    {
        $this->_settings = $settings;
        $this->_daoFactory = $daoFactory;
    }

    // ctor

    /*
     * Convert the compact bulk request value used by the multi-NZB interface
     * into the message-id list handled below. Existing single and array
     * messageid request shapes deliberately remain unchanged.
     */
    public static function resolveMessageIds($messageId, $bulkMessageIds)
    {
        if ($bulkMessageIds === null) {
            return $messageId;
        } // if

        if (!is_string($bulkMessageIds)) {
            throw new Exception('Invalid bulk NZB message ID list');
        } // if

        $messageIds = json_decode($bulkMessageIds, true);
        if (json_last_error() != JSON_ERROR_NONE || !self::isList($messageIds) || empty($messageIds)) {
            throw new Exception('Invalid bulk NZB message ID list');
        } // if

        foreach ($messageIds as $thisMessageId) {
            if (!is_string($thisMessageId) || $thisMessageId === '') {
                throw new Exception('Invalid bulk NZB message ID list');
            } // if
        } // foreach

        return $messageIds;
    }

    // resolveMessageIds

    private static function isList($values)
    {
        if (!is_array($values)) {
            return false;
        } // if

        $expectedKey = 0;
        foreach ($values as $key => $value) {
            if ($key !== $expectedKey) {
                return false;
            } // if

            $expectedKey++;
        } // foreach

        return true;
    }

    // isList

    /*
     * Check whether the appropriate permissions are there, and if so actually run the code
     */
    public function handleNzbAction($messageIds, array $currentSession, $action, Services_Providers_FullSpot $svcProvSpot, Services_Providers_Nzb $svcProvNzb)
    {
        if (!is_array($messageIds)) {
            $messageIds = [$messageIds];
        } // if

        // Make sure the user has the appropriate permissions
        $currentSession['security']->fatalPermCheck(SpotSecurity::spotsec_retrieve_nzb, '');
        if ($action != 'display') {
            $currentSession['security']->fatalPermCheck(SpotSecurity::spotsec_download_integration, $action);
        } // if

        /*
         * Get all the full spots for all of the specified NZB files
         */
        $nzbList = [];
        $fullSpot = [];
        foreach ($messageIds as $thisMsgId) {
            $fullSpot = $svcProvSpot->fetchFullSpot($thisMsgId, $currentSession['user']['userid']);

            if (!empty($fullSpot['nzb'])) {
                $nzbList[] = ['spot' => $fullSpot,
                    'nzb'            => $svcProvNzb->fetchNzb($fullSpot), ];
            } // if
        } // foreach

        /*
         * send nzblist to NzbHandler plugin
         */
        $nzbHandlerFactory = new Services_NzbHandler_Factory();
        $nzbHandler = $nzbHandlerFactory->build($this->_settings, $action, $currentSession['user']['prefs']['nzbhandling']);

        $nzbHandler->processNzb($fullSpot, $nzbList);

        /*
         * and mark the spot as downloaded
         */
        if ($currentSession['user']['prefs']['keep_downloadlist']) {
            if ($currentSession['security']->allowed(SpotSecurity::spotsec_keep_own_downloadlist, '')) {
                $spotStateListDao = $this->_daoFactory->getSpotStateListDao();

                foreach ($messageIds as $thisMsgId) {
                    $spotStateListDao->addToDownloadList($thisMsgId, $currentSession['user']['userid']);
                } // foreach
            } // if
        } // if

        // and send notifications
        $spotsNotifications = new SpotNotifications($this->_daoFactory, $this->_settings, $currentSession);
        $spotsNotifications->sendNzbHandled($action, $fullSpot);
    }

    // handleNzbAction
} // Services_Actions_DownloadNzb
