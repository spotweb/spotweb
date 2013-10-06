<?php

class Services_Translation_Microsoft {
    // actual translation URL
    const translateUrl = "http://api.microsofttranslator.com/v2/Http.svc/TranslateArray?";
    // oAuth url
    const authUrl      = "https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/";
    // application Scope Url
    const scopeUrl     = "http://api.microsofttranslator.com";
    // application grant type
    const grantType    = "client_credentials";

    /*
     * These id's need to be copied from the Azure market place, please
     * check the follow blog post
     *      http://blogs.msdn.com/b/translation/p/gettingstarted1.aspx
     */
    private $_clientId = '';
    private $_clientSecret = '';

    protected $_settings;
    protected $_cacheDao;

    public function __construct(Services_Settings_Container $settings, Dao_Cache $cacheDao) {
        $this->_settings = $settings;
        $this->_cacheDao = $cacheDao;

        $this->_clientId = $settings->get('ms_translator_clientid');
        $this->_clientSecret = $settings->get('ms_translator_clientsecret');
    } # ctor

    /*
     * Returns whether the translation API is available
     */
    function isAvailable() {
        return (!empty($this->_clientId) && !empty($this->_clientSecret));
    } # isAvailable

    /*
     * Request an actual authentication token
     */
    private function getAuthToken() {
        /*
         * We default to asking our own cache for an token, because
         * if we find one, this saves us one expensive HTTP call.
         *
         * The chance we find an auth token is slim though as it expires
         * after a few seconds
         */
        $svcPrvHttp = new Services_Providers_Http(null);
        $translaterToken = $this->_cacheDao->getCachedTranslaterToken($this->_clientId . $this->_clientSecret);
        if ($translaterToken != false) {
            return $translaterToken['content'];
        } # if

        # Don't even try if no tokens are available
        if (!$this->isAvailable()) {
            return false;
        } # if

        # create the request array
        $paramArr = array (
            'grant_type'    => Services_Translation_Microsoft::grantType,
            'scope'         => Services_Translation_Microsoft::scopeUrl,
            'client_id'     => $this->_clientId,
            'client_secret' => $this->_clientSecret
        );

        $svcPrvHttp->setMethod('POST');
        $svcPrvHttp->setPostContent($paramArr);
        $tmpResult = $svcPrvHttp->perform(Services_Translation_Microsoft::authUrl, null);

        if (!$tmpResult['successful']) {
            $tmpResult = @json_decode($tmpResult['data']);
            if (!empty($tmpResult->error)) {
                throw new Exception($tmpResult->error_description);
            } # if

            return false;
        } # if

        $result = json_decode($tmpResult['data']);

        /*
         * throw any exception if one happens because else we get sometimes
         * failing translations which suck even more.
         */
        if (!empty($result->error)) {
            throw new Exception($result->error_description);
        } # if

        /*
         * store the translation token into the cache
         */
        $translaterToken = $result->access_token;
        $this->_cacheDao->saveTranslaterTokenCache($this->_clientId . $this->_clientSecret, // unique token
                                              time() + ($result->expires_in - 2),      // The amount of secs token is valid
                                              $translaterToken);

        return $translaterToken;
    } # getAuthToken

    /*
     * translate a single body
     */
    public function translateSingle($dstLanguage, $text) {
        /*
         * Try to obtain an translator token
         */
        $translaterToken = $this->getAuthToken();
        if ($translaterToken === false) {
            return false;
        } # if

        /*
         * Actually start translating our message
         */
        $params = "appId=" .
                    "&text=" . urlencode($text) .
                    "&to=" . urlencode($dstLanguage) .
                    "&contentType=" . urlencode("text/plain");

        $svcPrvHttp = new Services_Providers_Http(null);
        $svcPrvHttp->setBearerAuth($translaterToken);
        $httpResult = $svcPrvHttp->perform(Services_Translation_Microsoft::translateUrl . $params, false);

        /*
         * and use the result
        */
        if ($httpResult['successful']) {
            $xmlReturn = simplexml_load_string($httpResult['data']);
            return (string) $xmlReturn[0];
        } else {
            return false;
        } # else
    } # translateSingle

    /*
     * translate a single body
     */
    public function translateMultiple($dstLanguage, $list, $field) {
        /*
         * Try to obtain an translator token
         */
        $translaterToken = $this->getAuthToken();
        if ($translaterToken === false) {
            return false;
        } # if

        /*
         * Actually start translating our message
         */
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = true;

        $tar = $doc->createElement('TranslateArrayRequest');
        $tar->appendChild($doc->createElement('AppId', ''));
        $tar->appendChild($doc->createElement('From', ''));

        $texts = $doc->createElement('Texts');
        foreach($list as $v) {
            $str = $doc->createElement('string');
            $str->appendChild($doc->createTextNode($v[$field]));
            $str->setAttribute('xmlns', 'http://schemas.microsoft.com/2003/10/Serialization/Arrays');

            $texts->appendChild($str);
        } # foreach
        $tar->appendChild($texts);
        $tar->appendChild($doc->createElement('To', $dstLanguage));
        $doc->appendChild($tar);

        $svcPrvHttp = new Services_Providers_Http(null);
        $svcPrvHttp->setBearerAuth($translaterToken);
        $svcPrvHttp->setMethod('POST');
        $svcPrvHttp->setRawPostData($doc->saveXML());
        $svcPrvHttp->setContentType('text/xml');
        $httpResult = $svcPrvHttp->perform(Services_Translation_Microsoft::translateUrl, false);

        /*
         * and use the result
        */
        if ($httpResult['successful']) {
            /*
             * Loop through all results, and actually translate the
             * information
             */
            $translated = simplexml_load_string($httpResult['data']);
            $listCounter = 0;

            foreach($translated->TranslateArrayResponse as $tar) {
                $list[$listCounter][$field . '_translated'] = (string) $tar->TranslatedText;

                $listCounter++;
            } # foreach

            return $list;
        } else {
            return false;
        } # else
    } # translateMultiple


} # class Services_Translation_Microsoft
