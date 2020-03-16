<?php

class Services_Providers_SpotImage
{
    private $_cacheDao;
    private $_serviceHttp;
    private $_nntpSpotReading;

    /*
     * constructor
     */
    public function __construct(
        Services_Providers_Http $serviceHttp,
        Services_Nntp_SpotReading $nntpSpotReading,
        Dao_Cache $cacheDao
    ) {
        $this->_serviceHttp = $serviceHttp;
        $this->_cacheDao = $cacheDao;
        $this->_nntpSpotReading = $nntpSpotReading;
    }

    // ctor

    /*
     * Checks if we have the specified image in the cache,
     * this prevents us from reading the image from disk
     * when this is not necessary
     */
    public function hasCachedSpotImage($messageId)
    {
        return $this->_cacheDao->hasCachedSpotImage($messageId);
    }

    // hasCachedSpotImage

    /*
     * Fetches an image either from the cache, the web or a
     * newsgroup depending on where the image is available
     */
    public function fetchSpotImage($fullSpot)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);
        $return_code = 0;
        $validImage = false;
        $imageString = '';

        $data = $this->_cacheDao->getCachedSpotImage($fullSpot['messageid']);
        if ($data !== false) {
            $this->_cacheDao->updateSpotImageCacheStamp($fullSpot['messageid'], $data);

            SpotTiming::stop(__CLASS__.'::'.__FUNCTION__);

            return $data;
        } // if

        /*
         * Determine whether the spot is stored on an NNTP server or a web resource,
         * older spots are stored on an HTTP server
         */
        if (is_array($fullSpot['image'])) {
            try {
                /*
                 * Convert the list of segments to a format
                 * usable for readBinary()
                 */
                $segmentList = [];
                foreach ($fullSpot['image']['segment'] as $seg) {
                    $segmentList[] = $seg;
                } // foreach

                SpotTiming::start('fetchSpotImage::readBinary()');
                $imageString = $this->_nntpSpotReading->readBinary($segmentList, false);
                SpotTiming::stop('fetchSpotImage::readBinary()');
                $validImage = true;
            } catch (Exception $x) {
                $validImage = false;
                $return_code = $x->getCode();
            } // catch
        } elseif (empty($fullSpot['image'])) {
            /*
             * Spot did not contain an image (this is illegal?),
             * create a dummy error message
             */
            $validImage = false;
            $return_code = 901;
        } elseif (!empty($fullSpot['image'])) {
            /*
             * We don't want the HTTP layer of this code to cache the image, because
             * we want to cache / store additional information in the cache for images
             */
            $tmpPerform = $this->_serviceHttp->perform($fullSpot['image'], null, 0);
            $return_code = $tmpPerform['http_code'];
            $imageString = $tmpPerform['data'];

            if (($return_code == 200) || ($return_code == 304)) {
                $validImage = true;
            } // else
        } // elseif

        /*
         * Now validate the resource we have retrieved from the server
         */
        if ($validImage) {
            SpotTiming::start('fetchSpotImage::getImageDimensions()');
            $svc_ImageUtil = new Services_Image_Util();
            $dimensions = $svc_ImageUtil->getImageDimensions($imageString);
            SpotTiming::stop('fetchSpotImage::getImageDimensions()', []);

            /*
             * If this is not a valid image, create a dummy error code,
             * else we save it in the cache
             */
            if ($dimensions !== false) {
                /*
                 * If the current image is an BMP file, convert it to
                 * JPEG
                 */
                if ($dimensions['isbmp']) {
                    SpotTiming::start('fetchSpotImage::convertToBmp()');
                    $svc_ImageBmpConverter = new Services_Image_BmpConverter();
                    $imageString = $svc_ImageBmpConverter->convertBmpImageStringToJpeg($imageString, $dimensions);

                    $dimensions = $svc_ImageUtil->getImageDimensions($imageString);
                    $validImage = ($dimensions !== false);
                    SpotTiming::stop('fetchSpotImage::convertToBmp()', serialize($validImage));
                } // if

                /*
                 * and store the file in the cache
                 */
                if ($validImage) {
                    /*
                     * This is an actual SpotImage
                     */
                    $dimensions['is_tempimage'] = false;

                    SpotTiming::start('fetchSpotImage::savingToCache()');
                    if (!$this->_cacheDao->saveSpotImageCache(
                        $fullSpot['messageid'],
                        $dimensions,
                        $imageString,
                        false
                    )) {
                        $validImage = false;
                        $return_code = 997;
                    } // if
                    SpotTiming::stop('fetchSpotImage::savingToCache()', serialize($validImage));
                } // if
            } else {
                $validImage = false;
                $return_code = 998;
            } // if
        } // if

        /*
         * Did we get a return code other than 200 OK and
         * other than 304 (Resource Not modified), create
         * an error code image
         */
        if (!$validImage) {
            SpotTiming::start('fetchSpotImage::createErrorImage()');
            $svc_ImageError = new Services_Image_Error();
            $errorImage = $svc_ImageError->createErrorImage($return_code);
            SpotTiming::stop('fetchSpotImage::createErrorImage()');

            $imageString = $errorImage['content'];
            $dimensions = $errorImage['metadata'];

            /*
             * Store a copy of the error image so we don't request
             * the same image over and over.
             */
            $this->_cacheDao->saveSpotImageCache(
                $fullSpot['messageid'],
                $dimensions,
                $imageString,
                true
            );
        } // if

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$fullSpot]);

        return ['content' => $imageString,
            'metadata'    => $dimensions, ];
    }

    // fetchSpotImage
} // Services_Providers_SpotImage
