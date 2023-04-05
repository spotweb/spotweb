<?php
#############################################################################
# PHP MovieAPI                                          (c) Itzchak Rehberg #
# written by Itzchak Rehberg <izzysoft AT qumran DOT org>                   #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Accessing Movie information
 * @author Georgos Giagas
 * @author Izzy (izzysoft AT qumran DOT org)
 * @copyright (c) 2002-2004 by Giorgos Giagas and (c) 2004-2009 by Itzchak Rehberg and IzzySoft
 */
class MdbBase extends Config
{
    public $version = '7.3.1';

    protected $months = array(
      "January" => "01",
      "Jan" => "01",
      "February" => "02",
      "Feb" => "02",
      "March" => "03",
      "Mar" => "03",
      "April" => "04",
      "Apr" => "04",
      "May" => "05",
      "June" => "06",
      "Jun" => "06",
      "July" => "07",
      "Jul" => "07",
      "August" => "08",
      "Aug" => "08",
      "September" => "09",
      "Sep" => "09",
      "October" => "10",
      "Oct" => "10",
      "November" => "11",
      "Nov" => "11",
      "December" => "12",
      "Dec" => "12"
    );

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Pages
     */
    protected $pages;

    protected $page = array();

    protected $xpathPage = array();

    /**
     * @var string 7 digit identifier for this person
     */
    protected $imdbID;

    /**
     * @param Config $config OPTIONAL override default config
     * @param LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache. None of the caching config in `\Imdb\Config` have any effect except cache_expire
     */
    public function __construct(Config $config = null, LoggerInterface $logger = null, CacheInterface $cache = null)
    {
        parent::__construct();

        if ($config) {
            foreach (array(
                       "language",
                       "imdbsite",
                       "cachedir",
                       "usecache",
                       "storecache",
                       "usezip",
                       "converttozip",
                       "cache_expire",
                       "photodir",
                       "photoroot",
                       "imdb_img_url",
                       "debug",
                       "throwHttpExceptions",
                       "use_proxy",
                       "ip_address",
                       "proxy_host",
                       "proxy_port",
                       "proxy_user",
                       "proxy_pw",
                       "default_agent",
                       "force_agent"
                     ) as $key) {
                $this->$key = $config->$key;
            }
        }

        $this->config = $config ?: $this;
        $this->logger = empty($logger) ? new Logger($this->debug) : $logger;
        $this->cache = empty($cache) ? new Cache($this->config, $this->logger) : $cache;
        $this->pages = new Pages($this->config, $this->cache, $this->logger);
    }

    /**
     * Retrieve the IMDB ID
     * @return string id IMDBID currently used
     */
    public function imdbid()
    {
        return $this->imdbID;
    }

    /**
     * Set and validate the IMDb ID
     * @param string id IMDb ID
     */
    protected function setid($id)
    {
        if (is_numeric($id)) {
            $this->imdbID = str_pad($id, 7, '0', STR_PAD_LEFT);
        } elseif (preg_match("/(?:nm|tt)(\d{7,8})/", $id, $matches)) {
            $this->imdbID = $matches[1];
        } else {
            $this->debug_scalar("<BR>setid: Invalid IMDB ID '$id'!<BR>");
        }
    }

    #---------------------------------------------------------[ Debug helpers ]---
    protected function debug_scalar($scalar)
    {
        $this->logger->error($scalar);
    }

    protected function debug_object($object)
    {
        $this->logger->error('{object}', array('object' => $object));
    }

    protected function debug_html($html)
    {
        $this->logger->error(htmlentities($html));
    }

    /**
     * Get numerical value for month name
     * @param string name name of month
     * @return integer month number
     */
    protected function monthNo($mon)
    {
        return @$this->months[$mon];
    }

    /**
     * Get a page from IMDb, which will be cached in memory for repeated use
     * @param string $context Name of the page or some other context to build the URL with to retrieve the page
     * @return string
     */
    protected function getPage($context = null)
    {
        return $this->pages->get($this->buildUrl($context));
    }

    /**
     * @param string $page
     * @return \DomXPath
     */
    protected function getXpathPage($page)
    {
        if (!empty($this->xpathPage[$page])) {
            return $this->xpathPage[$page];
        }
        $source = $this->getPage($page);
        libxml_use_internal_errors(true);
        /* Creates a new DomDocument object */
        $dom = new \DomDocument;
        /* Load the HTML */
        $dom->loadHTML('<?xml encoding="utf-8" ?>' .$source);
        /* Create a new XPath object */
        $this->xpathPage[$page] = new \DomXPath($dom);
        return $this->xpathPage[$page];
    }

    /**
     * Overrideable method to build the URL used by getPage
     * @param string $context OPTIONAL
     * @return string
     */
    protected function buildUrl($context = null)
    {
        return '';
    }

}
