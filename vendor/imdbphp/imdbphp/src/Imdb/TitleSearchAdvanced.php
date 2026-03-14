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
class TitleSearchAdvanced extends TitleSearchBase
{
    // Title types
    const MOVIE = 'feature';
    const TV_SERIES = 'tv_series';
    const TV_EPISODE = 'tv_episode';
    const TV_MINI_SERIES = 'tv_miniseries';
    const TV_MOVIE = 'tv_movie';
    const TV_SPECIAL = 'tv_special';
    const TV_SHORT = 'tv_short';
    const GAME = 'video_game';
    const VIDEO = 'video';
    const SHORT = 'short';
    const MUSIC_VIDEO = 'music_video';
    const PODCAST_EPISODE = 'podcast_episode';
    const PODCAST_SERIES = 'podcast_series';

    // Sorts
    const SORT_MOVIEMETER = ''; // moviemeter,asc
    const SORT_ALPHA = 'alpha,asc';
    const SORT_USER_RATING = 'user_rating,desc';
    const SORT_NUM_VOTES = 'num_votes,desc';
    const SORT_US_BOX_OFFICE_GROSS = 'boxoffice_gross_us,desc';
    const SORT_RUNTIME = 'runtime,asc';
    const SORT_YEAR = 'year,asc';
    const SORT_RELEASE_DATE = 'release_date,asc';

    protected $title = null;
    protected $titleTypes = array();
    protected $year = null;
    protected $countries = array();
    protected $languages = array();
    protected $sort = null;
    protected $start = 1;

    /**
     * @var integer
     */
    protected $count = 25;

    /**
     * Set title to search for
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Set which types of titles should be returned
     * @param array $types e.g. [self::MOVIE, self::SHORT]
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
     * Set the number of results to return per search
     * Defaults to 50
     *
     * @todo This method does not work
     * @param integer $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * Set start of results(kinda like offset)
     *
     * @todo This method does not work
     * @param string $start
     */
    public function setStart($start)
    {
        $this->start = $start;
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

        if ($this->title) {
            $queries['title'] = $this->title;
        }

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

        return "https://" . $this->imdbsite . '/search/title/?' . http_build_query($queries);
    }

    /**
     * @param string $page html of page
     */
    protected function parse_results($page)
    {
        $xp = $this->getXpathPage($page);
        $resultSections = $xp->query("//ul[contains(@class, 'detailed-list-view')]//li");

        $mtype = null;
        $ret = array();
        $counter = 0;
        $findTitleType = true;

        foreach ($resultSections as $resultSection) {
            $titleElement = $xp->query(".//div[contains(@class, 'dli-title')]/a", $resultSection)->item(0);
            $title = preg_replace('!^\d+\.\s+!', '', trim($titleElement->nodeValue));
            preg_match('/tt(\d{7,8})/', $titleElement->getAttribute('href'), $match);

            $id = $match[1];
            $ep_id = null;
            $ep_name = null;
            $ep_year = null;
            $is_serial = false;
            $year = null;
            $type = '';

            $yearType = $xp->query(".//div[contains(@class, 'dli-title-metadata')]", $resultSection);

            if ($yearType->length > 0) {
                if (preg_match('!^(?<year>\d{4})?(-(\d{4})?)?(?:s\d+\.e\d+)?(?<type>.*)!', $yearType->item(0)->nodeValue, $match)) {
                    $year = (int) $match['year'];
                    $type = $match['type'];
                }
            }

            if ($findTitleType) {
                $mtype = $this->parseTitleType($type);

                if (count($this->titleTypes) === 1) {
                    $findTitleType = false;
                }
            }

            if (in_array($mtype, array(Title::TV_SERIES, Title::TV_EPISODE, Title::TV_MINI_SERIES))) {
                $is_serial = true;
            }

            if ($mtype === Title::TV_EPISODE) {
                $epTitle = $xp->query(".//div[contains(@class, 'dli-ep-title')]/a", $resultSection)->item(0);

                if ($epTitle) {
                    $ep_name = $epTitle->nodeValue;
                    preg_match('/tt(\d{7,8})/', $epTitle->getAttribute('href'), $match);
                    $ep_id = $match[1];

                    $epYear = $xp->query(".//span[contains(@class, 'li-ep-year')]", $resultSection);

                    if ($epYear->length > 0) {
                        if (preg_match('!(?<year>\d{4})!', $epYear->item(0)->nodeValue, $match)) {
                            $ep_year = (int) $match['year'];
                        }
                    }
                }
            }

            $ret[] = array(
              'rank' => $this->start + $counter++,
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
