<?php

interface Dao_TmdbInfo {
    function addCast(Dto_TmdbCast $cast);
    function addCrew(Dto_TmdbCrew $crew);
    function addImage(Dto_TmdbImage $image);
    function addInfo(Dto_TmdbInfo $tmdb);
    function addTrailer(Dto_TmdbTrailer $trailer);

    function getInfo($tmdbId);
    function getTrailers($tmdbId);
    function getCastList($tmdbId);
    function getCrewList($tmdbId);
    function getImageList($tmdbId);
} // interface Dao_TmdbInfo