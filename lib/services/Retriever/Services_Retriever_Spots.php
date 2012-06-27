<?php
class Services_Retriever_Spots extends Services_Retriever_Base {
		private $_rsakeys;
		private $_retrieveFull;
		private $_prefetch_image;
		private $_prefetch_nzb;

		/**
		 * Server is the server array we are expecting to connect to
		 * db - database object
		 */
		function __construct($textServer, $binServer, SpotDb $db, SpotSettings $settings, $debug, $retro) {
			parent::__construct($textServer, $binServer, $db, $settings, $debug, $retro);
			
			$this->_rsakeys = $this->_settings->get('rsa_keys');
			$this->_retrieveFull = $this->_settings->get('retrieve_full');
			$this->_prefetch_image = $this->_settings->get('prefetch_image');
			$this->_prefetch_nzb = $this->_settings->get('prefetch_nzb');
		} # ctor

		/*
		 * Returns the status in either xml or text format 
		 */
		function displayStatus($cat, $txt) {
				switch($cat) {
					case 'start'			: echo "Retrieving new Spots from server " . $txt . "..." . PHP_EOL; break;
					case 'lastretrieve'		: echo strftime("Last retrieve at %c", $txt) . PHP_EOL; break;
					case 'done'				: echo "Finished retrieving spots." . PHP_EOL . PHP_EOL; break;
					case 'groupmessagecount': echo "Appr. Message count: 	" . $txt . "" . PHP_EOL; break;
					case 'firstmsg'			: echo "First message number:	" . $txt . "" . PHP_EOL; break;
					case 'lastmsg'			: echo "Last message number:	" . $txt . "" . PHP_EOL; break;
					case 'curmsg'			: echo "Current message:	" . $txt . "" . PHP_EOL; break;
					case 'progress'			: echo "Retrieving " . $txt; break;
					case 'hdrparsed'		: echo " (parsed " . $txt . " headers, "; break;
					case 'fullretrieved'	: echo $txt . " full, "; break;
					case 'verified'			: echo "verified " . $txt . ", "; break;
					case 'modcount'			: echo "moderated " . $txt . ", "; break;
					case 'skipcount'		: echo "skipped " . $txt . " of "; break;
					case 'loopcount'		: echo $txt . " total messages)"; break;
					case 'timer'			: echo " in " . $txt . " seconds" . PHP_EOL; break;
					case 'totalprocessed'	: echo "Processed a total of " . $txt . " spots" . PHP_EOL; break;
					case 'searchmsgid'		: echo "Looking for articlenumber for messageid" . PHP_EOL; break;
					case 'searchmsgidstatus': echo "Searching from " . $txt . PHP_EOL; break;
					case ''					: echo PHP_EOL; break;
					
					default					: echo $cat . $txt;
				} # switch
		} # displayStatus
		
		/*
		 * Remove any extraneous reports from the database because we assume
		 * the highest messgeid in the database is the latest on the server.
		 */
		function updateLastRetrieved($highestMessageId) {
			$this->debug('Highest messageid found: ' . $highestMessageId);

			/*
			 * Remove any extraneous spots from the database because we assume
			 * the highest messgeid in the database is the latest on the server.
			 *
			 * If the server is marked as buggy, the last 'x' amount of spot are
			 * always checked so we do not have to do this 
			 */
			if (!$this->_textServer['buggy']) {
				$this->_db->removeExtraSpots($highestMessageId);
			} # if
		} # updateLastRetrieved
		
		/*
		 * Actually process the retrieved headers from XOVER
		 */
		function process($hdrList, $curMsg, $endMsg, $timer) {
			$this->displayStatus("progress", ($curMsg) . " till " . ($endMsg));

			$spotParser = new Services_Format_Parsing();
			$signedCount = 0;
			$hdrsRetrieved = 0;
			$fullsRetrieved = 0;
			$msgCounter = 0;
			$modCount = 0;
			$skipCount = 0;
			$lastProcessedId = '';
			$fullSpotDbList = array();
			$spotDbList = array();
			$moderationList = array();
			$processingStartTime = time();

			/*
			 * Determine the cutoff date (unixtimestamp) from whereon we do not want to 
			 * load the spots
			 */
			if ($this->_settings->get('retention') > 0) {
				$retentionStamp = time() - ($this->_settings->get('retention') * 24 * 60 * 60);
			} else {
				$retentionStamp = 0;
			} # else
			$this->debug('retentionStamp=' . $retentionStamp);
			$this->debug('hdrList=' . serialize($hdrList));
			
			/**
			 * We ask the database to match our messageid's we just retrieved with
			 * the list of id's we have just retrieved from the server
			 */
			$dbIdList = $this->_db->matchSpotMessageIds($hdrList);

			$this->debug('dbIdList=' . serialize($dbIdList));

			# if we need to fetch images or nzb files, we need several service objects
			if ($this->_retrieveFull && ($this->_prefetch_image) || ($this->_prefetch_nzb)) {
				/*
				 * NNTP Spot Reading engine
				 */
				$this->_svcNntpTextReading = new Services_Nntp_SpotReading($this->_svcNntpText);
				$this->_svcNntpBinReading = new Services_Nntp_SpotReading($this->_svcNntpBin);

				$svcProvNzb = new Services_Providers_Nzb($this->_db->_cacheDao,
														   $this->_svcNntpBinReading);
				$svcProvImage = new Services_Providers_SpotImage(new Services_Providers_Http($this->_db->_cacheDao),
																	  $this->_svcNntpBinReading,
											  						  $this->_db->_cacheDao);
			} # if



			foreach($hdrList as $msgheader) {
				$msgCounter++;
				$this->debug('foreach-loop, start. msgId= ' . $msgCounter);

				/* 
				 * Keep te usenet server alive when processing is slow.
				 */
				if (($processingStartTime - time()) > 30) {
					$this->_svcNntpText->sendNoop();
					$this->_svcNntpBin->sendNoop();

					$processingStartTime = time();
				} # if

				/*
				 * We keep track whether we actually fetched this header and fullspot
				 * to add it to the database, because only then we can update the
				 * titel from the spots title or rely on our database to fetch
				 * the fullspot
				 */
				$didFetchHeader = false;
				$didFetchFullSpot = false;
				
				# Reset timelimit
				set_time_limit(120);

				# messageid to check
				$msgId = substr($msgheader['Message-ID'], 1, -1);
				
				/*
				 * We prepare some variables to we don't have to perform an array
				 * lookup for each check and the code is easier to read.
				 */
				$header_isInDb = isset($dbIdList['spot'][$msgId]);
				$fullspot_isInDb = isset($dbIdList['fullspot'][$msgId]);

				/*
				 * If the spotheader is not yet added to the database, parse the header
				 * information.
				 *
				 * If the header is present, but we don't have the fullspot yet or we are
				 * running in 'retro' mode, parse the header as well because some fields
				 * are only in the header and not in the full.
				 * 
				 * We need some of those fields (for example KeyID)
				 */
				if (!$header_isInDb || ((!$fullspot_isInDb || $this->_retro) && $this->_retrieveFull)) {
					$hdrsRetrieved++;
					$this->debug('foreach-loop, parsingXover, start. msgId= ' . $msgCounter);
					$spot = $spotParser->parseHeader($msgheader['Subject'], 
													 $msgheader['From'], 
													 $msgheader['Date'],
													 $msgheader['Message-ID'],
													 $this->_rsakeys);
					$this->debug('foreach-loop, parsingXover, done. msgId= ' . $msgCounter);

					/*
					 * When a parse error occured, we ignore the spot, also unverified
					 * spots are ignored
					 */
					if (($spot === false) || (!$spot['verified'])){
						continue;
					} # if

					/*
					 * Special moderator commands always have keyid 2
					 */
					if ($spot['keyid'] == 2) {
						$commandAr = explode(' ', strtolower($spot['title']));
						$validCommands = array('delete', 'dispose', 'remove');

						# is this one of the defined valid commands?
						if (in_array($commandAr[0], $validCommands) !== false) {
							$moderationList[] = $commandAr[1];
							$modCount++;
						} # if
						
					} else {
						/*
						 * Don't add spots older than specified for the retention stamp
						 */
						if (($retentionStamp > 0) && ($spot['stamp'] < $retentionStamp) && ($this->_settings->get('retentiontype') == 'everything')) {
							continue;
						} elseif ($spot['stamp'] < $this->_settings->get('retrieve_newer_than')) { 
							$skipCount++;
						} else {
							/*
							 * Do we have the header in the database? If not, lets add it
							 */
							if (!$header_isInDb) {
								$spotDbList[] = $spot;

								/*
								 * Some buggy NNTP servers give us the same messageid
								 * in one XOVER statement, hence we update the list of
								 * messageid's we already have retrieved and are ready
								 * to be added to the database
								 */
								$dbIdList['spot'][$msgId] = 1;
								$header_isInDb = true;
								$lastProcessedId = $msgId;
								$didFetchHeader = true;

								if ($spot['wassigned']) {
									$signedCount++;
								} # if
							} # if 
						} # if
					
					} # else
				} else {
					$lastProcessedId = $msgId;
				} # else


				/*
				 * We don't want to retrieve the fullspot if we don't have the header
				 * in the database. Because we try to add headers in the above code we just have
				 * to check if the header is in the database.
				 *
				 * We cannot collapse this code with the header fetching code because we want to
				 * be able to add the fullspot to a system after all the headers are retrieved
				 */
				if ($header_isInDb &&			# header must be in the db
					!$fullspot_isInDb)	 		# but the fullspot should not
				   {
					/*
					 * Don't add older fullspots than specified for the retention stamp
					 */
					if (($retentionStamp > 0) && (strtotime($msgheader['Date']) < $retentionStamp)) {
						continue;
					} # if

					if ($this->_retrieveFull) {
						$fullSpot = array();
						try {
							$fullsRetrieved++;
							$this->debug('foreach-loop, getFullSpot, start. msgId= ' . $msgId);
							$fullSpot = $this->_svcNntpReading->readFullSpot($msgId);
							$this->debug('foreach-loop, getFullSpot, done. msgId= ' . $msgId);
							
							# add this spot to the database
							$fullSpotDbList[] = $fullSpot;
							$fullspot_isInDb = true;
							$didFetchFullSpot = true;
							
							/*
							 * Some buggy NNTP servers give us the same messageid
							 * in once XOVER statement, hence we update the list of
							 * messageid's we already have retrieved and are ready
							 * to be added to the database
							 */
							$dbIdList['fullspot'][$msgId] = 1;
							
							/* 
							 * Overwrite the spots' title because the fullspot contains the title in
							 * UTF-8 format.
							 * We also overwrite the spotterid from the spotsfull because the spotterid
							 * is only in the header in more recent spots.
							 */
							if ($didFetchHeader) {
								$spotDbList[count($spotDbList) - 1]['title'] = $fullSpot['title'];
								$spotDbList[count($spotDbList) - 1]['spotterid'] = $fullSpot['spotterid'];
							} # if
						} 
						catch(ParseSpotXmlException $x) {
							; # swallow error
						} 
						catch(Exception $x) {
							/**
							 * Sometimes we get an 'No such article' error for a header we just retrieved,
							 * if we want to retrieve the full article. This is messed up, but let's just
							 * swallow the error
							 */
							if ($x->getCode() == 430) {
								;
							} 
							# if the XML is unparseable, don't bother complaining about it
							elseif ($x->getMessage() == 'String could not be parsed as XML') {
								;
							} else {
								throw $x;
							} # else
						} # catch
						
					} # if retrievefull
				} # if fullspot is not in db yet

				if ($this->_retrieveFull && $header_isInDb && ($this->_prefetch_image || $this->_prefetch_nzb)) {
					try {
						/*
						 * If we are running in 'retro' mode, it is possible both the header and spot are in the
						 * database already, however -- we need the information from the fullspot so we retrieve it
						 * again
						 */
						if (!$didFetchFullSpot) {
							$fullSpot = $this->_db->getFullSpot($msgId, SPOTWEB_ANONYMOUS_USERID);
							$fullSpot = array_merge($spotParser->parseFull($fullSpot['fullxml']), $fullSpot);
						} # if

						/*
						 * Prefetch (cache) the spots' image
						 */
						if ($this->_prefetch_image) {
							/*
							 * If the spot is older than 30 days, and the image is on the web, we do not 
							 * prefetch the image.
							 */
							if (is_array($fullSpot['image']) || ($fullSpot['stamp'] > (int) time()-30*24*60*60)) {
								$this->debug('foreach-loop, getImage(), start. msgId= ' . $msgId);
								$svcProvImage->fetchSpotImage($fullSpot);
								$this->debug('foreach-loop, getImage(), done. msgId= ' . $msgId);
							} # if
						} # if

						/*
						 * Prefetch (cache) the spots' NZB file
						 */
						if ($this->_prefetch_nzb) {
							/*
							 * Only do so if we can expect an NZB file
							 */
							if (!empty($fullSpot['nzb']) && $fullSpot['stamp'] > 1290578400) {
								$this->debug('foreach-loop, getNzb(), start. msgId= ' . $msgId);
								$svcProvNzb>fetchNzb($fullSpot);
								$this->debug('foreach-loop, getNzb(), done. msgId= ' . $msgId);
							} # if
						} # if
					}
					catch(ParseSpotXmlException $x) {
						; # swallow error
					} 
					catch(Exception $x) {
						/**
						 * Sometimes we get an 'No such article' error for a header we just retrieved,
						 * if we want to retrieve the full article. This is messed up, but let's just
						 * swallow the error
						 */
						if ($x->getCode() == 430) {
							;
						} 
							# if the XML is unparseable, don't bother complaining about it
						elseif ($x->getMessage() == 'String could not be parsed as XML') {
							;
						} else {
							throw $x;
						} # else
					} # catch
				} # if prefetch image and/or nzb

				$this->debug('foreach-loop, done. msgId= ' . $msgCounter);
			} # foreach

			if (count($hdrList) > 0) {
				$this->displayStatus("hdrparsed", $hdrsRetrieved);
				$this->displayStatus("fullretrieved", $fullsRetrieved);
				$this->displayStatus("verified", $signedCount);
				$this->displayStatus("modcount", $modCount);
				$this->displayStatus("skipcount", $skipCount);
				$this->displayStatus("loopcount", count($hdrList));
			} else {
				$this->displayStatus("hdrparsed", 0);
				$this->displayStatus("fullretrieved", 0);
				$this->displayStatus("verified", 0);
				$this->displayStatus("modcount", 0);
				$this->displayStatus("skipcount", 0);
				$this->displayStatus("loopcount", 0);
			} # else

			/* 
			 * Add the spots to the database and update the last article
			 * number found
			 */
			$this->_db->addSpots($spotDbList, $fullSpotDbList);
			$this->debug('added Spots, spotDbList=' . serialize($spotDbList));
			$this->debug('added Spots, fullSpotDbList=' . serialize($fullSpotDbList));

			/*
			 * Actually act on the moderation settings. We cannot process this inline
			 * because a spot can be added and moderated within the same iteration
			 */
			switch($this->_settings->get('spot_moderation')) {
				case 'disable'	: break;
				case 'markspot'	: {
					$this->_db->markCommentsModerated($moderationList); 
					$this->_db->markSpotsModerated($moderationList); 
					
					break;
				} # case 'markspot' 
				default			: { 
					$this->_db->removeSpots($moderationList); 
					$this->_db->removeComments($moderationList);
					
					break;
				} # default
			} # switch
			
			# update the maximum article id
			if ($this->_retro) {
				$this->_db->setMaxArticleid('spots_retro', $endMsg);
			} else {
				$this->_db->setMaxArticleid($this->_textServer['host'], $endMsg);
			} # if
			$this->debug('loop finished, setMaxArticleId=' . serialize($endMsg));
			
			$this->displayStatus("timer", round(microtime(true) - $timer, 2));

			return array('count' => count($hdrList), 'headercount' => $hdrsRetrieved, 'lastmsgid' => $lastProcessedId);
		} # process()

		/*
		 * returns the name of the group we are expected to retrieve messages from
		 */
		function getGroupName() {
			return array('text' => $this->_settings->get('hdr_group'),
						 'bin' => $this->_settings->get('hdr_group'));
		} # getGroupName

		/*
		 * Highest articleid for the implementation in the database
		 */
		function getMaxArticleId() {
			if ($this->_retro) {
				return $this->_db->getMaxArticleid('spots_retro');
			} else {
				return $this->_db->getMaxArticleid($this->_textServer['host']);
			} # if
		} # getMaxArticleId
		
		/*
		 * Returns the highest messageid in the database
		 */
		function getMaxMessageId() {
			return $this->_db->getMaxMessageId('headers');
		} # getMaxMessageId

		
} # Services_Retriever_Spots
