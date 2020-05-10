<?php

class Services_Image_Error
{
    private $_svcImageUtil;

    /*
     * Constructor
     */
    public function __construct()
    {
        $this->_svcImageUtil = new Services_Image_Util();
    }

    // ctor

    /*
     * Creates an error image using the specified
     * error code
     */
    public function createErrorImage($errcode)
    {
        $img = $this->_svcImageUtil->createDefaultSpotwebImage();
        $fontSize = 30;
        $angle = 0;

        // Headertext
        $text = ($errcode < 900) ? _('ERROR').' '.$errcode : _('ERROR');
        $bbox = imagettfbbox($fontSize, $angle, $img['font'], $text);
        $txtwidth = abs($bbox[2]);
        imagettftext($img['resource'], $fontSize, $angle, 256 - ($txtwidth / 2), 50, $this->_svcImageUtil->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

        // error info
        switch ($errcode) {
            case 5: 	$text = _('Access denied'); break;
            case 200:	$text = _('Remote host sent bad data'); break;
            case 400:	$text = _('Bad request'); break;
            case 403:	$text = _('Permission denied from remote host'); break;
            case 404:	$text = _('File not found'); break;
            case 430:	$text = _('Article not found'); break;
            case 700:	$text = _('No response from remote host'); break;
            case 900:	$text = _('XML parse error'); break;
            case 901:	$text = _('No image provided'); break;
            case 997:	$text = _('Unable to write to cachepath'); break;
            default:	$text = _('Unknown error');
        } // switch

        $fontSize = 20;
        $bbox = imagettfbbox($fontSize, $angle, $img['font'], $text);
        $txtwidth = abs($bbox[2]);
        imagettftext($img['resource'], $fontSize, $angle, 256 - ($txtwidth / 2), 300, $this->_svcImageUtil->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

        ob_start();
        imagejpeg($img['resource']);
        $imageString = ob_get_clean();
        imagedestroy($img['resource']);

        $dimensions = $this->_svcImageUtil->getImageDimensions($imageString);

        return ['metadata' => $dimensions, 'ttl' => time() + (7 * 24 * 60 * 60), 'content' => $imageString];
    }

    // createErrorImage
} // Services_Image_Error
