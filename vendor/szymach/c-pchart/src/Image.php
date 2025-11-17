<?php

namespace CpChart;

use RuntimeException;

use const IMAGE_MAP_DELIMITER;
use const IMAGE_MAP_STORAGE_FILE;
use const IMAGE_MAP_STORAGE_SESSION;
use const TEXT_ALIGN_BOTTOMLEFT;
use const TEXT_ALIGN_BOTTOMMIDDLE;
use const TEXT_ALIGN_BOTTOMRIGHT;
use const TEXT_ALIGN_MIDDLELEFT;
use const TEXT_ALIGN_MIDDLEMIDDLE;
use const TEXT_ALIGN_MIDDLERIGHT;
use const TEXT_ALIGN_TOPLEFT;
use const TEXT_ALIGN_TOPMIDDLE;
use const TEXT_ALIGN_TOPRIGHT;
use const VOID;

class Image extends Draw
{
    /**
     * @var string
     */
    public $ImageMapFileName;

    /**
     * @var string
     */
    public $ImageMapStorageFolder;

    /**
     * @param int<1, max> $XSize
     * @param int<1, max> $YSize
     * @param Data|null $DataSet
     * @param bool $TransparentBackground
     */
    public function __construct(
        $XSize,
        $YSize,
        ?Data $DataSet = null,
        $TransparentBackground = false
    ) {
        parent::__construct();

        $this->TransparentBackground = $TransparentBackground;

        $this->DataSet = null !== $DataSet ? $DataSet : new Data();
        $this->XSize = $XSize;
        $this->YSize = $YSize;
        $this->Picture = imagecreatetruecolor($XSize, $YSize);

        if ($this->TransparentBackground) {
            imagealphablending($this->Picture, false);
            /** @var int<0, max> $fillColor */
            $fillColor = imagecolorallocatealpha($this->Picture, 255, 255, 255, 127);
            imagefilledrectangle(
                $this->Picture,
                0,
                0,
                $XSize,
                $YSize,
                $fillColor
            );
            imagealphablending($this->Picture, true);
            imagesavealpha($this->Picture, true);
        } else {
            $C_White = $this->AllocateColor($this->Picture, 255, 255, 255);
            imagefilledrectangle($this->Picture, 0, 0, $XSize, $YSize, $C_White);
        }
    }

    public function __toString()
    {
        if ($this->TransparentBackground) {
            imagealphablending($this->Picture, false);
            imagesavealpha($this->Picture, true);
        }

        ob_start();
        imagepng($this->Picture);

        $return = ob_get_clean();
        if ($return === false) {
            throw new RuntimeException('Unable to cast the image to string');
        }

        return $return;
    }

    /**
     * Enable / disable and set shadow properties
     *
     * @param bool $Enabled
     * @param array{ X?: int, Y?: int, R?: int, G?: int, B?: int, Alpha?: int } $Format
     * @return void
     */
    public function setShadow($Enabled = true, array $Format = [])
    {
        $this->Shadow = (bool) $Enabled;
        $this->ShadowX = $Format["X"] ?? 2;
        $this->ShadowY = $Format["Y"] ?? 2;
        $this->ShadowR = $Format["R"] ?? 0;
        $this->ShadowG = $Format["G"] ?? 0;
        $this->ShadowB = $Format["B"] ?? 0;
        $this->Shadowa = $Format["Alpha"] ?? 10;
    }

    /**
     * Set the graph area position
     * @param int $X1
     * @param int $Y1
     * @param int $X2
     * @param int $Y2
     * @return int|null
     */
    public function setGraphArea($X1, $Y1, $X2, $Y2)
    {
        if ($X2 < $X1 || $X1 == $X2 || $Y2 < $Y1 || $Y1 == $Y2) {
            return -1;
        }

        $this->GraphAreaX1 = $X1;
        $this->DataSet->Data["GraphArea"]["X1"] = $X1;
        $this->GraphAreaY1 = $Y1;
        $this->DataSet->Data["GraphArea"]["Y1"] = $Y1;
        $this->GraphAreaX2 = $X2;
        $this->DataSet->Data["GraphArea"]["X2"] = $X2;
        $this->GraphAreaY2 = $Y2;
        $this->DataSet->Data["GraphArea"]["Y2"] = $Y2;

        return null;
    }

    /**
     * Return the width of the picture
     * @return int
     */
    public function getWidth()
    {
        return $this->XSize;
    }

    /**
     * Return the heigth of the picture
     * @return int
     */
    public function getHeight()
    {
        return $this->YSize;
    }

    /**
     * Render the picture to a file
     * @param string $FileName
     * @return void
     */
    public function render($FileName)
    {
        if ($this->TransparentBackground) {
            imagealphablending($this->Picture, false);
            imagesavealpha($this->Picture, true);
        }
        imagepng($this->Picture, $FileName);
    }

    /**
     * @return non-empty-string
     */
    public function toDataURI()
    {
        return 'data:image/png;base64,' . base64_encode($this->__toString());
    }

    /**
     * @FIXME better way to do this?
     *
     * Render the picture to a web browser stream
     * @param bool $BrowserExpire
     * @return void
     */
    public function stroke($BrowserExpire = false)
    {
        if ($this->TransparentBackground) {
            imagealphablending($this->Picture, false);
            imagesavealpha($this->Picture, true);
        }

        if ($BrowserExpire) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Cache-Control: no-cache");
            header("Pragma: no-cache");
        }

        header('Content-type: image/png');
        imagepng($this->Picture);
    }

    /**
     * Automatic output method based on the calling interface
     * @param string $FileName
     * @return void
     */
    public function autoOutput($FileName = "output.png")
    {
        if (php_sapi_name() == "cli") {
            $this->Render($FileName);
        } else {
            $this->Stroke();
        }
    }

    /**
     * Return the length between two points
     * @param int $X1
     * @param int $Y1
     * @param int $X2
     * @param int $Y2
     * @return float
     */
    public function getLength($X1, $Y1, $X2, $Y2)
    {
        return sqrt(
            pow(max($X1, $X2) - min($X1, $X2), 2) + pow(max($Y1, $Y2) - min($Y1, $Y2), 2)
        );
    }

    /**
     * Return the orientation of a line
     * @param int $X1
     * @param int $Y1
     * @param int $X2
     * @param int $Y2
     * @return float|int
     */
    public function getAngle($X1, $Y1, $X2, $Y2)
    {
        $Opposite = $Y2 - $Y1;
        $Adjacent = $X2 - $X1;
        $Angle = rad2deg(atan2($Opposite, $Adjacent));

        return ($Angle > 0) ? $Angle : (360 - abs($Angle));
    }

    /**
     * Return the surrounding box of text area
     * @param int $X
     * @param int $Y
     * @param string $FontName
     * @param int $FontSize
     * @param int $Angle
     * @param string $Text
     * @return array<int<0, max>, array{ X: float, Y: float }>
     */
    public function getTextBox($X, $Y, $FontName, $FontSize, $Angle, $Text)
    {
        $coords = imagettfbbox($FontSize, 0, $this->loadFont($FontName, 'fonts'), $Text);
        if ($coords === false) {
            throw new RuntimeException('Unable to create text box coordinates');
        }

        $a = deg2rad($Angle);
        $ca = cos($a);
        $sa = sin($a);

        $initalPositions = [];
        for ($i = 0; $i < 7; $i += 2) {
            /** @var int<0, 3> $index */
            $index = $i / 2;
            $initalPositions[$index] = [
                "X" => $X + round($coords[$i] * $ca + $coords[$i + 1] * $sa),
                "Y" => $Y + round($coords[$i + 1] * $ca - $coords[$i] * $sa),
            ];
        }

        // Split the assignments here to make PHPStan happy. In theory modifying
        // the array you are pulling values from could break the logic, even though
        // chances of that were zero, it still complained.

        $realPositions = $initalPositions;

        $realPositions[TEXT_ALIGN_BOTTOMLEFT] = [
            "X" => $initalPositions[0]["X"],
            "Y" => $initalPositions[0]["Y"],
        ];

        $realPositions[TEXT_ALIGN_BOTTOMRIGHT] = [
            "X" => $initalPositions[1]["X"],
            "Y" => $initalPositions[1]["Y"],
        ];

        $realPositions[TEXT_ALIGN_TOPLEFT] = [
            "X" => $initalPositions[3]["X"],
            "Y" => $initalPositions[3]["Y"],
        ];

        $realPositions[TEXT_ALIGN_TOPRIGHT] = [
            "X" => $initalPositions[2]["X"],
            "Y" => $initalPositions[2]["Y"],
        ];

        $realPositions[TEXT_ALIGN_BOTTOMMIDDLE] = [
            "X" => ($initalPositions[1]["X"] - $initalPositions[0]["X"]) / 2 + $initalPositions[0]["X"],
            "Y" => ($initalPositions[0]["Y"] - $initalPositions[1]["Y"]) / 2 + $initalPositions[1]["Y"],
        ];

        $realPositions[TEXT_ALIGN_TOPMIDDLE] = [
            "X" => ($initalPositions[2]["X"] - $initalPositions[3]["X"]) / 2 + $initalPositions[3]["X"],
            "Y" => ($initalPositions[3]["Y"] - $initalPositions[2]["Y"]) / 2 + $initalPositions[2]["Y"],
        ];

        $realPositions[TEXT_ALIGN_MIDDLELEFT] = [
            "X" => ($initalPositions[0]["X"] - $initalPositions[3]["X"]) / 2 + $initalPositions[3]["X"],
            "Y" => ($initalPositions[0]["Y"] - $initalPositions[3]["Y"]) / 2 + $initalPositions[3]["Y"]
        ];

        $realPositions[TEXT_ALIGN_MIDDLERIGHT] = [
            "X" => ($initalPositions[1]["X"] - $initalPositions[2]["X"]) / 2 + $initalPositions[2]["X"],
            "Y" => ($initalPositions[1]["Y"] - $initalPositions[2]["Y"]) / 2 + $initalPositions[2]["Y"],
        ];

        $realPositions[TEXT_ALIGN_MIDDLEMIDDLE] = [
            "X" => ($initalPositions[1]["X"] - $initalPositions[3]["X"]) / 2 + $initalPositions[3]["X"],
            "Y" => ($initalPositions[0]["Y"] - $initalPositions[2]["Y"]) / 2 + $initalPositions[2]["Y"],
        ];


        return $realPositions;
    }

    /**
     * @param array{
     *  R?: int,
     *  G?: int,
     *  B?: int,
     *  Alpha?: int,
     *  FontName?: string,
     *  FontSize?: int
     * } $Format
     * @return void
     */
    public function setFontProperties($Format = [])
    {
        $R = $Format["R"] ?? -1;
        $G = $Format["G"] ?? -1;
        $B = $Format["B"] ?? -1;
        $Alpha = $Format["Alpha"] ?? 100;
        $FontName = $Format["FontName"] ?? null;
        $FontSize = $Format["FontSize"] ?? null;

        if ($R != -1) {
            $this->FontColorR = $R;
        }
        if ($G != -1) {
            $this->FontColorG = $G;
        }
        if ($B != -1) {
            $this->FontColorB = $B;
        }
        if ($Alpha != null) {
            $this->FontColorA = $Alpha;
        }

        if ($FontName != null) {
            $this->FontName = $this->loadFont($FontName, 'fonts');
        }
        if ($FontSize != null) {
            $this->FontSize = $FontSize;
        }
    }

    /**
     * Returns the 1st decimal values (used to correct AA bugs)
     * @param string $Value
     * @return string|int
     */
    public function getFirstDecimal($Value)
    {
        $Values = preg_split("/\./", $Value);
        return isset($Values[1]) ? substr($Values[1], 0, 1) : 0;
    }

    /**
     * Attach a dataset to your pChart Object
     * @return void
     */
    public function setDataSet(Data $DataSet)
    {
        $this->DataSet = $DataSet;
    }

    /**
     * Print attached dataset contents to STDOUT
     * @return void
     */
    public function printDataSet()
    {
        print_r($this->DataSet);
    }

    /**
     * Initialise the image map methods
     * @param string $Name
     * @param int $StorageMode
     * @param string $UniqueID
     * @param string $StorageFolder
     * @return void
     */
    public function initialiseImageMap(
        $Name = "pChart",
        $StorageMode = IMAGE_MAP_STORAGE_SESSION,
        $UniqueID = "imageMap",
        $StorageFolder = "tmp"
    ) {
        $this->ImageMapIndex = $Name;
        $this->ImageMapStorageMode = $StorageMode;

        if ($StorageMode == IMAGE_MAP_STORAGE_SESSION) {
            // @FIXME looks like something potentially problematic
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION[$this->ImageMapIndex] = null;
        } elseif ($StorageMode == IMAGE_MAP_STORAGE_FILE) {
            $this->ImageMapFileName = $UniqueID;
            $this->ImageMapStorageFolder = $StorageFolder;

            $path = sprintf("%s/%s.map", $StorageFolder, $UniqueID);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Add a zone to the image map
     *
     * @param string $Type
     * @param string $Plots
     * @param string|null $Color
     * @param string $Title
     * @param string $Message
     * @param bool $HTMLEncode
     * @return void
     */
    public function addToImageMap(
        $Type,
        $Plots,
        $Color = null,
        $Title = null,
        $Message = null,
        $HTMLEncode = false
    ) {
        if ($this->ImageMapStorageMode == null) {
            $this->initialiseImageMap();
        }

        /* Encode the characters in the imagemap in HTML standards */
        $Title = htmlentities(
            str_replace("&#8364;", "\u20AC", $Title),
            ENT_QUOTES,
            "ISO-8859-15"
        );
        if ($HTMLEncode) {
            $Message = str_replace(
                "&gt;",
                ">",
                str_replace(
                    "&lt;",
                    "<",
                    htmlentities($Message, ENT_QUOTES, "ISO-8859-15")
                )
            );
        }

        if ($this->ImageMapStorageMode == IMAGE_MAP_STORAGE_SESSION) {
            if (!isset($_SESSION)) {
                $this->initialiseImageMap();
            }
            $_SESSION[$this->ImageMapIndex][] = [$Type, $Plots, $Color, $Title, $Message];
        } elseif ($this->ImageMapStorageMode == IMAGE_MAP_STORAGE_FILE) {
            $Handle = $this->openImageStorageFileHandle('a');
            if (is_resource($Handle) === false) {
                throw new RuntimeException('Unable to write to image storage file');
            }

            fwrite(
                $Handle,
                sprintf(
                    "%s%s%s%s%s%s%s%s%s\r\n",
                    $Type,
                    IMAGE_MAP_DELIMITER,
                    $Plots,
                    IMAGE_MAP_DELIMITER,
                    $Color,
                    IMAGE_MAP_DELIMITER,
                    $Title,
                    IMAGE_MAP_DELIMITER,
                    $Message
                )
            );
            fclose($Handle);
        }
    }

    /**
     * Remove VOID values from an imagemap custom values array
     * @param string $SerieName
     * @param array<int|float|numeric-string, int|float|numeric-string> $Values
     * @return list<float|int|numeric-string>|int
     */
    public function removeVOIDFromArray($SerieName, array $Values)
    {
        if (!isset($this->DataSet->Data["Series"][$SerieName])) {
            return -1;
        }

        $Result = [];
        foreach ($this->DataSet->Data["Series"][$SerieName]["Data"] as $Key => $Value) {
            if ($Value != VOID && isset($Values[$Key])) {
                $Result[] = $Values[$Key];
            }
        }

        return $Result;
    }

    /**
     * Replace the title of one image map serie
     * @param string $OldTitle
     * @param array<int|float|numeric-string, int|float|numeric-string>|string $NewTitle
     * @return null|int
     */
    public function replaceImageMapTitle($OldTitle, $NewTitle)
    {
        if ($this->ImageMapStorageMode == null) {
            return -1;
        }

        if (is_array($NewTitle)) {
            $NewTitle = $this->removeVOIDFromArray($OldTitle, $NewTitle);
        }

        if ($this->ImageMapStorageMode == IMAGE_MAP_STORAGE_SESSION) {
            if (!isset($_SESSION)) {
                return -1;
            }
            if (is_array($NewTitle)) {
                $ID = 0;
                foreach ($_SESSION[$this->ImageMapIndex] as $Key => $Settings) {
                    if ($Settings[3] == $OldTitle && isset($NewTitle[$ID])) {
                        $_SESSION[$this->ImageMapIndex][$Key][3] = $NewTitle[$ID];
                        $ID++;
                    }
                }
            } else {
                foreach ($_SESSION[$this->ImageMapIndex] as $Key => $Settings) {
                    if ($Settings[3] == $OldTitle) {
                        $_SESSION[$this->ImageMapIndex][$Key][3] = $NewTitle;
                    }
                }
            }
        } elseif ($this->ImageMapStorageMode == IMAGE_MAP_STORAGE_FILE) {
            $TempArray = [];
            $Handle = $this->openImageStorageFileHandle();
            if ($Handle) {
                while (($Buffer = fgets($Handle, 4096)) !== false) {
                    $Fields = preg_split(
                        sprintf("/%s/", IMAGE_MAP_DELIMITER),
                        str_replace([chr(10), chr(13)], "", $Buffer)
                    );

                    if ($Fields === false) {
                        throw new RuntimeException('Unable to parse image storage file buffer');
                    }

                    $TempArray[] = [$Fields[0], $Fields[1], $Fields[2], $Fields[3], $Fields[4]];
                }
                fclose($Handle);

                if (is_array($NewTitle)) {
                    $ID = 0;
                    foreach ($TempArray as $Key => $Settings) {
                        if ($Settings[3] == $OldTitle && isset($NewTitle[$ID])) {
                            $TempArray[$Key][3] = $NewTitle[$ID];
                            $ID++;
                        }
                    }
                } else {
                    foreach ($TempArray as $Key => $Settings) {
                        if ($Settings[3] == $OldTitle) {
                            $TempArray[$Key][3] = $NewTitle;
                        }
                    }
                }

                $Handle = $this->openImageStorageFileHandle("w");
                foreach ($TempArray as $Key => $Settings) {
                    fwrite(
                        $Handle,
                        sprintf(
                            "%s%s%s%s%s%s%s%s%s\r\n",
                            $Settings[0],
                            IMAGE_MAP_DELIMITER,
                            $Settings[1],
                            IMAGE_MAP_DELIMITER,
                            $Settings[2],
                            IMAGE_MAP_DELIMITER,
                            $Settings[3],
                            IMAGE_MAP_DELIMITER,
                            $Settings[4]
                        )
                    );
                }
                fclose($Handle);
            }
        }

        return null;
    }

    /**
     * Replace the values of the image map contents
     * @param string $Title
     * @param list<int|float|numeric-string> $Values
     * @return null|int
     */
    public function replaceImageMapValues($Title, array $Values)
    {
        if ($this->ImageMapStorageMode == null) {
            return -1;
        }

        $Values = $this->removeVOIDFromArray($Title, $Values);
        $ID = 0;
        if ($this->ImageMapStorageMode == IMAGE_MAP_STORAGE_SESSION) {
            if (!isset($_SESSION)) {
                return -1;
            }
            foreach ($_SESSION[$this->ImageMapIndex] as $Key => $Settings) {
                if ($Settings[3] == $Title) {
                    if (isset($Values[$ID])) {
                        $_SESSION[$this->ImageMapIndex][$Key][4] = $Values[$ID];
                    }
                    $ID++;
                }
            }
        } elseif ($this->ImageMapStorageMode == IMAGE_MAP_STORAGE_FILE) {
            $TempArray = [];
            $Handle = $this->openImageStorageFileHandle();
            if ($Handle) {
                while (($Buffer = fgets($Handle, 4096)) !== false) {
                    $Fields = preg_split(
                        "/" . IMAGE_MAP_DELIMITER . "/",
                        str_replace([chr(10), chr(13)], "", $Buffer)
                    );

                    if ($Fields === false) {
                        throw new RuntimeException('Unable to parse image storage file buffer');
                    }

                    $TempArray[] = [$Fields[0], $Fields[1], $Fields[2], $Fields[3], $Fields[4]];
                }
                fclose($Handle);

                foreach ($TempArray as $Key => $Settings) {
                    if ($Settings[3] == $Title) {
                        if (isset($Values[$ID])) {
                            $TempArray[$Key][4] = $Values[$ID];
                        }
                        $ID++;
                    }
                }

                $Handle = $this->openImageStorageFileHandle("w");
                foreach ($TempArray as $Key => $Settings) {
                    fwrite(
                        $Handle,
                        sprintf(
                            "%s%s%s%s%s%s%s%s%s\r\n",
                            $Settings[0],
                            IMAGE_MAP_DELIMITER,
                            $Settings[1],
                            IMAGE_MAP_DELIMITER,
                            $Settings[2],
                            IMAGE_MAP_DELIMITER,
                            $Settings[3],
                            IMAGE_MAP_DELIMITER,
                            $Settings[4]
                        )
                    );
                }
                fclose($Handle);
            }
        }

        return null;
    }

    /**
     * Dump the image map
     * @param string $Name
     * @param int $StorageMode
     * @param string $UniqueID
     * @param string $StorageFolder
     * @return void
     */
    public function dumpImageMap(
        $Name = "pChart",
        $StorageMode = IMAGE_MAP_STORAGE_SESSION,
        $UniqueID = "imageMap",
        $StorageFolder = "tmp"
    ) {
        $this->ImageMapIndex = $Name;
        $this->ImageMapStorageMode = $StorageMode;

        if ($this->ImageMapStorageMode == IMAGE_MAP_STORAGE_SESSION) {
            if (!isset($_SESSION)) {
                session_start();
            }
            if ($_SESSION[$Name] != null) {
                foreach ($_SESSION[$Name] as $Key => $Params) {
                    echo $Params[0] . IMAGE_MAP_DELIMITER . $Params[1]
                    . IMAGE_MAP_DELIMITER . $Params[2] . IMAGE_MAP_DELIMITER
                    . $Params[3] . IMAGE_MAP_DELIMITER . $Params[4] . "\r\n";
                }
            }
        } elseif ($this->ImageMapStorageMode == IMAGE_MAP_STORAGE_FILE) {
            $storageFileMapFilePath = "$StorageFolder/$UniqueID.map";
            if (file_exists($storageFileMapFilePath)) {
                $Handle = @fopen($storageFileMapFilePath, "r");
                if ($Handle !== false) {
                    while (($Buffer = fgets($Handle, 4096)) !== false) {
                        echo $Buffer;
                    }
                    fclose($Handle);
                }

                if ($this->ImageMapAutoDelete) {
                    unlink($storageFileMapFilePath);
                }
            }
        }
    }

    /**
     * Return the HTML converted color from the RGB composite values
     * @param int $R
     * @param int $G
     * @param int $B
     * @return string
     */
    public function toHTMLColor($R, $G, $B)
    {
        $R = intval($R);
        $G = intval($G);
        $B = intval($B);
        $R = dechex($R < 0 ? 0 : ($R > 255 ? 255 : $R));
        $G = dechex($G < 0 ? 0 : ($G > 255 ? 255 : $G));
        $B = dechex($B < 0 ? 0 : ($B > 255 ? 255 : $B));
        $Color = "#" . (strlen($R) < 2 ? '0' : '') . $R;
        $Color .= (strlen($G) < 2 ? '0' : '') . $G;
        $Color .= (strlen($B) < 2 ? '0' : '') . $B;

        return $Color;
    }

    /**
     * Reverse an array of points
     * @param array<int, int|float|numeric-string> $Plots
     * @return list<int|float|numeric-string>
     */
    public function reversePlots(array $Plots)
    {
        $Result = [];
        for ($i = count($Plots) - 2; $i >= 0; $i = $i - 2) {
            $Result[] = $Plots[$i];
            $Result[] = $Plots[$i + 1];
        }

        return $Result;
    }

    /**
     * Mirror Effect
     *
     * @param int $X
     * @param int $Y
     * @param int $Width
     * @param int $Height
     * @param array{ StartAlpha?: int, EndAlpha?: int } $Format
     * @return void
     */
    public function drawAreaMirror($X, $Y, $Width, $Height, array $Format = [])
    {
        $StartAlpha = $Format["StartAlpha"] ?? 80;
        $EndAlpha = isset($Format["EndAlpha"]) ? $Format["EndAlpha"] : 0;

        $AlphaStep = ($StartAlpha - $EndAlpha) / $Height;

        $Picture = imagecreatetruecolor($this->XSize, $this->YSize);
        imagecopy($Picture, $this->Picture, 0, 0, 0, 0, $this->XSize, $this->YSize);

        for ($i = 1; $i <= $Height; $i++) {
            if ($Y + ($i - 1) < $this->YSize && $Y - $i > 0) {
                imagecopymerge(
                    $Picture,
                    $this->Picture,
                    $X,
                    $Y + ($i - 1),
                    $X,
                    $Y - $i,
                    $Width,
                    1,
                    $StartAlpha - $AlphaStep * $i
                );
            }
        }

        imagecopy($this->Picture, $Picture, 0, 0, 0, 0, $this->XSize, $this->YSize);
    }

    /**
     * @param string $mode
     */
    private function openImageStorageFileHandle($mode = "r"): mixed
    {
        return @fopen(
            "$this->ImageMapStorageFolder/$this->ImageMapFileName.map",
            $mode
        );
    }
}
