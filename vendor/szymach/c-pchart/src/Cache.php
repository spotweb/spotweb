<?php

namespace CpChart;

use RuntimeException;

/**
 * @phpstan-type SettingsArray array{
 *  0: numeric-string,
 *  1: numeric-string,
 *  2: numeric-string,
 *  3: numeric-string,
 *  4: numeric-string
 * }
 */
class Cache
{
    /**
     * @var string
     */
    public $CacheFolder;

    /**
     * @var string
     */
    public $CacheIndex;

    /**
     * @var string
     */
    public $CacheDB;

    /**
     * @param array{
     *  CacheFolder?: string,
     *  CacheIndex?: string,
     *  CacheDB?: string
     * } $Settings
     */
    public function __construct(array $Settings = [])
    {
        $this->CacheFolder = $Settings["CacheFolder"] ?? __DIR__ . "/../cache";
        $this->CacheIndex = $Settings["CacheIndex"] ?? "index.db";
        $this->CacheDB = $Settings["CacheDB"] ?? "cache.db";

        $indexFilePath = "$this->CacheFolder/$this->CacheIndex";
        if (file_exists($indexFilePath) === false) {
            touch($indexFilePath);
        }

        $databaseFilePath = "$this->CacheFolder/$this->CacheDB";
        if (file_exists($databaseFilePath) === false) {
            touch($databaseFilePath);
        }
    }

    /**
     * Flush the cache contents
     * @return void
     */
    public function flush()
    {
        $indexFilePath = "$this->CacheFolder/$this->CacheIndex";
        if (file_exists($indexFilePath)) {
            unlink($indexFilePath);
            touch($indexFilePath);
        }

        $databaseFilePath = "$this->CacheFolder/$this->CacheDB";
        if (file_exists($databaseFilePath)) {
            unlink($databaseFilePath);
            touch($databaseFilePath);
        }
    }

    /**
     * Return the MD5 of the data array to clearly identify the chart
     *
     * @param Data $Data
     * @param string $Marker
     * @return string
     */
    public function getHash(Data $Data, $Marker = "")
    {
        return md5($Marker . serialize($Data->Data));
    }

    /**
     * Write the generated picture to the cache
     *
     * @param string $ID
     * @param Image $pChartObject
     * @return void
     */
    public function writeToCache($ID, Image $pChartObject)
    {
        /* Compute the paths */
        $TemporaryFile = tempnam($this->CacheFolder, "tmp_");
        $Database = "$this->CacheFolder/$this->CacheDB";
        $Index = "$this->CacheFolder/$this->CacheIndex";
        /* Flush the picture to a temporary file */
        imagepng($pChartObject->Picture, $TemporaryFile);

        $PictureSize = filesize($TemporaryFile);
        if ($PictureSize === false || $PictureSize < 1) {
            throw new RuntimeException(
                "Invalid picture size for file $TemporaryFile"
            );
        }
        $DBSize = filesize($Database);

        /* Save the index */
        $Handle = @fopen($Index, "a");
        if ($Handle === false) {
            throw new RuntimeException("Unable to open file $Index");
        }
        fwrite($Handle, $ID . "," . $DBSize . "," . $PictureSize . "," . time() . ",0\r\n");
        fclose($Handle);

        /* Get the picture raw contents */
        $Handle = @fopen($TemporaryFile, "r");
        if ($Handle === false) {
            throw new RuntimeException("Unable to open file $TemporaryFile");
        }
        $Raw = fread($Handle, $PictureSize);
        if ($Raw === false) {
            throw new RuntimeException(
                "Unable to read $PictureSize from file $TemporaryFile"
            );
        }
        fclose($Handle);

        /* Save the picture in the solid database file */
        $Handle = @fopen($Database, "a");
        if ($Handle === false) {
            throw new RuntimeException("Unable to open file $Database");
        }
        fwrite($Handle, $Raw);
        fclose($Handle);

        /* Remove temporary file */
        unlink($TemporaryFile);
    }

    /**
     * Remove object older than the specified TS
     * @param int<0, max> $Expiry
     * @return void
     */
    public function removeOlderThan($Expiry)
    {
        $this->dbRemoval(["Expiry" => $Expiry]);
    }

    /**
     * Remove an object from the cache
     * @param string $ID
     * @return void
     */
    public function remove($ID)
    {
        $this->dbRemoval(["Name" => $ID]);
    }

    /**
     * Remove with specified criterias.
     *
     * @param array{ Name?: string, Expiry?: int<0, max> } $Settings
     * @return int|null
     */
    public function dbRemoval(array $Settings)
    {
        $ID = $Settings["Name"] ?? null;
        $Expiry = $Settings["Expiry"] ?? -(24 * 60 * 60);
        $TS = time() - $Expiry;

        /* Compute the paths */
        $Database = "$this->CacheFolder/$this->CacheDB";
        $Index = "$this->CacheFolder/$this->CacheIndex";
        $DatabaseTemp = "$this->CacheFolder/$this->CacheDB.tmp";
        $IndexTemp = "$this->CacheFolder/$this->CacheIndex.tmp";

        /* Single file removal */
        if ($ID != null) {
            /* Retrieve object informations */
            $Object = $this->isInCache($ID, true);

            /* If it's not in the cache DB, go away */
            if (!$Object) {
                return 0;
            }
        }

        /* Create the temporary files */
        if (file_exists($DatabaseTemp) === false) {
            touch($DatabaseTemp);
        }
        if (file_exists($IndexTemp) === false) {
            touch($IndexTemp);
        }

        /* Open the file handles */
        $IndexHandle = @fopen($Index, "r");
        if ($IndexHandle === false) {
            throw new RuntimeException("Unable to open file $Index");
        }

        $IndexTempHandle = @fopen($IndexTemp, "w");
        if ($IndexTempHandle === false) {
            fclose($IndexHandle);
            throw new RuntimeException("Unable to open file $IndexTemp");
        }

        $DBHandle = @fopen($Database, "r");
        if ($DBHandle === false) {
            fclose($IndexHandle);
            fclose($IndexTempHandle);
            throw new RuntimeException("Unable to open file $Database");
        }

        $DBTempHandle = @fopen($DatabaseTemp, "w");
        if ($DBTempHandle === false) {
            fclose($IndexHandle);
            fclose($IndexTempHandle);
            fclose($DBHandle);
            throw new RuntimeException("Unable to open file $DBTempHandle");
        }

        /* Remove the selected ID from the database */
        while (feof($IndexHandle) === false) {
            $Entry = fgets($IndexHandle, 4096);
            if ($Entry === false) {
                break;
            }

            $Entry = str_replace("\r", "", $Entry);
            $Entry = str_replace("\n", "", $Entry);
            /** @var SettingsArray|false $Settings */
            $Settings = preg_split("/,/", $Entry);
            if ($Settings === false) {
                fclose($IndexHandle);
                fclose($IndexTempHandle);
                fclose($DBHandle);
                fclose($DBTempHandle);
                throw new RuntimeException(
                    "Unable to read settings from entry $Entry of file $Index"
                );
            }

            if ($Entry != "") {
                $PicID = $Settings[0];
                $DBPos = (int) $Settings[1];
                /** @var int<1, max> $PicSize */
                $PicSize = (int) $Settings[2];
                $GeneratedTS = $Settings[3];
                $Hits = $Settings[4];

                if ($Settings[0] != $ID && $GeneratedTS > $TS) {
                    $CurrentPos = ftell($DBTempHandle);
                    fwrite(
                        $IndexTempHandle,
                        sprintf(
                            "%s,%s,%s,%s,%s\r\n",
                            $PicID,
                            $CurrentPos,
                            $PicSize,
                            $GeneratedTS,
                            $Hits
                        )
                    );

                    fseek($DBHandle, $DBPos);
                    $Picture = fread($DBHandle, $PicSize);
                    if ($Picture === false) {
                        fclose($IndexHandle);
                        fclose($IndexTempHandle);
                        fclose($DBHandle);
                        fclose($DBTempHandle);
                        throw new RuntimeException(
                            "Unable to read $PicSize from file $Database"
                        );
                    }

                    fwrite($DBTempHandle, $Picture);
                }
            }
        }

        /* Close the handles */
        fclose($IndexHandle);
        fclose($IndexTempHandle);
        fclose($DBHandle);
        fclose($DBTempHandle);

        /* Remove the prod files */
        unlink($Database);
        unlink($Index);

        /* Swap the temp & prod DB */
        rename($DatabaseTemp, $Database);
        rename($IndexTemp, $Index);

        return null;
    }

    /**
     * Is the file in cache?
     *
     * @param string $id
     * @param bool $Verbose
     * @param bool $updateHitsCount
     * @return bool|array{
     *  DBPos: numeric-string,
     *  PicSize: numeric-string,
     *  GeneratedTS: numeric-string,
     *  Hits: int|string
     * }
     */
    public function isInCache($id, $Verbose = false, $updateHitsCount = false)
    {
        $filePath = "$this->CacheFolder/$this->CacheIndex";

        /* Search the picture in the index file */
        $handle = @fopen($filePath, "r");
        if ($handle === false) {
            throw new RuntimeException("Unable to open file $filePath");
        }

        while (feof($handle) === false) {
            $indexPos = ftell($handle);
            $entry = fgets($handle, 4096);
            if ($entry != "") {
                /** @var SettingsArray $settings */
                $settings = preg_split("/,/", $entry);
                $pictureId = $settings[0];
                if ($pictureId == $id) {
                    fclose($handle);

                    $dbPos = $settings[1];
                    $pictureSize = $settings[2];
                    $generatedTs = $settings[3];
                    $hits = (int) $settings[4];

                    if ($updateHitsCount) {
                        $hits++;
                        $hitsAsString = (string) $hits;
                        if (strlen($hitsAsString) < 7) {
                            $hits = $hits . str_repeat(" ", 7 - strlen($hitsAsString));
                        }

                        $handle = @fopen($filePath, "r+");
                        if ($handle === false) {
                            throw new RuntimeException("Unable to open file $filePath");
                        }

                        if ($indexPos === false) {
                            fclose($handle);
                            throw new RuntimeException(
                                "Cannot read index position of $filePath"
                            );
                        }

                        fseek($handle, $indexPos);
                        fwrite(
                            $handle,
                            sprintf(
                                "%s,%s,%s,%s,%s\r\n",
                                $pictureId,
                                $dbPos,
                                $pictureSize,
                                $generatedTs,
                                $hits
                            )
                        );
                        fclose($handle);
                    }

                    if (((bool) $Verbose) === false) {
                        return true;
                    }

                    return [
                        "DBPos" => $dbPos,
                        "PicSize" => $pictureSize,
                        "GeneratedTS" => $generatedTs,
                        "Hits" => $hits
                    ];
                }
            }
        }

        fclose($handle);

        return false;
    }

    /**
     * Automatic output method based on the calling interface
     * @param string $ID
     * @param string $Destination
     * @return void
     */
    public function autoOutput($ID, $Destination = "output.png")
    {
        if (php_sapi_name() == "cli") {
            $this->saveFromCache($ID, $Destination);
        } else {
            $this->strokeFromCache($ID);
        }
    }

    /**
     * Show image from cache
     * @param string $ID
     * @return bool
     */
    public function strokeFromCache($ID)
    {
        $Picture = $this->getFromCache($ID);
        if ($Picture == null) {
            return false;
        }

        // @FIXME configurable content type
        header('Content-type: image/png');
        echo $Picture;

        return true;
    }

    /**
     * @param string $ID
     * @param string $Destination
     * @return bool
     */
    public function saveFromCache($ID, $Destination)
    {
        $picture = $this->getFromCache($ID);
        if ($picture == null) {
            return false;
        }

        $handle = @fopen($Destination, "w");
        if ($handle === false) {
            throw new RuntimeException("Unable to open file $Destination");
        }

        fwrite($handle, $picture);
        fclose($handle);

        return true;
    }

    /**
     * Get file from cache
     * @param string $ID
     * @return string|null
     */
    public function getFromCache($ID)
    {
        $filePath = "$this->CacheFolder/$this->CacheDB";
        $cacheInfo = $this->isInCache($ID, true, true);
        if ($cacheInfo === false) {
            return null;
        }

        if ($cacheInfo === true) {
            throw new RuntimeException('Expected an array, got bool instead');
        }

        $dbPosition = (int) $cacheInfo["DBPos"];
        /** @var int<1, max> $pictureSize */
        $pictureSize = (int) $cacheInfo["PicSize"];

        $handle = @fopen($filePath, "r");
        if ($handle === false) {
            throw new RuntimeException("Unable to open file $filePath");
        }

        fseek($handle, $dbPosition);

        // Raw picture data
        $picture = fread($handle, $pictureSize);
        fclose($handle);

        if ($picture === false) {
            throw new RuntimeException(
                "Unable to read $pictureSize from file $filePath"
            );
        }

        return $picture;
    }
}
