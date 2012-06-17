<?php

interface SpotSearchDao {
	/* SIGNATURES updaten ?!! */
	public function getSpotCount($sqlFilter);
	public function getSpots($ourUserId, $pageNr, $limit, $parsedSearch);
	
} # SpotSearchDao
