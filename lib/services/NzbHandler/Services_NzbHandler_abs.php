<?php

abstract class Services_NzbHandler_abs
{
    protected $_name = 'Abstract';
    protected $_nameShort = 'Abstract';

    protected $_nzbHandling = null;
    protected $_settings = null;

    public function __construct(Services_Settings_Container $settings, $name, $nameShort, array $nzbHandling)
    {
        $this->_settings = $settings;
        $this->_nzbHandling = $nzbHandling;
        $this->_name = $name;
        $this->_nameShort = $nameShort;
    }

    // __construct

    /**
     * Actually process the spot.
     *
     * @param $fullspot Array with fullspot information, needed for title and category
     * @param $nzblist array List of NZB's (or one) we need to process
     *
     * @return mixed
     */
    abstract public function processNzb($fullspot, $nzblist);

    /**
     * Get the name of the application handling the nzb, e.g. "SabNZBd".
     */
    public function getName()
    {
        return $this->_name;
    }

    // getName

    /**
     * Set the name of the application handling the nzb. This allows template
     * designers to adapt the application name if necessary.
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    // setName

    /**
     * Get the name of the application handling the nzb, e.g. "SAB".
     */
    public function getNameShort()
    {
        return $this->_nameShort;
    }

    // getNameShort

    /**
     * Set the short name of the application handling the nzb. This allows template
     * designers to adapt the application name if necessary.
     */
    public function setNameShort($name)
    {
        $this->_nameShort = $name;
    }

    // setNameShort

    public function generateNzbHandlerUrl($spot, $spotwebApiParam)
    {
        $spotwebUrl = $this->_settings->get('spotweburl');
        $action = $this->_nzbHandling['action'];
        $url = $spotwebUrl.'?page=getnzb&amp;action='.$action.'&amp;messageid='.$spot['messageid'].$spotwebApiParam;

        return $url;
    }

    // generateNzbHandlerUrl

    /*
     * Generates a clean filename with characters which are allowed
     * on most operating systems' filesystems
     */
    protected function cleanForFileSystem($title)
    {
        $allowedChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!,@#^()-=+ _{}';
        $newTitle = '';

        for ($i = 0; $i < strlen($title); $i++) {
            if (stripos($allowedChars, $title[$i]) === false) {
                $newTitle .= '_';
            } else {
                $newTitle .= $title[$i];
            }
        } // for

        return $newTitle;
    }

    // cleanForFileSystem

    /*
     * Generates the full path where the NZB files are to be stored
     */
    protected function makeNzbLocalPath($fullspot, $path)
    {
        $category = $this->convertCatToSabnzbdCat($fullspot);

        // add category to path when asked for
        $path = str_replace('$SABNZBDCAT', $this->cleanForFileSystem($category), $path);

        // make sure the path adds with a trailing slash so we can just append the filename to it
        $path = $this->addTrailingSlash($path);

        return $path;
    }

    // makeNzbLocalPath

    /*
     * Adds, when necessary, a trailing slash to the directory path
     */
    protected function addTrailingSlash($path)
    {
        // als de path niet eindigt met een backslash of forwardslash, voeg die zelf toe
        if (strpos('\/', $path[strlen($path) - 1]) === false) {
            $path .= DIRECTORY_SEPARATOR;
        } // if

        return $path;
    }

    // addTrailingSlash

    /**
     * Either compresses or merges the NZB files.
     *
     * @param $fullspot array with full spot information
     * @param $nzblist array list of nzb files we want to process
     *
     * @return array contains the meta data and the nzb itself
     */
    protected function prepareNzb($fullspot, $nzblist)
    {
        /*
         * Depending on the requested action, we make sure this NZB
         * file can be sent as one file as current download managers
         * cannot process more than one file in one request.
         */
        $result = [];
        switch ($this->_nzbHandling['prepare_action']) {
            case 'zip':
                $result['nzb'] = $this->zipNzbList($nzblist);
                $result['mimetype'] = 'application/x-zip-compressed';
                $result['filename'] = 'SpotWeb_'.microtime(true).'.zip';
                break;
             // zip

            default:
                $result['nzb'] = $this->mergeNzbList($nzblist);
                $result['mimetype'] = 'application/x-nzb';
                $result['filename'] = $this->cleanForFileSystem($fullspot['title']).'.nzb';
                break;
             // merge
        } // switch

        return $result;
    }

    // prepareNzb

    /*
     * Converts a Spot category into a category which can be
     * used for sabnzbd or other download manager
     */
    protected function convertCatToSabnzbdCat($spot)
    {
        // fix the category
        $spot['category'] = (int) $spot['category'];

        /*
         * Retrieve the list of categories, and we user the default
         * category per .. default
         */
        $sabnzbd = $this->_settings->get('sabnzbd');

        if (isset($sabnzbd['categories'][$spot['category']]['default'])) {
            $category = $sabnzbd['categories'][$spot['category']]['default'];
        } else {
            $category = '';
        } // else

        /*
         * If we find a better match, than use that one, but else we use the
         * default category defined
         */
        foreach (['a', 'b', 'c', 'd', 'z'] as $subcatType) {
            $subList = explode('|', $spot['subcat'.$subcatType]);

            foreach ($subList as $cat) {
                if (isset($sabnzbd['categories'][$spot['category']][$cat])) {
                    $category = $sabnzbd['categories'][$spot['category']][$cat];
                } // if
            } // foreach
        } // foreach

        return $category;
    }

    // convertCatToSabnzbdCat

    /*
     * Merges a list of XML files into one
     */
    protected function mergeNzbList($nzbList)
    {
        $nzbXml = simplexml_load_string('<?xml version="1.0" encoding="iso-8859-1" ?>
											<!DOCTYPE nzb PUBLIC "-//newzBin//DTD NZB 1.0//EN" "http://www.newzbin.com/DTD/nzb/nzb-1.0.dtd">
											<nzb xmlns="http://www.newzbin.com/DTD/2003/nzb"></nzb>');

        $domNzbXml = dom_import_simplexml($nzbXml);

        $dom = new DOMDocument('1.0', 'utf-8');
        $domHead = $dom->createElement('head');
        foreach ($nzbList as $nzb) {
            $oneNzbFile = simplexml_load_string($nzb['nzb']);

            // go through all the head -> meta elements
            if (is_object($oneNzbFile->head->meta)) {
                foreach ($oneNzbFile->head->meta as $meta) {
                    // check for password type
                    if ($meta['type'] == 'password') {
                        // create a meta element with the password as value
                        $domMeta = $dom->createElement('meta', ''.$meta[0]);
                        // create attribute: type=password
                        $domAttribute = $dom->createAttribute('type');
                        $domAttribute->value = 'password';
                        // append attribute to meta-element
                        $domMeta->appendChild($domAttribute);
                        // append meta-element to head
                        $domHead->appendChild($domMeta);
                    }
                }
            }
        }
        // import head into result xml
        $domHead = $domNzbXml->ownerDocument->importNode($domHead, true);
        $domNzbXml->appendChild($domHead);

        foreach ($nzbList as $nzb) {
            $oneNzbFile = simplexml_load_string($nzb['nzb']);

            // add each file section to the larger XML object
            foreach ($oneNzbFile->file as $file) {
                // Import the file into the larger NZB object
                $domFile = $domNzbXml->ownerDocument->importNode(dom_import_simplexml($file), true);
                $domNzbXml->appendChild($domFile);
            } // foreach
        } // foreach

        return $nzbXml->asXml();
    }

    // mergeNzbList

    /*
     * Compresses the NZB files as one, so we can send the list of
     * NZB files as one.
     */
    protected function zipNzbList($nzbList)
    {
        $tmpZip = tempnam(sys_get_temp_dir(), 'SpotWebZip');

        $zip = new ZipArchive();
        $res = $zip->open($tmpZip, ZipArchive::CREATE);
        if ($res !== true) {
            throw new Exception('Unable to create temporary ZIP file: '.$res);
        } // if

        foreach ($nzbList as $nzb) {
            $zip->addFromString($this->cleanForFileSystem($nzb['spot']['title']).'.nzb', $nzb['nzb']);
        } // foreach
        $zip->close();

        /*
         * and read the ZIP file, so we can just process it as
         * opaque data
         */
        $zipFile = file_get_contents($tmpZip);

        // en wis de tijdelijke file
        unlink($tmpZip);

        return $zipFile;
    }

    // zipNzbList

    // NzbHandler API functions
    public function hasApiSupport()
    {
        return false;
    }

    // hasApiSupport

    public function getStatus()
    {
        // do nothing
        return false;
    }

    // getStatus

    public function isAvailable()
    {
        return true;
    }

    // isAvailable

    public function pauseQueue()
    {
        // do nothing
        return false;
    }

    //pauseQueue

    public function resumeQueue()
    {
        // do nothing
        return false;
    }

    // resumeQueue

    public function setSpeedLimit($limit)
    {
        // do nothing
        return false;
    }

    // setSpeedLimit

    public function moveDown($id)
    {
        // do nothing
        return false;
    }

    // moveDown

    public function moveUp($id)
    {
        // do nothing
        return false;
    }

    // moveUp

    public function moveTop($id)
    {
        // do nothing
        return false;
    }

    // moveTop

    public function moveBottom($id)
    {
        // do nothing
        return false;
    }

    // moveBottom

    public function setCategory($id, $category)
    {
        // do nothing
        return false;
    }

    // setCategory

    public function setPriority($id, $priority)
    {
        // do nothing
        return false;
    }

    // setPriority

    public function setPassword($id, $password)
    {
        // do nothing
        return false;
    }

    // setPassword

    public function delete($id)
    {
        // do nothing
        return false;
    }

    // delete

    public function rename($id, $name)
    {
        // do nothing
        return false;
    }

    // rename

    public function pause($id)
    {
        // do nothing
        return false;
    }

    // pause

    public function resume($id)
    {
        // do nothing
        return false;
    }

    // resume

    public function getBuiltinCategories()
    {
        /*
         * For NzbHandlers that do not use configurable categories, but simply create
         * category directories on demand (e.g. NZBGet) we'll just use the categories
         * that are configured in SpotWeb.
         */
        $sabnzbd = $this->_settings->get('sabnzbd');

        $allcategories = [];
        foreach ($sabnzbd['categories'] as $categories) {
            $allcategories = array_merge($allcategories, array_values($categories));
        }

        $allcategories = array_unique($allcategories);

        $result = [];
        $result['readonly'] = true;	// inform the GUI to not allow adding of adhoc categories
        $result['categories'] = $allcategories;

        return $result;
    }

    // getBuiltinCategories

    public function getVersion()
    {
        // do nothing
        return false;
    }

    // getVersion
} // class Services_NzbHandler_abs
