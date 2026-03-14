<?php

namespace Imdb;

class TitleSearch extends TitleSearchBase
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
    const MUSIC_VIDEO = Title::MUSIC_VIDEO;
    const SHORT = Title::SHORT;
    const PODCAST_EPISODE = Title::PODCAST_EPISODE;
    const PODCAST_SERIES = Title::PODCAST_SERIES;

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

        // Parse & filter results
        $xpath = $this->getXpathPage($searchTerms);

        $cells = $xpath->query("//section[@data-testid='find-results-section-title']//div[@class='ipc-metadata-list-summary-item__tc']");

        foreach ($cells as $key => $cell) {
            $year = 0;
            $type = '';

            $yearType = $xpath->query(".//div[contains(@class, 'cli-title-metadata')]", $cell);

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

            $epTitleQuery = ".//div[contains(@class, 'cli-ep-title')]";
            $epTitle = $xpath->query($epTitleQuery, $cell);

            if ($epTitle->length > 0) {
                $linkAndTitle = $xpath->query($epTitleQuery . "//a[@class='ipc-title-link-wrapper']", $cell);
                $epYear = $xpath->query(".//span[contains(@class, 'cli-ep-year')]", $cell);

                if ($epYear->length > 0) {
                    if (preg_match('!(?<year>\d{4})!', $epYear->item(0)->nodeValue, $match)) {
                        $year = (int) $match['year'];
                    }
                }
            } else {
                $linkAndTitle = $xpath->query(".//a[@class='ipc-title-link-wrapper']", $cell);
            }

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

        return $results;
    }

    protected function buildUrl($searchTerms = null)
    {
        return "https://" . $this->imdbsite . "/find/?s=tt&q=" . urlencode($searchTerms);
    }
}
