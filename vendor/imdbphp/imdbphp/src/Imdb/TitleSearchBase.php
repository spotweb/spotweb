<?php

namespace Imdb;

class TitleSearchBase extends MdbBase
{
    protected function parseTitleType($string)
    {
        $string = strtoupper($string);

        if (strpos($string, 'TV SERIES') !== false) {
            return Title::TV_SERIES;
        } elseif (strpos($string, 'TV EPISODE') !== false) {
            return Title::TV_EPISODE;
        } elseif (strpos($string, 'TV MINI SERIES') !== false) {
            return Title::TV_MINI_SERIES;
        } elseif (strpos($string, 'TV MOVIE') !== false) {
            return Title::TV_MOVIE;
        } elseif (strpos($string, 'TV SPECIAL') !== false) {
            return Title::TV_SPECIAL;
        } elseif (strpos($string, 'TV SHORT') !== false) {
            return Title::TV_SHORT;
        } elseif (strpos($string, 'VIDEO GAME') !== false) {
            return Title::GAME;
        } elseif (strpos($string, 'MUSIC VIDEO') !== false) {
            return Title::MUSIC_VIDEO;
        } elseif (strpos($string, 'VIDEO') !== false) {
            return Title::VIDEO;
        } elseif (strpos($string, 'SHORT') !== false) {
            return Title::SHORT;
        } elseif (strpos($string, 'PODCAST EPISODE') !== false) {
            return Title::PODCAST_EPISODE;
        } elseif (strpos($string, 'PODCAST SERIES') !== false) {
            return Title::PODCAST_SERIES;
        } else {
            return Title::MOVIE;
        }
    }
}
