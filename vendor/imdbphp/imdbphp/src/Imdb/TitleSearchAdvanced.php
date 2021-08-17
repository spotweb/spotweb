<?php

#############################################################################
# IMDBPHP                              (c) Giorgos Giagas & Itzchak Rehberg #
# ------------------------------------------------------------------------- #
# Miscellaneous movie lists                                                 #
# written by Itzchak Rehberg <izzysoft AT qumran DOT org>                   #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Use IMDb's advanced search to get filtered lists of titles
 * e.g. most popular tv shows from 2000
 * @see https://www.imdb.com/search/
 * @see https://www.imdb.com/search/title?year=2015,2015&title_type=feature&explore=has
 * @author Izzy (izzysoft AT qumran DOT org)
 * @copyright (c) 2009 by Itzchak Rehberg and IzzySoft
 */
class TitleSearchAdvanced extends MdbBase
{

    // Title types
    const MOVIE = 'feature';
    const TV_SERIES = 'tv_series';
    const TV_EPISODE = 'tv_episode';
    const TV_MINI_SERIES = 'mini_series';
    const TV_MOVIE = 'tv_movie';
    const TV_SPECIAL = 'tv_special';
    const TV_SHORT = 'tv_short';
    const DOCUMENTARY = 'documentary';
    const GAME = 'game';
    const VIDEO = 'video';
    const SHORT = 'short';

    // Sorts
    const SORT_MOVIEMETER = 'moviemeter,asc';
    const SORT_ALPHA = 'alpha,asc';
    const SORT_USER_RATING = 'user_rating,desc';
    const SORT_NUM_VOTES = 'num_votes,desc';
    const SORT_US_BOX_OFFICE_GROSS = 'boxoffice_gross_us,desc';

    protected $titleTypes = array();
    protected $year = null;
    protected $countries = array();
    protected $languages = array();
    protected $sort = 'moviemeter,asc';

    /**
     * Set which types of titles should be returned
     * @param array $types e.g. [self::MOVIE, self::DOCUMENTARY]
     */
    public function setTitleTypes(array $types)
    {
        $this->titleTypes = $types;
    }

    /**
     * Set which year you want titles from
     * @param int $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * Set which countries of origin you want titles from
     * These are combinatory so you will only get titles that were made in every country you specify
     * @param array $countries Countries are 2/3/4 character codes
     * @see https://www.imdb.com/country/
     */
    public function setCountries(array $countries)
    {
        $this->countries = $countries;
    }

    /**
     * Set which languages are in the title
     * These are combinatory so you will only get titles that include every language you specify
     * @param array $languages Languages are 2/3/4 character codes
     * @see https://www.imdb.com/language/
     */
    public function setLanguages(array $languages)
    {
        $this->languages = $languages;
    }

    /**
     * Set the ordering of results.
     * See the SORT_ constants e.g. self::SORT_MOVIEMETER
     * @param string $sort
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * Perform the search
     * @return array
     * array('imdbid' => $id,
     *  'title' => $title,
     *  'year' => $year,
     *  'type' => $mtype,              e.g. 'TV Series', 'Feature Film' ..
     *  'serial' => $is_serial,        Is it a TV Series?
     *  'episode_imdbid' => $ep_id,    If the search found an episode it will show as type TV Series but have episode information too
     *  'episode_title' => $ep_name,   As above. The title of the episode
     *  'episode_year' => $ep_year     As above. The year of the episode
     * )
     */
    public function search()
    {
        $page = $this->pages->get($this->buildUrl());
        return $this->parse_results($page);
    }

    protected function buildUrl($context = null)
    {
        $queries = array();

        if ($this->titleTypes) {
            $queries['title_type'] = implode(',', $this->titleTypes);
        }

        if ($this->year) {
            $queries['year'] = $this->year;
        }

        if ($this->countries) {
            $queries['countries'] = implode(',', $this->countries);
        }

        if ($this->languages) {
            $queries['languages'] = implode(',', $this->languages);
        }

        if ($this->sort) {
            $queries['sort'] = $this->sort;
        }

        return "https://" . $this->imdbsite . '/search/title?' . http_build_query($queries);
    }

    protected function parseTitleType($xp, $resultSection)
    {
        $typeString = $xp->query(".//span[contains(@class, 'lister-item-year')]", $resultSection)->item(0)->nodeValue;
        if (preg_match('/\((\d+)(?P<serial>\x{2013}).+\)|\((\d+)\s+(?P<type>.*?)\)/u', $typeString, $match)) {
            if (isset($match['type'])) {
                return $match['type'];
            }
            if (isset($match['serial'])) {
                return Title::TV_SERIES;
            }
        }
        if ($xp->query(".//span[contains(@class, 'genre')]", $resultSection)->length) {
            $genre = strpos($xp->query(".//span[contains(@class, 'genre')]", $resultSection)->item(0)->nodeValue,
              'Short');
            if ($genre === 0 OR $genre >= 1) {
                return Title::SHORT;
            }
        }
        if ($xp->query(".//h3[@class='lister-item-header']/small", $resultSection)->length) {
            return Title::TV_EPISODE;
        }

        return 'Feature Film';
    }

    protected function getTitleType($type)
    {
        switch ($type) {
            case 'tv_series':
                return Title::TV_SERIES;
                break;
            case 'tv_episode':
                return Title::TV_EPISODE;
                break;
            case 'mini_series':
                return Title::TV_MINI_SERIES;
                break;
            case 'tv_movie':
                return Title::TV_MOVIE;
                break;
            case 'tv_special':
                return Title::TV_SPECIAL;
                break;
            case 'tv_short':
                return Title::TV_SHORT;
                break;
            case 'documentary':
                return Title::MOVIE;
                break;
            case 'game':
                return Title::GAME;
                break;
            case 'video':
                return Title::VIDEO;
                break;
            case 'short':
                return Title::SHORT;
                break;
            default:
                return 'Feature Film';
        }
    }

    /**
     * @param string html of page
     */
    protected function parse_results($page)
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $page);
        $xp = new \DOMXPath($doc);
        $resultSections = $xp->query("//div[@class='article']//div[@class='lister-item mode-advanced']");

        $mtype = null;
        $ret = array();
        $findTitleType = true;
        if (count($this->titleTypes) === 1) {
            $mtype = $this->getTitleType($this->titleTypes[0]);
            $findTitleType = false;
        }

        foreach ($resultSections as $resultSection) {
            $titleElement = $xp->query(".//h3[@class='lister-item-header']/a", $resultSection)->item(0);
            $title = trim($titleElement->nodeValue);
            preg_match('/tt(\d{7,8})/', $titleElement->getAttribute('href'), $match);
            $id = $match[1];
            $ep_id = null;
            $ep_name = null;
            $ep_year = null;
            $is_serial = false;

            if ($findTitleType) {
                $mtype = $this->parseTitleType($xp, $resultSection);
            }
            if (in_array($mtype, array('TV Series', 'TV Episode', 'TV Mini-Series'))) {
                $is_serial = true;
            }

            $yearItems = $xp->query(".//span[contains(@class, 'lister-item-year')]", $resultSection);
            $yearString = $yearItems->item(0)->nodeValue;

            preg_match('/\((\d+)/', $yearString, $match);
            if (isset($match[1])) {
                $year = (int)$match[1];
            } else {
                $year = null;
            }


            if ($mtype === 'TV Episode') {
                $episodeTitleElement = $xp->query(".//h3[@class='lister-item-header']/a", $resultSection)->item(1);
                if ($episodeTitleElement) {
                    $ep_name = $episodeTitleElement->nodeValue;
                    preg_match('/tt(\d{7,8})/', $episodeTitleElement->getAttribute('href'), $match);
                    $ep_id = $match[1];
                    if ($yearItems->length > 1) {
                        $yearString = $yearItems->item(1)->nodeValue;
                        if ($yearString) {
                            $ep_year = trim($yearString, '() ');
                        }
                    }
                }
            }

            $ret[] = array(
              'imdbid' => $id,
              'title' => $title,
              'year' => $year,
              'type' => $mtype,
              'serial' => $is_serial,
              'episode_imdbid' => $ep_id,
              'episode_title' => $ep_name,
              'episode_year' => $ep_year
            );
        }
        return $ret;
    }

}
