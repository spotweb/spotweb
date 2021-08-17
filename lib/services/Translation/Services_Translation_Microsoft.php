<?php

class Services_Translation_Microsoft
{
    // actual translation URL
    const translateUrl = 'https://api.cognitive.microsofttranslator.com/translate?api-version=3.0';
    // oAuth url
    const authUrl = 'https://westeurope.api.cognitive.microsoft.com/sts/v1.0/issueToken';
    // subscription key headers
    const subscriptionKeyHeader = 'Ocp-Apim-Subscription-Key';

    /*
     * These id's need to be copied from the Azure market place, please
     * check the following
     *      https://azure.microsoft.com/nl-nl/services/cognitive-services/translator/#pricing
     */
    private $_subscriptionKey = '';

    protected $_settings;
    protected $_cacheDao;

    public function __construct(Services_Settings_Container $settings, Dao_Cache $cacheDao)
    {
        $this->_settings = $settings;
        $this->_cacheDao = $cacheDao;

        $this->_subscriptionKey = $settings->get('ms_translator_subscriptionkey');
    }

    // ctor

    /*
     * Returns whether the translation API is available
     */
    public function isAvailable()
    {
        return !empty($this->_subscriptionKey);
    }

    // isAvailable

    /*
     * Request an actual authentication token
     */
    private function getAuthToken()
    {
        /*
         * We default to asking our own cache for an token, because
         * if we find one, this saves us one expensive HTTP call.
         *
         * The chance we find an auth token is slim though as it expires
         * after a few seconds
         */
        $svcPrvHttp = new Services_Providers_Http(null);
        $translaterToken = $this->_cacheDao->getCachedTranslaterToken($this->_subscriptionKey);
        if ($translaterToken != false) {
            return $translaterToken['content'];
        } // if

        // Don't even try if no tokens are available
        if (!$this->isAvailable()) {
            return false;
        } // if

        $svcPrvHttp->setMethod('POST');
        $svcPrvHttp->setContentType('text/plain');
        $svcPrvHttp->setRawPostData('{body}');
        $svcPrvHttp->addHttpHeaders([self::subscriptionKeyHeader.': '.$this->_subscriptionKey]);
        $tmpResult = $svcPrvHttp->perform(self::authUrl, null);

        if (!$tmpResult['successful']) {
            return false;
        } // if

        $translaterToken = $tmpResult['data'];

        /*
         * store the translation token into the cache
         */
        $this->_cacheDao->saveTranslaterTokenCache($this->_subscriptionKey, // unique token
                                              time() + 7 * 60, // valid for 10 minutes so renew early
                                              $translaterToken);

        return $translaterToken;
    }

    // getAuthToken

    /*
     * translate a single body
     */
    public function translateSingle($dstLanguage, $text)
    {
        return false; // function not used anymore
    }

    // translateSingle

    /*
     * translate a list of body's
     */
    public function translateMultiple($dstLanguage, $list, $field)
    {
        /*
         * Try to obtain an translator token
         */
        $translaterToken = $this->getAuthToken();
        if ($translaterToken === false) {
            return false;
        } // if

        /*
         * Actually start translating the list of messages
         */
        foreach ($list as $v) {
            $obj = new stdClass();
            $obj->text = $v[$field];
            $ar[] = $obj;
        }
        $svcPrvHttp = new Services_Providers_Http(null);
        $svcPrvHttp->setBearerAuth($translaterToken);
        $svcPrvHttp->setMethod('POST');
        $svcPrvHttp->setRawPostData(json_encode($ar));
        $svcPrvHttp->setContentType('application/json; charset=UTF-8');
        $url = self::translateUrl.'&to='.$dstLanguage;
        $httpResult = $svcPrvHttp->perform($url, false);
        if ($httpResult['successful']) {
            $robj = json_decode($httpResult['data'], false);
            $listCounter = 0;
            foreach ($robj as $l) {
                $x[] = $l->translations[0]->text;
                $list[$listCounter][$field.'_translated'] = (string) $l->translations[0]->text;
                $listCounter++;
            }

            return $list;
        } else {
            return false;
        }
    }

    // translateMultiple
} // class Services_Translation_Microsoft
