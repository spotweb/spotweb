<?php

interface Dao_SpotSearch {
	/* SIGNATURES updaten ?!! */
	public function getSpotCount($sqlFilter);
	public function getSpots($ourUserId, $pageNr, $limit, $parsedSearch);
	
} # Dao_SpotSearch
