<?php

abstract class Services_ParseCollections_Abstract {
    /**
     * Record holding the spot information
     *
     * @var array
     */
    protected $spot;

    /**
     * @param array $spot
     */
    public function __construct(array $spot) {
        $this->spot = $spot;
    } // ctor

    /**
     * Parses an given Spot, and returns an Dto_CollectionInfo object,
     * with all the necessary fields
     *
     * @internal param array $spot
     * @returns Dto_CollectionInfo
     */
    abstract function parseSpot();

    /**
     * Cleans up an title and lowercases it
     *
     * @param string $title
     * @returns string
     */
    protected function prepareTitle($title) {
        return strtolower($title);
    } // prepareTitle

    /**
     * Cleans up an collection name
     *
     * @param string $collName
     * @returns string Cleaned up collection name
     */
    protected function prepareCollName($collName) {
        $tmpName = mb_convert_encoding($collName, 'UTF-8', 'UTF-8');
        $tmpName = str_replace(array(
                                    '.',
                                    ':',            // Remove any semi columns
                                    '-',
                                    '\'',           // Apostrophes
                                    '!',
                                    '_',
                                    '=',
                               ),
                               ' ',
                               $tmpName);
        $tmpName = preg_replace('/\s+/', ' ', $tmpName);
        $tmpName = trim($tmpName, " \t\n\r\0\x0B-=");
        $tmpName = strtolower($tmpName);
        $tmpName = ucfirst($tmpName);

        return $tmpName;
    } // prepareCollName

} // Series_Collections_Abstract