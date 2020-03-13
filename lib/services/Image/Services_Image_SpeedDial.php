<?php

class Services_Image_SpeedDial
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

    public function createSpeedDial($totalSpots, $newSpots, $lastUpdate)
    {
        $img = $this->_svcImageUtil->createDefaultSpotwebImage();
        $fontSize = 24;
        $angle = 0;

        $text = sprintf(_('Total spots: %d'), $totalSpots);
        $bbox = imagettfbbox($fontSize, $angle, $img['font'], $text);
        $width = abs($bbox[2]);
        imagettftext($img['resource'], $fontSize, $angle, 256 - ($width / 2), 50, $this->_svcImageUtil->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

        if (!$newSpots) {
            $newSpots = 0;
        }
        $text = sprintf(_('Total new spots: %d'), $newSpots);
        $bbox = imagettfbbox($fontSize, $angle, $img['font'], $text);
        $width = abs($bbox[2]);
        imagettftext($img['resource'], $fontSize, $angle, 256 - ($width / 2), 90, $this->_svcImageUtil->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

        $text = _('Last update:');
        $bbox = imagettfbbox($fontSize, $angle, $img['font'], $text);
        $width = abs($bbox[2]);
        imagettftext($img['resource'], $fontSize, $angle, 256 - ($width / 2), 230 + $fontSize, $this->_svcImageUtil->colorHex($img['resource'], $img['fontColor']), $img['font'], $text);

        $bbox = imagettfbbox($fontSize, $angle, $img['font'], $lastUpdate);
        $width = abs($bbox[2]);
        imagettftext($img['resource'], $fontSize, $angle, 256 - ($width / 2), 270 + $fontSize, $this->_svcImageUtil->colorHex($img['resource'], $img['fontColor']), $img['font'], $lastUpdate);

        ob_start();
        imagejpeg($img['resource']);
        $imageString = ob_get_clean();
        imagedestroy($img['resource']);

        $dimensions = $this->_svcImageUtil->getImageDimensions($imageString);

        return ['metadata' => $dimensions, 'ttl' => time() + (60 * 60), 'content' => $imageString];
    }

    // createSpeedDial
} // Services_Image_SpeedDial
