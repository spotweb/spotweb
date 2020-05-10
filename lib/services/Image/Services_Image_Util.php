<?php

class Services_Image_Util
{
    /**
     * Returns width, height, and type of an image,
     * or 'false' when an invalid image.
     */
    public function getImageDimensions($imageString)
    {
        /*
         * PHP image functions require an actual file,
         * so we create one
         */
        $temp_file = tempnam(sys_get_temp_dir(), 'SpotWeb_');

        $fh = fopen($temp_file, 'w');
        fwrite($fh, $imageString);
        fclose($fh);

        /*
         * Now read the file, but only if its actual any use
         */
        if (filesize($temp_file) < 15) {
            unlink($temp_file);

            return false;
        } // if

        $imageInfo = getimagesize($temp_file);
        if ($imageInfo == false) {
            unlink($temp_file);

            return false;
        } // if

        /*
         * Remove the temporary file
         */
        unlink($temp_file);

        /*
         * If an image is illegal anyway, invalidate it
         */
        if (($imageInfo[0] < 0) || ($imageInfo[1] < 0)) {
            return false;
        } // if

        return ['width' => $imageInfo[0],
            'height'    => $imageInfo[1],
            'isbmp'     => ($imageInfo[2] == 6),
            'imagetype' => $imageInfo[2], ];
    }

    // getImageDimensions

    /*
     * Returns an resource with the specificied colors to be
     * used as a brush
     */
    public function colorHex($img, $hexColorString)
    {
        $r = hexdec(substr($hexColorString, 0, 2));
        $g = hexdec(substr($hexColorString, 2, 2));
        $b = hexdec(substr($hexColorString, 4, 2));

        return imagecolorallocate($img, $r, $g, $b);
    }

    // colorHex

    /*
     * Creates an empty spotweb base image
     */
    public function createDefaultSpotwebImage()
    {
        $imageFile = __DIR__.'/../../../images/spotnet.gif';
        $ttfFont = __DIR__.'/../../../images/ttf/liberation-sans/LiberationSans-Bold.ttf';
        $fontColor = 'ffffff';

        // Create image
        $img = imagecreatetruecolor(512, 320);

        // Set alphablending to on
        imagealphablending($img, true);

        // Draw a square
        imagefilledrectangle($img, 8, 8, 504, 312, $this->colorHex($img, '123456'));

        // Load and show the background image
        $bg = imagecreatefromgif($imageFile);
        list($width, $height, $type, $attr) = getimagesize($imageFile);
        imagecopymerge($img, $bg, 256 - ($width / 2), 160 - ($height / 2), 0, 0, $width, $height, 30);
        imagedestroy($bg);

        return ['resource' => $img, 'font' => $ttfFont, 'fontColor' => $fontColor];
    }

    // createDefaultSpotwebImage
} // Services_Image_Util
