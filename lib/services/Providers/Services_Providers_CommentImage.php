<?php

class Services_Providers_CommentImage
{
    private $_serviceHttp;
    private $_svc_ImageUtil;

    /*
     * constructor
     */
    public function __construct(Services_Providers_Http $serviceHttp)
    {
        $this->_serviceHttp = $serviceHttp;
        $this->_svc_ImageUtil = new Services_Image_Util();
    }

    // ctor

    /*
     * Returns an the data of an gravatar comments url
     */
    public function fetchGravatarImage($imageParams)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        $imgDefaults = ['md5' => false,
            'size'            => 80,
            'default'         => 'identicon',
            'rating'          => 'g', ];
        $imgSettings = array_merge($imgDefaults, $imageParams);

        if ($imgSettings['size'] < 1 || $imgSettings['size'] > 512) {
            $imgSettings['size'] = $imgDefaults['size'];
        } // if

        if (!in_array($imgSettings['default'], ['identicon', 'mm', 'monsterid', 'retro', 'wavatar'])) {
            $imgSettings['default'] = $imgDefaults['default'];
        } // if

        if (!in_array($imgSettings['rating'], ['g', 'pg', 'r', 'x'])) {
            $imgSettings['rating'] = $imgDefaults['rating'];
        } // if

        $data = $this->getAvatarImage($imgSettings['md5'], $imgSettings['size'], $imgSettings['default'], $imgSettings['rating']);

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, []);

        return $data;
    }

    // fetchGravatarImage

    /*
     * Returns an Spotweb commenters' avatar image
     */
    private function getAvatarImage($md5, $size, $default, $rating)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);
        $url = 'http://www.gravatar.com/avatar/'.$md5.'?s='.$size.'&d='.$default.'&r='.$rating;

        list($return_code, $data) = $this->_serviceHttp->performCachedGet($url, true, 60 * 60);

        $dimensions = $this->_svc_ImageUtil->getImageDimensions($data);

        $data = ['content' => $data];
        $data['metadata'] = $dimensions;
        $data['ttl'] = (24 * 7 * 60 * 60);
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$md5, $size, $default, $rating]);

        return $data;
    }

    // getAvatarImage
} // Services_Providers_SpotImage
