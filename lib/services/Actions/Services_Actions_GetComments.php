<?php

class Services_Actions_GetComments
{
    /**
     * Define what we think is the native language of the Spots, if we get
     * asked to provide comments in our 'native' language, we just skip translation.
     */
    const nativeLanguage = 'nl';

    /**
     * @var Services_Settings_Base
     */
    private $_settings;
    /**
     * @var Dao_Factory
     */
    private $_daoFactory;
    /**
     * @var Dao_Cache
     */
    private $_cacheDao;
    /**
     * @var SpotSecurity
     */
    private $_spotSec;
    /**
     * @var Services_Translation_Microsoft
     */
    private $_svcTranslate;

    public function __construct(Services_Settings_Container $settings, Dao_Factory $daoFactory, SpotSecurity $spotSec)
    {
        $this->_settings = $settings;
        $this->_daoFactory = $daoFactory;
        $this->_spotSec = $spotSec;
        $this->_cacheDao = $daoFactory->getCacheDao();

        $this->_svcTranslate = new Services_Translation_Microsoft($settings, $daoFactory->getCacheDao());
    }

    // ctor

    /*
     * Returns the spot comments, and translates them when asked to,
     * tries to minimize the amount of requests to the translator API
     */
    public function getSpotComments($msgId, $prevMsgids, $userId, $start, $length, $language)
    {
        // Check users' permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_comments, '');

        $svcNntpSpotReading = new Services_Nntp_SpotReading(Services_Nntp_EnginePool::pool($this->_settings, 'hdr'));
        $svcProvComments = new Services_Providers_Comments($this->_daoFactory->getCommentDao(), $svcNntpSpotReading);
        $tryTranslate = (self::nativeLanguage !== $language);

        /*
         * Basically we are retrieving the comments from the database, for them to be translated
         * if necessary
         */
        $comments = $svcProvComments->fetchSpotComments($msgId, $prevMsgids, $userId, $start, $length);
        if (!$tryTranslate) {
            return $comments;
        } // if

        /*
         * In our cache, we store an key => value pair with the original string and
         * the translation, so we can do very quick lookups.
         */
        $toBeTranslated = [];
        $translated = $this->_cacheDao->getCachedTranslatedComments($msgId, $language);
        if ($translated === false) {
            $translated = [];
        } // if

        foreach ($comments as &$comment) {
            $tmpBody = $comment['body'];

            if (isset($translated[$tmpBody])) {
                $comment['body_translated'] = $translated[$tmpBody];
            } else {
                $toBeTranslated[] = $comment;
            } // else
        } // foreach

        /*
         * Actually translate our list of comments, and merge
         * them with the actual comments
         */
        if (!empty($toBeTranslated)) {
            $svcTranslate = new Services_Translation_Microsoft($this->_settings, $this->_cacheDao);
            if (!$svcTranslate->isAvailable()) {
                return $comments;
            } // if
            $translations = $svcTranslate->translateMultiple($language, $toBeTranslated, 'body');

            /*
             * copy the translations into the cache
             */
            if (!empty($translations)) {
                foreach ($translations as $v) {
                    $tmpBody = $v['body'];
                    $translated[$tmpBody] = $v['body_translated'];
                } // foreach

                /*
                 * Convert the comments once again
                 */
                foreach ($comments as &$comment) {
                    $tmpBody = $comment['body'];
                    if (isset($translated[$tmpBody])) {
                        $comment['body_translated'] = $translated[$tmpBody];
                    } // else
                } // foreach
            } // if

            /*
             * and save the translated bodies into the cache
             */
            $this->_cacheDao->saveTranslatedCommentCache($msgId, $language, $translated);
        } // if

        return $comments;
    }

    // getSpotComments
} // Services_Actions_GetComments
