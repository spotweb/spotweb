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
# IMDBPHP UPCOMING RELEASES (based on IMDBPHP TOP CHARTS (c)Ricardo Silva) #
# written/modified/extended by Ed (github user: duck7000)                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Obtains information about upcoming movie releases as seen on IMDb
 * https://www.imdb.com/calendar
 * @author Ricardo Silva (banzap) <banzap@gmail.com>
 * @author Ed (github user: duck7000)
 */
class Calendar extends MdbBase
{
    /**
     * Get upcoming movie releases as seen on IMDb
     * @parameter $country This defines which country's releases are returned
     * for example DE, NL, US as they appear on https://www.imdb.com/calendar
     * @return array[] (a list of releases in the form [title, year, imdbid, release_date])
     * e.g. Array( Array(
     *   [release_date] => DateTime Object
     *   [title] => Babylon
     *   [year] => 2022
     *   [imdbid] => 10640346
     *   ) )
     *
     */
    public function upcomingReleases($country)
    {
        $page = $this->getXpathPage($country);
        $calendar = array();

        $dates = $page->query("//*[@id='main']/h4");

        if ($dates->length > 0) {
            foreach ($dates as $key => $date) {
                $key++;
                $titlesRaw = $page->query("//*[@id='main']/ul[$key]//li");

                foreach ($titlesRaw as $value) {
                    $href = $value->getElementsByTagName('a')->item(0)->getAttribute('href');
                    preg_match('!.*?/title/tt(\d+)/.*!', $href, $imdbid);
                    $title = trim($value->getElementsByTagName('a')->item(0)->nodeValue);
                    preg_match('#\((\d{4})\)$#', trim($value->nodeValue), $year);
                    $release_date = \DateTime::createFromFormat("d F Y", trim($date->nodeValue));
                    $release_date->setTime(0, 0, 0);
                    $calendar[] = array(
                        'release_date' => $release_date,
                        'title' => $title,
                        'year' => $year[1],
                        'imdbid' => $imdbid[1]
                    );
                }
            }
        } else {
            $dates = $page->query("//article[@data-testid='calendar-section']");

            foreach ($dates as $date) {
                $releaseDateNode = $page->query('.//div[@data-testid="release-date"]', $date);
                $release_date = \DateTime::createFromFormat('M d, Y', trim($releaseDateNode->item(0)->nodeValue));
                if (!$release_date) {
                    // Try the old date format, just in case that works
                    $release_date = \DateTime::createFromFormat('m/d/Y', trim($releaseDateNode->item(0)->nodeValue));
                }
                $release_date->setTime(0, 0, 0);

                $items = $page->query('.//a[@class="ipc-metadata-list-summary-item__t"]', $date);

                foreach ($items as $item) {
                    $title = preg_replace('!\(\d{4}\)$!', '', trim($item->nodeValue));
                    preg_match('#\((\d{4})\)$#', trim($item->nodeValue), $year);
                    preg_match('!tt(\d+)!', $item->getAttribute('href'), $imdbid);

                    $calendar[] = array(
                        'release_date' => $release_date,
                        'title' => $title,
                        'year' => $year[1],
                        'imdbid' => $imdbid[1]
                    );
                }
            }
        }

        return $calendar;
    }

    protected function buildUrl($context = null)
    {
        return "https://" . $this->config->imdbsite . "/calendar/?region=$context";
    }
}
