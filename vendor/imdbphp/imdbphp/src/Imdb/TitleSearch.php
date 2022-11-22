<?php

namespace Imdb;

class TitleSearch extends MdbBase
{

    const MOVIE = Title::MOVIE;
    const TV_SERIES = Title::TV_SERIES;
    const TV_EPISODE = Title::TV_EPISODE;
    const TV_MINI_SERIES = Title::TV_MINI_SERIES;
    const TV_MOVIE = Title::TV_MOVIE;
    const TV_SPECIAL = Title::TV_SPECIAL;
    const TV_SHORT = Title::TV_SHORT;
    const GAME = Title::GAME;
    const VIDEO = Title::VIDEO;
    const SHORT = Title::SHORT;

    /**
     * Search IMDb for titles matching $searchTerms
     * @param string $searchTerms
     * @param array $wantedTypes *optional* imdb types that should be returned. Defaults to returning all types.
     *                            The class constants MOVIE,GAME etc should be used e.g. [TitleSearch::MOVIE, TitleSearch::TV_SERIES]
     * @param integer $maxResults *optional* specifies the maximum number of results for the search. The default is unlimited.
     * @return Title[] array of Title objects
     */
    public function search($searchTerms, $wantedTypes = null, $maxResults = -1)
    {
        $results = array();
        $resultsCounter = 0;
        $page = $this->getPage($searchTerms);

        // Parse & filter results
        if (preg_match_all(
            '!class="result_text"\s*>\s*<a href="/title/tt(?<imdbid>\d{7,8})/[^>]*>(?<title>.*?)</a>\s*(?:\(in development\))?(\([XIV]+\)\s*)?(?:\((?<year>\d{4})\))?(?<type>[^<]*)!ims',
            $page,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $type = $this->parseTitleType($match['type']);

                if (is_array($wantedTypes) && !in_array($type, $wantedTypes)) {
                    continue;
                }

                $results[] = Title::fromSearchResult(
                    $match['imdbid'],
                    $match['title'],
                    $match['year'],
                    $type,
                    $this->config,
                    $this->logger,
                    $this->cache
                );

                if (++$resultsCounter === $maxResults) {
                    break;
                }
            }
        } else {
            $xpath = $this->getXpathPage($searchTerms);

            $cells = $xpath->query("//section[@data-testid='find-results-section-title']//div[@class='ipc-metadata-list-summary-item__tc']");

            foreach ($cells as $key => $cell) {
                $year = 0;
                $type = '';

                $yearType = $xpath->query(".//ul[contains(@class, 'ipc-metadata-list-summary-item__tl')]", $cell);

                if ($yearType->length > 0) {
                    if (preg_match('!^(?<year>\d{4})?(-(\d{4})?)?(?:s\d+\.e\d+)?(?<type>.*)!', $yearType->item(0)->nodeValue, $match)) {
                        $year = (int) $match['year'];
                        $type = $match['type'];
                    }
                }

                $type = $this->parseTitleType($type);

                if (is_array($wantedTypes) && !in_array($type, $wantedTypes)) {
                    continue;
                }

                $linkAndTitle = $xpath->query(".//a[@class='ipc-metadata-list-summary-item__t']", $cell);

                if ($linkAndTitle->length < 1 || !preg_match('!tt(?<imdbid>\d+)!', $linkAndTitle->item(0)->getAttribute('href'), $href)) {
                    continue;
                }

                $results[] = Title::fromSearchResult(
                    $href['imdbid'],
                    trim($linkAndTitle->item(0)->nodeValue),
                    $year,
                    $type,
                    $this->config,
                    $this->logger,
                    $this->cache
                );

                if (++$resultsCounter === $maxResults) {
                    break;
                }
            }
        }

        return $results;
    }

    protected function parseTitleType($string)
    {
        $string = strtoupper($string);

        if (strpos($string, 'TV SERIES') !== false) {
            return self::TV_SERIES;
        } elseif (strpos($string, 'TV EPISODE') !== false) {
            return self::TV_EPISODE;
        } elseif (strpos($string, 'VIDEO GAME') !== false) {
            return self::GAME;
        } elseif (strpos($string, 'VIDEO') !== false) {
            return self::VIDEO;
        } elseif (strpos($string, 'SHORT') !== false) {
            return self::SHORT;
        } elseif (strpos($string, 'TV MINI SERIES') !== false) {
            return self::TV_MINI_SERIES;
        } elseif (strpos($string, 'TV MOVIE') !== false) {
            return self::TV_MOVIE;
        } elseif (strpos($string, 'TV SPECIAL') !== false) {
            return self::TV_SPECIAL;
        } elseif (strpos($string, 'TV SHORT') !== false) {
            return self::TV_SHORT;
        } else {
            return self::MOVIE;
        }
    }

    protected function buildUrl($searchTerms = null)
    {
        return "https://" . $this->imdbsite . "/find?s=tt&q=" . urlencode($searchTerms);
    }
}
