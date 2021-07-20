<?php

#############################################################################
# IMDBPHP                              (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# IMDBPHP TOP CHARTS                                      (c) Ricardo Silva #
# written by Ricardo Silva (banzap) <banzap@gmail.com>                      #
# http://www.ricardosilva.pt.tl/                                            #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Obtaining the information about Moviemeter Top 10 and Weekend box office of IMDB
 * @author Ricardo Silva (banzap) <banzap@gmail.com>
 */
class Charts extends MdbBase
{

    protected $page = null;

    /**
     * Get the MOVIEmeter Top 10
     * @return string[] array of IMDb IDs
     */
    public function getChartsTop10()
    {
        $page = $this->getPage('moviemeter');
        $offset = strpos($page, 'Most Popular Movies');
        $end = strpos($page, 'Our Most Popular charts use data');
        $res = array();
        while (count($res) < 10) {
            $matches = null;
            preg_match("#<td class=\"titleColumn\">\s+<a\s+href=\"/title/tt(\d+)#", $page, $matches, PREG_OFFSET_CAPTURE, $offset);
            if (!$matches || $offset > $end) {
                break;
            }

            $res[] = $matches[1][0];
            $offset = $matches[0][1] + 1;
        }
        return $res;
    }

    /**
     * Get the USA Weekend Box-Office Summary, weekend earnings and all time earnings
     * Seems to only work in the UK and the US
     * @return array[] where each array contains [id, weekend, gross]
     * e.g. [
     *   [
     *     'id' => '2395427', IMDb ID of the title
     *     'weekend' => '3.46', Weekend takings in millions of local currency
     *     'gross' => '39.97' Gross/total takings in millions of local currency
     *   ]
     * ]
     */
    public function getChartsBoxOffice()
    {
        $page = $this->getPage('boxoffice');
        $matchinit = '<h1 class="header">Top Box Office';
        $offset = strpos($page, $matchinit);
        $end = strpos($page, 'See more box office results at BoxOfficeMojo.com');
        $chart = array();
        while (true) {
            $title = array();

            //mid
            $pattern = "#<a\s+href=\"/title/tt(\d+)#";
            $matches = null;
            preg_match($pattern, $page, $matches, PREG_OFFSET_CAPTURE, $offset);
            if (!isset($matches[0][0])) {
                break;
            }
            $title['id'] = substr($matches[0][0], 18, 7);
            $offset = $matches[0][1] + 10;

            if ($offset > $end) {
                break;
            }

            //weekend
            $moneyPattern = "/[\$£]([\d\.]+)(M|K)/";
            $matches1 = null;
            preg_match($moneyPattern, $page, $matches1, PREG_OFFSET_CAPTURE, $offset);
            $title['weekend'] = $matches1[2][0] === 'M' ? $matches1[1][0] : $matches1[1][0] / 1000;
            $offset = $matches1[1][1] + 10;

            //all
            $matches2 = null;
            preg_match($moneyPattern, $page, $matches2, PREG_OFFSET_CAPTURE, $offset);
            $title['gross'] = $matches2[2][0] === 'M' ? $matches2[1][0] : $matches2[1][0] / 1000;
            $offset = $matches2[1][1] + 10;

            $chart[] = $title;
        }
        return $chart;
    }

    protected function buildUrl($context = null)
    {
        return "https://" . $this->config->imdbsite . "/chart/$context";
    }

}
