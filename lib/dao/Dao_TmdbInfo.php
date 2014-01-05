<?php

interface Dao_TmdbInfo {
    function saveCast(Dto_TmdbCast $cast);
    function saveCrew(Dto_TmdbCrew $crew);
    function saveImage(Dto_TmdbImage $image);
    function saveInfo(Dto_TmdbInfo $tmdb);
    function saveTrailer(Dto_TmdbTrailer $trailer);

    function getSpecificCredit($tmdbCreditId);
    function getSpecificCast($tmdbCastId);
    function getSpecificCrew($tmdbCrewId);

    function findPosterImages($tmdbId);
    function findPosterImage($filePath);

    function findBackDropImages($tmdbId);
    function findBackDropImage($filePath);

    function findTrailers($tmdbId);
    function findTrailer($name, $source, $size);
} // interface Dao_TmdbInfo