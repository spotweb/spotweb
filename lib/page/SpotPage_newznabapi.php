<?php

class SpotPage_newznabapi extends SpotPage_Abs {
	private $_params;

	function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params) {
		parent::__construct($daoFactory, $settings, $currentSession);

		$this->_params = $params;
	} # ctor

    /**
     * Generic render function, the actual processing happens elsewhere
     * in this class
     */
    function render() {
		# Don't let this output be cached
		$this->sendExpireHeaders(true);

        /*
         * CAPS function is used to query the server for supported features and the protocol version and other
         * meta data relevant to the implementation. This function doesn't require the client to provide any
         * login information but can be executed out of "login session".
         */
		if ($this->_params['t'] == "caps" || $this->_params['t'] == "c") {
			$this->caps();
			return ;
		} # if

		# Make sure the user has permissions to retrieve the index
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spots_index, '');

        /*
         * Determine the output type
         */
        if ($this->_params['o'] == 'json') {
            $outputtype = 'json';
        } else {
            $outputtype = 'xml';
        } # else

        /*
         * Main switch statement, determines what actually has to be done
         */
        switch ($this->_params['t']) {
			case ""			: $this->showApiError(200); break;
			case "search"	:
			case "s"		:
			case "tvsearch"	:
			case "t"		:
			case "music"	:
			case "movie"	:
			case "m"		: $this->search($outputtype); break;
			case "d"		:
			case "details"	: $this->spotDetails($outputtype); break;
			case "g"		:
			case "get"		: $this->getNzb(); break;
			default			: $this->showApiError(202);
		} # switch

	} # render()

    /**
     * Search the spotweb database for a specific piece of information
     *
     * @param $outputtype
     */
    function search($outputtype) {
		# Check users' permissions
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_perform_search, '');

		$searchParams = array();
        $tvInfo = new Dto_MediaInformation();
        $tvInfo -> setTitle("");
        $tvInfo -> setValid(true);
        /**
         * Now determine what type of information we are searching for using sabnzbd
         */
        if ($this->_params['t'] == "t" || $this->_params['t'] == "tvsearch") {
        	$found = false;
        	# First search on tvmazeid if present
        	if (($found == false) and ($this->_params['tvmazeid'] != "")) {
        		if (! preg_match ( '/^[0-9]{1,6}$/', $this->_params ['tvmazeid'] )) {
        			$this->showApiError ( 201 );
        			return;
        		} // if
				/*
				 * Actually retrieve the information from TVMaze, based on the
				 * TVmaze ID passed by the API
				 */
				$svcMediaInfoTvmaze = new Services_MediaInformation_Tvmaze ( $this->_daoFactory->getCacheDao () );
				$svcMediaInfoTvmaze->setSearchid ( $this->_params ['tvmazeid'] );
				$svcMediaInfoTvmaze->setSearchName ( "tvmaze" ); # Indicate tvmazeid usage
				$tvInfo = $svcMediaInfoTvmaze->retrieveInfo ();
				$found = $tvInfo -> isValid();
        	}
        	# second search on rid (rageid) if present
        	if (($found == false) and ($this->_params['rid'] != "")) {
        		# validate input
        		if (!preg_match('/^[0-9]{1,6}$/', $this->_params['rid'])) {
        			$this->showApiError(201);
        			return ;
        		} # if
	            /*
	             * Actually retrieve the information from TVMaze as long as TVrage is down, based on the
	             * tvrage passed by the API
	             */
	            $svcMediaInfoTvmaze = new Services_MediaInformation_Tvmaze($this->_daoFactory->getCacheDao());
	            $svcMediaInfoTvmaze->setSearchid($this->_params['rid']);
	            $tvInfo = $svcMediaInfoTvmaze->retrieveInfo();
	            $svcMediaInfoTvmaze->setSearchName ( "tvrage" ); # Indicate tvmazeid usage
				$found = $tvInfo -> isValid();
        	}
        	# third search on q (showname) if present
        	if (($found == false) and ($this->_params['q'] != "")) {
        		$tvInfo = new Dto_MediaInformation();
        		$tvInfo -> setTitle($this->_params['q']);
        		$tvInfo -> setValid(true);
        		$found = true;
        	}
        	# fourth, no search information present, set emtpy
        	if ($found == false) {
        		if ((!empty($this -> _params['tvmazeid'])) or (!empty($this -> _params['rid']))) {
        			$this->showApiError(300);
        			return ;
        		}
        	}

        	/*
             * Try to parse the season parameter. This can be either in the form of S1, S01, 1, 2012, etc.
             * we try to standardize all these types of season definitions into one format.
             */
			$episodeSearch = '';
            $seasonSearch = '';
			if (preg_match('/^[sS][0-9]{1,2}$/', $this->_params['season']) ||
                preg_match('/^[0-9]{1,4}$/', $this->_params['season'])) {
                /*
                 * Did we get passed a 4 digit season (most likely a year), or a
                 * two digit season?
                 */
                if (strlen($this->_params['season']) < 3) {
                    if (is_numeric($this->_params['season'])) {
                        $seasonSearch = 'S' . str_pad($this->_params['season'], 2, "0", STR_PAD_LEFT);
                    } else {
                        $seasonSearch = $this->_params['season'];
                    } # else
                } else {
                    $seasonSearch = $this->_params['season'] . ' ';
                } # else
			} elseif ($this->_params['season'] != "") {
				$this->showApiError(201);
				return ;
			} # if

            /*
             * And try to add an episode parameter, basically the same set of rules
             * as for the season
             */
            $title = $tvInfo->getTitle();
			if (preg_match('/^[eE][0-9]{1,2}$/', $this->_params['ep']) ||
                preg_match('/^[0-9]{1,2}$/', $this->_params['ep']) ||
                preg_match('/^[0-9]{1,2}\/[0-9]{1,2}$/', $this->_params['ep'])) {
                    if (is_numeric($this->_params['ep'])) {
                        $episodeSearch .= 'E' . str_pad($this->_params['ep'], 2, "0", STR_PAD_LEFT);
                    } else {
                        $episodeSearch .= $this->_params['ep'];
                    } # else
			} elseif ($this->_params['ep'] != "") {
				$this->showApiError(201);
				return ;
			} else {
                // Complete season search, add wildcard character to season
            	if (!empty($title)) {
                    if (!empty($seasonSearch )) {
                        $seasonSearch .= '*';
                        // and search for the text 'Season ' ...
                        $searchParams['value'][] = "Titel:=:OR:+\"" . $title . "\" +\"Season " . (int) $this->_params['season'] . "\"";
                    }
            	}
            } # else

			/*
             * The + operator is supported both by PostgreSQL and MySQL's FTS
			 *
			 * We search both for S04E17 and S04 E17 (with a space)
			 */
            if (!empty($title)) {
				$searchParams['value'][] = "Titel:=:OR:+\"" . $tvInfo->getTitle() . "\" +" . $seasonSearch . $episodeSearch;
	            if (!empty($episodeSearch)) {
	                $searchParams['value'][] = "Titel:=:OR:+\"" . $tvInfo->getTitle() . "\" +" . $seasonSearch . ' +' . $episodeSearch;
	            } # if
            }
            if (empty($this->_params['cat'] )) {
				$this->_params['cat'] = 5000;
            }
		} elseif ($this->_params['t'] == "music") {
			if (empty($this->_params['artist']) && empty($this->_params['cat'])) {
				$this->_params['cat'] = 3000;
			} else {
				$searchParams['value'][] = "Titel:=:DEF:\"" . $this->_params['artist'] . "\"";
			} # if
		} elseif ($this->_params['t'] == "m" || $this->_params['t'] == "movie") {
			# validate input
			if ($this->_params['imdbid'] == "") {
				$this->showApiError(200);

				return ;
			} elseif (!preg_match('/^[0-9]{1,8}$/', $this->_params['imdbid'])) {
				$this->showApiError(201);

				return ;
			} # if

			/*
             * Actually retrieve the information from imdb, based on the
			 * imdbid passed by the API
			 */
            $svcMediaInfoImdb = new Services_MediaInformation_Imdb($this->_daoFactory->getCacheDao());
            $svcMediaInfoImdb->setSearchid($this->_params['imdbid']);
            $imdbInfo = $svcMediaInfoImdb->retrieveInfo();

            if (!$imdbInfo->isValid()) {
				$this->showApiError(301);

				return ;
			} # if

			/* Extract the release date from the IMDB info page */
			if ($imdbInfo->getReleaseYear() != null) {
				$movieReleaseDate = '+(' . $imdbInfo->getReleaseYear() . ')';
			} else {
                $movieReleaseDate = '';
            } # else

            /*
             * Add movie title to the query
             */
			$searchParams['value'][] = "Titel:=:OR:+\"" . $imdbInfo->getTitle()  . "\" " . $movieReleaseDate;

			// imdb sometimes returns the title translated, if so, pass the original title as well
            if ($imdbInfo->getAlternateTitle() != null) {
                $searchParams['value'][] = "Title:=:OR:+\"" . $imdbInfo->getAlternateTitle() . "\" " . $movieReleaseDate;
            } # if

		} elseif (!empty($this->_params['q'])) {
			$searchTerm = str_replace(" ", " +", $this->_params['q']);
			$searchParams['value'][] = "Titel:=:OR:+" . $searchTerm;
		} # elseif

        /*
         * When a user added a maximum age for queries, convert it to
         * a Spotweb query as well
         */
        if ($this->_params['maxage'] != "" && is_numeric($this->_params['maxage'])) {
			$searchParams['value'][] = "date:>:DEF:-" . $this->_params['maxage'] . "days";
        } # if

        /*
         * We combine the "newznabapi" categories, with a custom extension for
         * categories so we can filter deeper than the newznab API can per default
         */
		$tmpCat = array();
		foreach (explode(",", $this->_params['cat']) as $category) {
			$tmpCat[] = $this->nabcat2spotcat($category);
		} # foreach
		$searchParams['tree'] = implode(",", $tmpCat) . ',' . $this->_params['spotcat'];

		/*
		 * Do not retrieve spots with a filesize of zero (these are very old spots,
		 * which have no NZB linked to it) as they are useless for a API consumer
		 */
		$searchParams['value'][] = "filesize:>:DEF:0";

        /*
         * Gather the preference of the results per page and use it in this
         * system as well when no value is explicitly provided
         */
		if ((!empty($this->_params['limit'])) &&
            (is_numeric($this->_params['limit'])) &&
            ($this->_params['limit'] < 500)) {
                $limit = $this->_params['limit'];
        } else {
            $limit = $this->_currentSession['user']['prefs']['perpage'];
        } # else


        if ((!empty($this->_params['offset'])) && (is_numeric($this->_params['offset']))) {
            $pageNr = $this->_params['offset'];
        } else {
            $pageNr = 0;
        } # else

        /*
         * We get a bunch of query parameters, so now change this to the actual
         * search query the user requested including the required sorting
         */
        $svcUserFilter = new Services_User_Filters($this->_daoFactory, $this->_settings);

        $svcSearchQp = new Services_Search_QueryParser($this->_daoFactory->getConnection());
        $parsedSearch = $svcSearchQp->filterToQuery(
            $searchParams,
            array(
                'field' => 'stamp',
                'direction' => 'DESC'
            ),
            $this->_currentSession,
            $svcUserFilter->getIndexFilter($this->_currentSession['user']['userid']));

         /*
         * Actually fetch the spots, we always perform
         * this action even when the watchlist is editted
         */
        $svcProvSpotList = new Services_Providers_SpotList($this->_daoFactory->getSpotDao());
        $spotsTmp = $svcProvSpotList->fetchSpotList($this->_currentSession['user']['userid'],
            $pageNr,
			$limit,
            $parsedSearch);

		$this->showResults($spotsTmp, ($pageNr * $limit), $outputtype);
	} # search

    /*
     * Actually create the XML or JSON output from the search
     * results
     */
	function showResults($spots, $offset, $outputtype) {
		$nzbhandling = $this->_currentSession['user']['prefs']['nzbhandling'];

		if ($outputtype == "json") {
			$doc = array();
			foreach($spots['list'] as $spot) {
				$data = array();
				$data['ID']				= $spot['messageid'];
				$data['name']			= html_entity_decode ($spot['title'],ENT_QUOTES,'UTF-8');
				$data['size']			= $spot['filesize'];
				$data['adddate']		= date('Y-m-d H:i:s', $spot['stamp']);
				$data['guid']			= $spot['messageid'];
				$data['fromname']		= $spot['poster'];
				$data['completion']		= 100;

                $cat = array();
                if( !empty($spot["subcatz"])) {
					$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcatz'], $spot["subcata"]));
					if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
						$cat = $nabCat[0];
					} # if
				} # if
				
				$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcata']));
				if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
					$data['categoryID'] = $nabCat[0];
					$cat .= implode(",", $nabCat);
				} # if

				$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcatb']));
				if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
					$cat .= "," . $nabCat[0];
				} # if

				$data['comments']		= $spot['commentcount'];
				$data['category_name']	= SpotCategories::HeadCat2Desc($spot['category']) . ': ' . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);
				$data['category_ids']	= $cat;

				if (empty($doc)) {
					$data['_totalrows'] = count($spots['list']);
				}

				$doc[] = $data;
			} # foreach

			echo json_encode($doc);
		} else {
			# Create XML
			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = true;

			$rss = $doc->createElement('rss');
			$rss->setAttribute('version', '2.0');
			$rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
			$rss->setAttribute('xmlns:newznab', 'http://www.newznab.com/DTD/2010/feeds/attributes/');
			$doc->appendChild($rss);

			$atomSelfLink = $doc->createElement('atom:link');
			$atomSelfLink->setAttribute('href', $this->_settings->get('spotweburl') . 'api');
			$atomSelfLink->setAttribute('rel', 'self');
			$atomSelfLink->setAttribute('type', 'application/rss+xml');

			$channel = $doc->createElement('channel');
			$channel->appendChild($atomSelfLink);
			$channel->appendChild($doc->createElement('title', 'Spotweb Index'));
			$channel->appendChild($doc->createElement('description', 'Spotweb Index API Results'));
			$channel->appendChild($doc->createElement('link', $this->_settings->get('spotweburl')));
			$channel->appendChild($doc->createElement('language', 'en-gb'));
			$channel->appendChild($doc->createElement('webMaster', $this->_currentSession['user']['mail'] . ' (' . $this->_currentSession['user']['firstname'] . ' ' . $this->_currentSession['user']['lastname'] . ')'));
			$channel->appendChild($doc->createElement('category', ''));
			$rss->appendChild($channel);

			$image = $doc->createElement('image');
			$image->appendChild($doc->createElement('url', $this->_settings->get('spotweburl') . 'images/spotnet.gif'));
			$image->appendChild($doc->createElement('title', 'Spotweb Index'));
			$image->appendChild($doc->createElement('link', $this->_settings->get('spotweburl')));
			$image->appendChild($doc->createElement('description', 'SpotWeb Index API Results'));
			$channel->appendChild($image);

			$newznabResponse = $doc->createElement('newznab:response');
			$newznabResponse->setAttribute('offset', $offset);
			$newznabResponse->setAttribute('total', count($spots['list']));
			$channel->appendChild($newznabResponse);

			foreach($spots['list'] as $spot) {
				$spot = $this->_tplHelper->formatSpotHeader($spot);
				$nzbUrl = $this->_tplHelper->makeBaseUrl("full") . 'api?t=g&amp;id=' . $spot['messageid'] . $this->_tplHelper->makeApiRequestString();
				if ($this->_params['del'] == "1" && $this->_spotSec->allowed(SpotSecurity::spotsec_keep_own_watchlist, '')) {
					$nzbUrl .= '&amp;del=1';
				} # if

				$guid = $doc->createElement('guid', $spot['messageid']);
				$guid->setAttribute('isPermaLink', 'false');

				$item = $doc->createElement('item');
 				$item->appendChild($doc->createElement('title', htmlspecialchars(html_entity_decode ($spot['title'],ENT_QUOTES,'UTF-8'), ENT_XHTML, "UTF-8")));
				$item->appendChild($guid);
				$item->appendChild($doc->createElement('link', $nzbUrl));
				$item->appendChild($doc->createElement('pubDate', date('r', $spot['stamp'])));
				$item->appendChild($doc->createElement('category', SpotCategories::HeadCat2Desc($spot['category']) . " > " . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata'])));
				$channel->appendChild($item);

				$enclosure = $doc->createElement('enclosure');
				$enclosure->setAttribute('url', html_entity_decode($nzbUrl));
				$enclosure->setAttribute('length', $spot['filesize']);
				switch ($nzbhandling['prepare_action']) {
					case 'zip'	: $enclosure->setAttribute('type', 'application/zip'); break;
					default		: $enclosure->setAttribute('type', 'application/x-nzb');
				} # switch
				$item->appendChild($enclosure);
				
				if( !empty($spot["subcatz"])) {
					$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcatz'], $spot["subcata"]));
					if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
						$attr = $doc->createElement('newznab:attr');
						$attr->setAttribute('name', 'category');
						$attr->setAttribute('value', $nabCat[0]);
						$item->appendChild($attr);
					} # if
				} # if
				
				$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcata']));
				if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
					$attr = $doc->createElement('newznab:attr');
					$attr->setAttribute('name', 'category');
					$attr->setAttribute('value', $nabCat[0]);
					$item->appendChild($attr);

					$attr = $doc->createElement('newznab:attr');
					$attr->setAttribute('name', 'category');
					$attr->setAttribute('value', $nabCat[1]);
					$item->appendChild($attr);
				} # if

				$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcatb']));
				if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
					$attr = $doc->createElement('newznab:attr');
					$attr->setAttribute('name', 'category');
					$attr->setAttribute('value', $nabCat[0]);
					$item->appendChild($attr);
				} # if

				if ( !empty($spot['subcatc'])) {
					$nabCat = explode("|",  $spot['subcatc']);
					$count=0;
					$subs=array();

					if ( in_array('c2',$nabCat)==true || in_array('c1',$nabCat)==true || in_array('c6',$nabCat)==true  ) {
						$subs[$count] = 'dutch';
						$count+=1;
					} # if
					if ( in_array('c3',$nabCat)==true || in_array('c4',$nabCat)==true || in_array('c7',$nabCat)==true) {
						$subs[$count] = 'english';
						$count+=1;
					} # if
					if ( $count!=0) {
					$attr = $doc->createElement('newznab:attr');
						$attr->setAttribute('name', 'subs');
						$attr->setAttribute('value', implode(',', $subs));
						$item->appendChild($attr);
					}
				} # if

				$attr = $doc->createElement('newznab:attr');
				$attr->setAttribute('name', 'size');
				$attr->setAttribute('value', $spot['filesize']);
				$item->appendChild($attr);

				if ($this->_params['extended'] != "0") {
					$attr = $doc->createElement('newznab:attr');
					$attr->setAttribute('name', 'poster');
					$attr->setAttribute('value', $spot['poster'] . '@spot.net');
					$item->appendChild($attr);

					$attr = $doc->createElement('newznab:attr');
					$attr->setAttribute('name', 'comments');
					$attr->setAttribute('value', $spot['commentcount']);
					$item->appendChild($attr);
				} # if
			} # foreach

			$this->sendContentTypeHeader('xml');
			echo $doc->saveXML();
		}
	} # showResults

	function spotDetails($outputtype) {
		if (empty($this->_params['messageid'])) {
			$this->showApiError(200);

			return ;
		} # if

		# Make sure the specific permissions are implemented
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_view_spotdetail, '');

		# spot ophalen
		try {
			$fullSpot = $this->_tplHelper->getFullSpot($this->_params['messageid'], true);
		}
		catch(Exception $x) {
			$this->showApiError(302);

			return ;
		} # catch

		$nzbhandling = $this->_currentSession['user']['prefs']['nzbhandling'];
		/*
		 * Ugly @ operator, but we cannot reliably fix the library for the moment
		 */
		$spot = @$this->_tplHelper->formatSpot($fullSpot);

		if ($outputtype == "json") {
			$doc = array();
			$doc['ID']				= $spot['id'];
			$doc['name']			= $spot['title'];
			$doc['size']			= $spot['filesize'];
			$doc['adddate']			= date('Y-m-d H:i:s', $spot['stamp']);
			$doc['guid']			= $spot['messageid'];
			$doc['fromname']		= $spot['poster'];
			$doc['completion']		= 100;

			if( !empty($spot["subcatz"])) {
				$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcatz'], $spot["subcata"]));
				if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
					$cat = $nabCat[0];
				} # if
			} # if

			$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcata']));
			if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
				$doc['categoryID'] = $nabCat[0];
				$cat .= implode(",", $nabCat);
			} # if

			$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcatb']));
			if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
				$cat .= "," . $nabCat[0];
			} # if

			$doc['comments']		= $spot['commentcount'];
			$doc['category_name']	= SpotCategories::HeadCat2Desc($spot['category']) . ': ' . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);
			$doc['category_ids']	= $cat;

			echo json_encode($doc);
		} else {
			$nzbUrl = $this->_tplHelper->makeBaseUrl("full") . 'api?t=g&amp;id=' . $spot['messageid'] . $this->_tplHelper->makeApiRequestString();

			# Opbouwen XML
			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = true;

			$rss = $doc->createElement('rss');
			$rss->setAttribute('version', '2.0');
			$rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
			$rss->setAttribute('xmlns:newznab', 'http://www.newznab.com/DTD/2010/feeds/attributes/');
			$rss->setAttribute('encoding', 'utf-8');
			$doc->appendChild($rss);

			$channel = $doc->createElement('channel');
			$channel->appendChild($doc->createElement('title', 'Spotweb'));
			$channel->appendChild($doc->createElement('language', 'nl'));
			$channel->appendChild($doc->createElement('description', 'Spotweb Index Api Detail'));
			$channel->appendChild($doc->createElement('link', $this->_settings->get('spotweburl')));
			$channel->appendChild($doc->createElement('webMaster', $this->_currentSession['user']['mail'] . ' (' . $this->_currentSession['user']['firstname'] . ' ' . $this->_currentSession['user']['lastname'] . ')'));
			$channel->appendChild($doc->createElement('category', ''));
			$rss->appendChild($channel);

			$image = $doc->createElement('image');
			$image->appendChild($doc->createElement('url', $this->_tplHelper->makeImageUrl($spot, 300, 300)));
			$image->appendChild($doc->createElement('title', 'Spotweb Index'));
			$image->appendChild($doc->createElement('link', $this->_settings->get('spotweburl')));
			$image->appendChild($doc->createElement('description', 'Visit Spotweb Index'));
			$channel->appendChild($image);

			$poster = (empty($spot['spotterid'])) ? $spot['poster'] : $spot['poster'] . " (" . $spot['spotterid'] . ")";

			$guid = $doc->createElement('guid', $spot['messageid']);
			$guid->setAttribute('isPermaLink', 'false');

			$description = $doc->createElement('description');
			$descriptionCdata = $doc->createCDATASection($spot['description'] . '<br /><font color="#ca0000">Door: ' . $poster . '</font>');
			$description->appendChild($descriptionCdata);

			$item = $doc->createElement('item');
			$item->appendChild($doc->createElement('title', htmlspecialchars($spot['title'], ENT_QUOTES, "UTF-8")));
			$item->appendChild($guid);
			$item->appendChild($doc->createElement('link', $nzbUrl));
			$item->appendChild($doc->createElement('pubDate', date('r', $spot['stamp'])));
			$item->appendChild($doc->createElement('category', SpotCategories::HeadCat2Desc($spot['category']) . " > " . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata'])));
			$item->appendChild($description);
			$channel->appendChild($item);

			$enclosure = $doc->createElement('enclosure');
			$enclosure->setAttribute('url', html_entity_decode($nzbUrl));
			$enclosure->setAttribute('length', $spot['filesize']);
			switch ($nzbhandling['prepare_action']) {
				case 'zip'	: $enclosure->setAttribute('type', 'application/zip'); break;
				default		: $enclosure->setAttribute('type', 'application/x-nzb');
			} # switch
			$item->appendChild($enclosure);

			if( !empty($spot["subcatz"])) {
				$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcatz'], $spot["subcata"]));
				if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
					$attr = $doc->createElement('newznab:attr');
					$attr->setAttribute('name', 'category');
					$attr->setAttribute('value', $nabCat[0]);
					$item->appendChild($attr);
				} # if
			} # if

			$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcata']));
			if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
				$attr = $doc->createElement('newznab:attr');
				$attr->setAttribute('name', 'category');
				$attr->setAttribute('value', $nabCat[0]);
				$item->appendChild($attr);

				$attr = $doc->createElement('newznab:attr');
				$attr->setAttribute('name', 'category');
				$attr->setAttribute('value', $nabCat[1]);
				$item->appendChild($attr);
			} # if

			$nabCat = explode("|", $this->Cat2NewznabCat($spot['category'], $spot['subcatb']));
			if ($nabCat[0] != "" && is_numeric($nabCat[0])) {
				$attr = $doc->createElement('newznab:attr');
				$attr->setAttribute('name', 'category');
				$attr->setAttribute('value', $nabCat[0]);
				$item->appendChild($attr);
			} # if

			$attr = $doc->createElement('newznab:attr');
			$attr->setAttribute('name', 'size');
			$attr->setAttribute('value', $spot['filesize']);
			$item->appendChild($attr);

			$attr = $doc->createElement('newznab:attr');
			$attr->setAttribute('name', 'poster');
			$attr->setAttribute('value', $spot['poster'] . '@spot.net (' . $spot['poster'] . ')');
			$item->appendChild($attr);

			$attr = $doc->createElement('newznab:attr');
			$attr->setAttribute('name', 'comments');
			$attr->setAttribute('value', $spot['commentcount']);
			$item->appendChild($attr);

			$this->sendContentTypeHeader('xml');
			echo $doc->saveXML();
		} # if
	} # spotDetails

	function getNzb() {
		if ($this->_params['del'] == "1" && $this->_spotSec->allowed(SpotSecurity::spotsec_keep_own_watchlist, '')) {
			$spot = $this->_db->getFullSpot($this->_params['messageid'], $this->_currentSession['user']['userid']);
			if ($spot['watchstamp'] !== NULL) {
				$this->_db->removeFromWatchList($this->_params['messageid'], $this->_currentSession['user']['userid']);
				$spotsNotifications = new SpotNotifications($this->_db, $this->_settings, $this->_currentSession);
				$spotsNotifications->sendWatchlistHandled('remove', $this->_params['messageid']);
			} # if
		} # if

		header('Location: ' . $this->_tplHelper->makeBaseUrl("full") . '?page=getnzb&action=display&messageid=' . $this->_params['messageid'] . html_entity_decode($this->_tplHelper->makeApiRequestString()));
	} # getNzb

	function caps() {
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$caps = $doc->createElement('caps');
		$doc->appendChild($caps);

		$server = $doc->createElement('server');
		$server->setAttribute('version', '0.1');
		$server->setAttribute('title', 'Spotweb');
		$server->setAttribute('strapline', 'Spotweb API Index');
		$server->setAttribute('email', $this->_currentSession['user']['mail'] . ' (' . $this->_currentSession['user']['firstname'] . ' ' . $this->_currentSession['user']['lastname'] . ')');
		$server->setAttribute('url', $this->_settings->get('spotweburl'));
		$server->setAttribute('image', $this->_settings->get('spotweburl') . 'images/spotnet.gif');
		$caps->appendChild($server);

		$limits = $doc->createElement('limits');
		$limits->setAttribute('max', '500');
		$limits->setAttribute('default', $this->_currentSession['user']['prefs']['perpage']);
		$caps->appendChild($limits);

		if (($this->_settings->get('retention') > 0) && ($this->_settings->get('retentiontype') == 'everything')) {
			$ret = $doc->createElement('retention');
			$ret->setAttribute('days', $this->_settings->get('retention'));
			$caps->appendChild($ret);
		} # if

		$reg = $doc->createElement('registration');
		$reg->setAttribute('available', 'no');
		$reg->setAttribute('open', 'no');
		$caps->appendChild($reg);

		$searching = $doc->createElement('searching');
		$caps->appendChild($searching);

		$search = $doc->createElement('search');
		$search->setAttribute('available', 'yes');
		$searching->appendChild($search);

		$tvsearch = $doc->createElement('tv-search');
		$tvsearch->setAttribute('available', 'yes');
		$tvsearch->setAttribute('supportedParams', 'q,rid,tvmazeid,season,ep');
		$searching->appendChild($tvsearch);

		$moviesearch = $doc->createElement('movie-search');
		$moviesearch->setAttribute('available', 'yes');
		$searching->appendChild($moviesearch);

		$audiosearch = $doc->createElement('audio-search');
		$audiosearch->setAttribute('available', 'yes');
		$searching->appendChild($audiosearch);

		$categories = $doc->createElement('categories');
		$caps->appendChild($categories);

		foreach($this->categories() as $category) {
			$cat = $doc->createElement('category');
			$cat->setAttribute('id', $category['cat']);
			$cat->setAttribute('name', $category['name']);
			$categories->appendChild($cat);

			foreach($category['subcata'] as $name => $subcata) {
				$subCat = $doc->createElement('subcat');
				$subCat->setAttribute('id', $subcata);
				$subCat->setAttribute('name', $name);
				$cat->appendChild($subCat);
			} # foreach
		} # foreach

		$this->sendContentTypeHeader('xml');
		echo $doc->saveXML();
	} # caps

	function Cat2NewznabCat($hcat, $cat, $catZCompanion = "") {
		$result = "-";
		$catList = explode("|", $cat);
		$cat = $catList[0];
		$nr = substr($cat, 1);

		# Als $nr niet gevonden kan worden is dat niet erg, het mag echter
		# geen Notice veroorzaken.
		if (!empty($cat[0])) {
			switch ($cat[0]) {
				case "a"	: $newznabcat = $this->spotAcat2nabcat(); return @$newznabcat[$hcat][$nr]; break;
				case "b"	: $newznabcat = $this->spotBcat2nabcat(); return @$newznabcat[$nr]; break;
				case "z"	: 
					switch($nr) {
						case "1":
							if(!empty($catZCompanion)) {
								$catZCompanionList = explode("|", $catZCompanion);
								$catZCompanion = $catZCompanionList[0];
								
								if(in_array($catZCompanion, $this->spotHdCat())) {
									return 5040;
								}
								elseif(in_array($catZCompanion, $this->spotSdCat())) {
									return 5030;
								}
							}
						break;
						default: 
							$newznabcat = $this->spotZcat2nabcat(); 
							return @$newznabcat[$nr]; 						
						break;
					}

				break;
			} # switch
		} # if

        return '';
	} # Cat2NewznabCat

	function showApiError($errcode=42) {
		switch ($errcode) {
			case 100: $errtext = "Incorrect user credentials"; break;
			case 101: $errtext = "Account suspended"; break;
			case 102: $errtext = "Insufficient priviledges/not authorized"; break;
			case 103: $errtext = "Registration denied"; break;
			case 104: $errtext = "Registrations are closed"; break;
			case 105: $errtext = "Invalid registration (Email Address Taken)"; break;
			case 106: $errtext = "Invalid registration (Email Address Bad Format)"; break;
			case 107: $errtext = "Registration Failed (Data error)"; break;

			case 200: $errtext = "Missing parameter"; break;
			case 201: $errtext = "Incorrect parameter"; break;
			case 202: $errtext = "No such function"; break;
			case 203: $errtext = "Function not available"; break;

			case 300: $errtext = "On TVSearch no q, tvmaze or rid parameter present"; break;
			case 301: $errtext = "IMDB information returned is invalid"; break;
			case 302: $errtext = "Error in fetching spot information"; break;

			case 500: $errtext = "Request limit reached"; break;
			case 501: $errtext = "Download limit reached"; break;
			default: $errtext = "Unknown error"; break;
		} # switch

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$error = $doc->createElement('error');
		$error->setAttribute('code', $errcode);
		$error->setAttribute('description', $errtext);
		$doc->appendChild($error);

		$this->sendContentTypeHeader('xml');
		echo $doc->saveXML();
	} # showApiError

	function categories() {
		return array(
				array('name'		=> 'Console',
					  'cat'			=> '1000',
					  'subcata'		=> array('NDS'		=> '1010',
											 'PSP'		=> '1020',
											 'Wii'		=> '1030',
											 'Xbox'		=> '1040',
											 'Xbox 360'	=> '1050',
											 'PS3'		=> '1080')
				), array('name'		=> 'Movies',
						 'cat'		=> '2000',
						 'subcata'	=> array('SD'		=> '2030',
											 'HD'		=> '2040',
											 'Sport'	=> '2060')
				), array('name'		=> 'Audio',
						 'cat'		=> '3000',
						 'subcata'	=> array('MP3'		=> '3010',
											 'Video'	=> '3020',
											 'Lossless'	=> '3040')
				), array('name'		=> 'PC',
						 'cat'		=> '4000',
						 'subcata'	=> array('Mac'		=> '4030',
											 'Phone'	=> '4040',
											 'Games'	=> '4050')
				), array('name'		=> 'TV',
						 'cat'		=> '5000',
						 'subcata'	=> array('Foreign'	=> '5020',
											'SD'		=> '5030',
											 'HD'		=> '5040',
											 'Other'	=> '5050',
											 'Sport'	=> '5060',
											 'Anime'	=> '5070')
				), array('name'		=> 'XXX',
						 'cat'		=> '6000',
						 'subcata'	=> array('DVD'		=> '6010',
											 'WMV'		=> '6020',
											 'XviD'		=> '6030',
											 'x264'		=> '6040')
				), array('name'		=> 'Other',
						 'cat'		=> '7000',
						 'subcata'	=> array('Ebook'	=> '7020')
				)
		);
	} # categories

	function nabcat2spotcat($cat) {
		switch ($cat) {
			case 1000: return 'cat2_a3,cat2_a4,cat2_a5,cat2_a6,cat2_a7,cat2_a8,cat2_a9,cat2_a10,cat2_a11,cat2_a12';
			case 1010: return 'cat2_a10';
			case 1020: return 'cat2_a5';
			case 1030: return 'cat2_a11';
			case 1040: return 'cat2_a6';
			case 1050: return 'cat2_a7';
			case 1060: return 'cat2_a7';

			case 2000: return 'cat0_z0';
			case 2010:
			case 2030: return 'cat0_a0,cat0_a1,cat0_a2,cat0_a3,cat0_a10,~cat0_z1,~cat0_z2,~cat0_z3';
			case 2040: return 'cat0_a4,cat0_a6,cat0_a7,cat0_a8,cat0_a9,~cat0_z1,~cat0_z2,~cat0_z3';
			case 2060: return 'cat0_d18';

			case 3000: return 'cat1_a';
			case 3010: return 'cat1_a0';
			case 3020: return 'cat0_d13';
			case 3040: return 'cat1_a2,cat1_a4,cat1_a7,cat1_a8';

			case 4000: return 'cat3_a0';
			case 4030: return 'cat3_a1';
			case 4040: return 'cat3_a4,cat3_a5,cat3_a6,cat3_a7';
			case 4050: return 'cat2_a0,cat2_a1,cat2_a2';

			case 5000: return 'cat0_z1';
			case 5020: return 'cat0_z1_a0,cat0_z1_a1,cat0_z1_a2,cat0_z1_a3,cat0_z1_a4,cat0_z1_a6,cat0_z1_a7,cat0_z1_a8,cat0_z1_a9,cat0_z1_a10';
			case 5030: return 'cat0_z1_a0,cat0_z1_a1,cat0_z1_a2,cat0_z1_a3,cat0_z1_a10';
			case 5040: return 'cat0_z1_a4,cat0_z1_a6,cat0_z1_a7,cat0_z1_a8,cat0_z1_a9';
			case 5050: return 'cat0_z1_a0,cat0_z1_a1,cat0_z1_a2,cat0_z1_a3,cat0_z1_a4,cat0_z1_a6,cat0_z1_a7,cat0_z1_a8,cat0_z1_a9,cat0_z1_a10';
			case 5060: return 'cat0_z1_d18';
			case 5070: return 'cat0_z1_d29';

			case 6000: return 'cat0_z3';
			case 6010: return 'cat0_a3,cat0_a10,~cat0_z0,~cat0_z1,~cat0_z2';
			case 6020: return 'cat0_a1,cat0_a8,~cat0_z1,~cat0_z0,~cat0_z1,~cat0_z2';
			case 6030: return 'cat0_a0,~cat0_z0,~cat0_z1,~cat0_z2';
			case 6040: return 'cat0_a4,cat0_a6,cat0_a7,cat0_a8,cat0_a9,~cat0_z0,~cat0_z1,~cat0_z2';

			case 7020: return 'cat0_z2';
		}

        return '';
	} # nabcat2spotcat

	function spotAcat2nabcat() {
		return Array(0 =>
				Array(0 => "2000|2030",
					  1 => "2000|2030",
					  2 => "2000|2030",
					  3 => "2000|2030",
					  4 => "2000|2040",
					  5 => "7000|7020",
					  6 => "2000|2040",
					  7 => "2000|2040",
					  8 => "2000|2040",
					  9 => "2000|2040",
					  10 => "2000|2030"),
			  1 =>
				Array(0	=> "3000|3010",
					  1 => "3000|3010",
					  2 => "3000|3040",
					  3 => "3000|3010",
					  4 => "3000|3040",
					  5 => "3000|3040",
					  6 => "3000|3010",
					  7 => "3000|3040",
					  8 => "3000|3040"),
			  2 =>
				Array(0 => "4000|4050",
					  1 => "4000|4030",
					  2 => "TUX",
					  3 => "PS",
					  4 => "PS2",
					  5 => "1000|1020",
					  6 => "1000|1040",
					  7 => "1000|1050",
					  8 => "GBA",
					  9 => "GC",
					  10 => "1000|1010",
					  11 => "1000|1030",
					  12 => "1000|1080",
					  13 => "4000|4040",
					  14 => "4000|4040",
					  15 => "4000|4040",
					  16 => "3DS"),
			  3 =>
				Array(0 => "4000|4020",
					  1 => "4000|4030",
					  2 => "TUX",
					  3 => "OS/2",
					  4 => "4000|4040",
					  5 => "NAV",
					  6 => "4000|4040",
					  7 => "4000|4040")
			);
	} # spotAcat2nabcat

	function spotBcat2nabcat() {
		return Array(0 => "",
					 1 => "",
					 2 => "",
					 3 => "",
					 4 => "5000",
					 5 => "",
					 6 => "5000",
					 7 => "",
					 8 => "",
					 9 => "",
					 10 => "");
	} # spotBcat2nabcat
	
	function spotZcat2nabcat() {
		return Array (
			3 => "6000"
		);
	} # spotZcat2nabcat
	
	function spotHdCat() {
		return Array("a4", "a6", "a7", "a8", "a9");
	} # spotHdCat
	
	function spotSdCat() {
		return Array("a1", "a2", "a3", "a10");
	} # spotSdCat

} # class SpotPage_api
