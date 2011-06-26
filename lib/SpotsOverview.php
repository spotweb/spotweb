<?php
/*
 * Vormt basically de koppeling tussen DB en NNTP, waarbij de db als een soort
 * cache dient
 */
class SpotsOverview {
	private $_db;
	private $_settings;

	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor
	
	/*
	 * Geef een volledig Spot array terug
	 */
	function getFullSpot($msgId, $ourUserId, $nntp) {
		$fullSpot = $this->_db->getFullSpot($msgId, $ourUserId);

		if (empty($fullSpot)) {
			# Vraag de volledige spot informatie op -- dit doet ook basic
			# sanity en validatie checking
			$fullSpot = $nntp->getFullSpot($msgId);
			$this->_db->addFullSpot($fullSpot);
			
			# we halen de fullspot opnieuw op zodat we de 'xover' informatie en de 
			# niet xover informatie in 1 hebben
			$fullSpot = $this->_db->getFullSpot($msgId, $ourUserId);
		} # if

		$spotParser = new SpotParser();
		$fullSpot = array_merge($spotParser->parseFull($fullSpot['fullxml']), $fullSpot);
		
		/*
		 * Als je een fullspot ophaalt, maar er is nog gen 'spot' entry, dan blijf je een
		 * lege spot terugkrijgen omdat de join misgaat. Omdat dit verwarring op kan leveren
		 * gooien we dan een exception
		 */
		if (empty($fullSpot)) {
			throw new Exception("Spot is not in our Spotweb database");
		} # if
		
		return $fullSpot;
	} # getFullSpot

	/*
	 * Callback functie om enkel verified 'iets' terug te geven
	 */
	function cbVerifiedOnly($x) {
		return $x['verified'];
	} # cbVerifiedOnly
	
	/*
	 * Geef de lijst met comments terug 
	 */
	function getSpotComments($msgId, $nntp, $start, $length) {
		if (!$this->_settings->get('retrieve_comments')) {
			return array();
		} # if
	
		# Bereken wat waardes zodat we dat niet steeds moeten doen
		$totalCommentsNeeded = ($start + $length);
		
		SpotTiming::start(__FUNCTION__);

		# vraag een lijst op met comments welke in de database zitten en
		# als er een fullcomment voor bestaat, vraag die ook meteen op
		$fullComments = $this->_db->getCommentsFull($msgId);
		
		# Nu gaan we op zoek naar het eerste comment dat nog volledig opgehaald
		# moet worden. Niet verified comments negeren we.
		$haveFullCount = 0;
		$lastHaveFullOffset = -1;
		$retrievedVerified = 0;
		$fullCommentsCount = count($fullComments);
		for ($i = 0; $i < $fullCommentsCount; $i++) {
			if ($fullComments[$i]['havefull']) {
				$haveFullCount++;
				$lastHaveFullOffset = $i;
				
				if ($fullComments[$i]['verified']) {
					$retrievedVerified++;
				} # if
			} # if
		} # for
		
		# en haal de overgebleven comments op van de NNTP server
		if ($retrievedVerified < $totalCommentsNeeded) {
			# Als we de comments maar in delen moeten ophalen, gaan we loopen tot we
			# net genoeg comments hebben. We moeten wel loopen omdat we niet weten 
			# welke comments verified zijn tot we ze opgehaald hebben
			if (($start > 0) || ($length > 0)) {
				$newComments = array();
			
				# en ga ze ophalen
				while (($retrievedVerified < $totalCommentsNeeded) && ( ($lastHaveFullOffset) < count($fullComments) )) {
					SpotTiming::start(__FUNCTION__. ':nntp:getComments()');
					$tempList = $nntp->getComments(array_slice($fullComments, $lastHaveFullOffset + 1, $length));
					SpotTiming::stop(__FUNCTION__ . ':nntp:getComments()', array(array_slice($fullComments, $lastHaveFullOffset + 1, $length), $start, $length));
				
					$lastHaveFullOffset += $length;
					foreach($tempList as $comment) {
						$newComments[] = $comment;
						if ($comment['verified']) {
							$retrievedVerified++;
						} # if
					} # foreach
				} # while
			} else {
				$newComments = $nntp->getComments(array_slice($fullComments, $lastHaveFullOffset + 1, count($fullComments)));
			} # else
			
			# voeg ze aan de database toe
			$this->_db->addCommentsFull($newComments);
			
			# en voeg de oude en de nieuwe comments samen
			$fullComments = $this->_db->getCommentsFull($msgId);
		} # foreach
		
		# filter de comments op enkel geverifieerde comments
		$fullComments = array_filter($fullComments, array($this, 'cbVerifiedOnly'));

		# geef enkel die comments terug die gevraagd zijn. We vragen wel alles op
		# zodat we weten welke we moeten negeren.
		if (($start > 0) || ($length > 0)) {
			$fullComments = array_slice($fullComments , $start, $length);
		} # if
		
		# omdat we soms array elementen unsetten, is de array niet meer
		# volledig oplopend. We laten daarom de array hernummeren
		SpotTiming::stop(__FUNCTION__, array($msgId, $start, $length));
		return $fullComments;
	} # getSpotComments()
	
	/* 
	 * Geef de NZB file terug
	 */
	function getNzb($msgIdList, $nntp) {
		return $nntp->getNzb($msgIdList);
	} # getNzb

	/*
	 * Laad de spots van af positie $start, maximaal $limit spots.
	 *
	 * $parsedSearch is een array met velden, filters en sorteringen die 
	 * alles bevat waarmee SpotWeb kan filteren. 
	 */
	function loadSpots($ourUserId, $start, $limit, $parsedSearch) {
		SpotTiming::start(__FUNCTION__);
		
		# en haal de daadwerkelijke spots op
		$spotResults = $this->_db->getSpots($ourUserId, $start, $limit, $parsedSearch, false);

		$spotCnt = count($spotResults['list']);
		for ($i = 0; $i < $spotCnt; $i++) {
			# We forceren category naar een integer, sqlite kan namelijk een lege
			# string terug ipv een category nummer
			$spotResults['list'][$i]['category'] = (int) $spotResults['list'][$i]['category'];
			
			# We trekken de lijst van subcategorieen uitelkaar 
			$spotResults['list'][$i]['subcatlist'] = explode("|", 
							$spotResults['list'][$i]['subcata'] . 
							$spotResults['list'][$i]['subcatb'] . 
							$spotResults['list'][$i]['subcatc'] . 
							$spotResults['list'][$i]['subcatd'] . 
							$spotResults['list'][$i]['subcatz']);
		} # foreach

		SpotTiming::stop(__FUNCTION__, array($spotResults));
		return $spotResults;
	} # loadSpots()

	/*
	 * Bereid een string met daarin categorieen voor en 'expand' 
	 * die naar een complete string met alle subcategorieen daarin
	 */
	private function prepareCategorySelection($dynaList) {
		$strongNotList = array();
		$categoryList = array();
		
		# 
		# De Dynatree jquery widget die we gebruiken haalt zijn data uit ?page=catsjson,
		# voor elke node in de boom geven wij een key mee.
		# Stel je de boom als volgt voor, met tussen haakjes de unieke key:
		#
		# - Beeld (cat0)
		# +-- Formaat (cat0_a)
		# +---- DivX (cat0_a0)
		# +---- WMV (cat0_a1)
		# +-- Bron (cat0_b)
		# - Geluid (cat1)
		# +-- Formaat (cat1_a)
		# 
		# Oftewel - je hebt een hoofdcategory nummer, daaronder heb je een subcategory type (a,b,c etc),
		# en daaronder heb je dan weer een nummer welke subcategory het is.
		#
		# Als je in bovenstaand voorbeeld dus DivX wilt selecteren, dan is de keywaarde simpelweg cat0_a0, 
		# wil je echter heel 'Beeld' selecteren dan is 'cat0' al genoeg. Als je echter in de Dynatree boom
		# zelf het item 'Beeld' zou selecteren, dan zal Dynatree de verschillende items doorsturen als
		# individuele keys, oftewel: cat0_a0,cat0_a1, etc etc.
		#
		# Als we gebruikers handmatig de category willen laten opgeven (bv. door een entry in settings.php)
		# dan is het bijzonder onhandig als ze al die categorieen individueel moeten opgeven. Om dit op te
		# lossen hebben we een aantal shorthands toegevoegd aan de filter taal welke dan door Spotweb zelf
		# weer 'uitgepakt' worden naar een volledige zoekopdracht.
		#
		# In een 'settings-zoekopdracht' zijn de volgende shortcuts toegestaan voor het automatisch uitvouwen van
		# de boom:
		#
		# cat0						- Zal uitgebreid worden naar alle subcategorieen van category 0
		# cat0_a					- Zal uitgebreid worden naar alle subcategorieen 'A' van category 0.
		# !cat0_a1					- Zal cat0_a1 verwijderen uit de lijst (volgorde van opgeven is belangrijk)
		# ~cat0_a1					- 'Verbied' dat een spot in cat0_a1 zit
		#
		# Intern werken we dus alleen met de gehele lijst van subcategorieen.
		#
		# In deze functie herbouwen we de categorylijst naar een nieuwe lijst met alle categorieen welke mogelijk
		# zijn.
		$newTreeQuery = '';
		
		# We lopen nu door elk item in de lijst heen, en expanden die eventueel naar
		# een volledige category met subcategorieen indien nodig.
		$dynaListCount = count($dynaList);
		for($i = 0; $i < $dynaListCount; $i++) {
			# De opgegeven category kan in twee soorten voorkomen:
			#     cat1_a			==> Alles van cat1, en daar alles van 'a' selecteren
			#	  cat1				==> Heel cat1 selecteren
			#
			# Omdat we in deze code de dynatree emuleren, voeren we deze lelijke hack uit.
			if ((strlen($dynaList[$i]) == 6) || (strlen($dynaList[$i]) == 4)) {
				$hCat = (int) substr($dynaList[$i], 3, 1);
				
				# was een subcategory gespecificeerd?
				if (strlen($dynaList[$i]) == 6) {
					$subCatSelected = substr($dynaList[$i], 5);
				} else {
					$subCatSelected = '*';
				} # else

				#
				# creeer een string die alle subcategories bevat
				#
				# we loopen altijd door alle subcategorieen heen zodat we zowel voor complete category selectie
				# als voor enkel subcategory selectie dezelfde code kunnen gebruiken.
				#
				$tmpStr = '';
				foreach(SpotCategories::$_categories[$hCat] as $subCat => $subcatValues) {
				
					if (($subCat == $subCatSelected) || ($subCatSelected == '*')) {
						foreach(SpotCategories::$_categories[$hCat][$subCat] as $x => $y) {
							$tmpStr .= ",cat" . $hCat . "_" . $subCat . $x;
						} # foreach
					} # if
				} # foreach

				$newTreeQuery .= $tmpStr;
			} elseif (substr($dynaList[$i], 0, 1) == '!') {
				# als het een NOT is, haal hem dan uit de lijst
				$newTreeQuery = str_replace(substr($dynaList[$i], 1) . ",", "", $newTreeQuery);
			} elseif (substr($dynaList[$i], 0, 1) == '~') {
				# als het een STRONG NOT is, zorg dat hij in de lijst blijft omdat we die moeten
				# meegeven aan de nextpage urls en dergelijke.
				$newTreeQuery .= "," . $dynaList[$i];
				
				# en voeg hem toe aan een strong NOT list (~cat0_d12)
				$strongNotTmp = explode("_", $dynaList[$i]);
				$strongNotList[(int) substr($strongNotTmp[0], 4)][] = $strongNotTmp[1];
			} else {
				$newTreeQuery .= "," . $dynaList[$i];
			} # else
		} # for
		if ($newTreeQuery[0] == ",") { $newTreeQuery = substr($newTreeQuery, 1); }

		#
		# Vanaf hier hebben we de geprepareerde lijst - oftewel de lijst met categorieen 
		# die al helemaal in het formaat is zoals Dynatree hem ons ook zou aanleveren.
		# 
		# We vertalen de string met subcategorieen hier netjes naar een array met alle
		# individuele subcategorieen zodat we die later naar SQL kunnen omzetten.
		$dynaList = explode(',', $newTreeQuery);

		foreach($dynaList as $val) {
			if (substr($val, 0, 3) == 'cat') {
				# 0e element is hoofdcategory
				# 1e element is category
				$val = explode('_', (substr($val, 3) . '_'));

				$catVal = $val[0];
				$subCatIdx = substr($val[1], 0, 1);
				$subCatVal = substr($val[1], 1);

				if (count($val) >= 3) {
					$categoryList['cat'][$catVal][$subCatIdx][] = $subCatVal;
				} # if
			} # if
		} # foreach
		
		return array($categoryList, $strongNotList);
	} # prepareCategorySelection

	/*
	 * Converteert een lijst met subcategorieen 
	 * naar een lijst met daarin SQL where filters
	 */
	private function categoryListToSql($categoryList) {
		$categorySql = array();

		# controleer of de lijst geldig is
		if ((!isset($categoryList['cat'])) || (!is_array($categoryList['cat']))) {
			return $categorySql;
		} # if
		
		# 
		# We vertalen nu de lijst met sub en hoofdcategorieen naar een SQL WHERE statement, we 
		# doen dit in twee stappen waarbij de uiteindelijke category filter een groot filter is.
		# 
		foreach($categoryList['cat'] as $catid => $cat) {
			$catid = (int) $catid;
			$tmpStr = "((category = " . (int) $catid . ")";

			#
			# Voor welke category die we hebben, gaan we alle subcategorieen 
			# af en proberen die vervolgens te verwerken.
			#
			if ((is_array($cat)) && (!empty($cat))) {
				#
				# Uiteraard is een LIKE query voor category search niet super schaalbaar
				# maar omdat deze webapp sowieso niet bedoeld is voor grootschalig gebruik
				# moet het meer dan genoeg zijn
				#
				$subcatItems = array();
				foreach($cat as $subcat => $subcatItem) {
					$subcatValues = array();
					
					foreach($subcatItem as $subcatValue) {
						#
						# category a en z mogen maar 1 keer voorkomen, dus dan kunnen we gewoon
						# equality ipv like doen
						#
						if (in_array($subcat, array('a', 'z'))) {
							$subcatValues[] = "(subcat" . $subcat . " = '" . $subcat . $subcatValue . "|') ";
						} elseif (in_array($subcat, array('b', 'c', 'd'))) {
							$subcatValues[] = "(subcat" . $subcat . " LIKE '%" . $subcat . $subcatValue . "|%') ";
						} # if
					} # foreach
					
					# 
					# We voegen alle subcategorieen items binnen dezelfde subcategory en binnen dezelfde category
					# (bv. alle formaten films) samen met een OR. Dus je kan kiezen voor DivX en WMV als formaat.
					#
					$subcatItems[] = " (" . join(" OR ", $subcatValues) . ") ";
				} # foreach subcat

				#
				# Hierna voegen we binnen de hoofdcategory (Beeld,Geluid), de subcategorieen filters die hierboven
				# zijn samengesteld weer samen met een AND, bv. genre: actie, type: divx.
				#
				# Je krijgt dus een filter als volgt:
				#
				# (((category = 0) AND ( ((subcata = 'a0|') ) AND ((subcatd LIKE '%d0|%')
				# 
				# Dit zorgt er voor dat je wel kan kiezen voor meerdere genres, maar dat je niet bv. een Linux actie game
				# krijgt (ondanks dat je Windows filterde) alleen maar omdat het een actie game is waar je toevallig ook
				# op filterde.
				#
				$tmpStr .= " AND (" . join(" AND ", $subcatItems) . ") ";
			} # if
			
			# Sluit het haakje af
			$tmpStr .= ")";
			$categorySql[] = $tmpStr;
		} # foreach
		
		return $categorySql;
	} # categoryListToSql 
	
	/*
	 * Zet een lijst met "strong nots" om naar de daarbij
	 * behorende SQL where statements
	 */
	private function strongNotListToSql($strongNotList) {
		$strongNotSql = array();
		
		if (empty($strongNotList)) {
			return array();
		} # if

		#
		# Voor elke strong not die we te zien krijgen, creer de daarbij
		# behorende SQL WHERE filter
		#
		foreach(array_keys($strongNotList) as $strongNotCat) {
			foreach($strongNotList[$strongNotCat] as $strongNotSubcat) {
				$subcat = $strongNotSubcat[0];

				# category a en z mogen maar 1 keer voorkomen, dus dan kunnen we gewoon
				# equality ipv like doen
				if (in_array($subcat, array('a', 'z'))) { 
					$strongNotSql[] = "((Category <> " . (int) $strongNotCat . ") OR (subcat" . $subcat . " <> '" . $this->_db->safe($strongNotSubcat) . "|'))";
				} elseif (in_array($subcat, array('b', 'c', 'd'))) { 
					$strongNotSql[] = "((Category <> " . (int) $strongNotCat . ") OR (NOT subcat" . $subcat . " LIKE '%" . $this->_db->safe($strongNotSubcat) . "|%'))";
				} # if
			} # foreach				
		} # forEach

		return $strongNotSql;
	} # strongNotListToSql

	/*
	 * Prepareert de filter values naar een altijd juist formaat 
	 */
	private function prepareFilterValues($search) {
		$filterValueList = array();
		
		# We hebben drie soorten filters:
		#		- Oude type waarin je een search[type] hebt met als waarden stamp,titel,tag etc en search[text] met 
		#		  de waarde waar je op wilt zoeken. Dit beperkt je tot maximaal 1 type filter wat het lastig maakt.
		#
		# 		  We converteren deze oude type zoekopdrachten automatisch naar het nieuwe type.
		#
		#		- Nieuw type waarin je een search[value] array hebt, hierin zitten values in de vorm: type:operator:value, dus
		#		  bijvoorbeeld tag:=:spotweb. Er is ook een shorthand beschikbaar, als je de operator weglaat (dus: tag:spotweb),
		#		  nemen we aan dat de EQ operator bedoelt is.
		#
		#		- Speciale soorten lijsten - er zijn een aantal types welke een speciale betekenis hebben:
		#				New:0 			(nieuwe posts)
		#				Downloaded:0 	(spots welke gedownload zijn door deze account)
		#				Watch:0 		(spots die op de watchlist staan van deze account)
		#				Seen:0 			(spots die al geopend zijn door deze account)
		#				
		#
		if (isset($search['type'])) {
			if (!isset($search['text'])) {
				$search['text'] = '';
			} # if
			
			$search['value'] = array();
			$search['value'][] = $search['type'] . ':=:' . $search['text'];
			unset($search['type']);
		} # if

		# Zorg er voor dat we altijd een array hebben waar we door kunnen lopen
		if ((!isset($search['value'])) || (!is_array($search['value']))) {
			$search['value'] = array();
		} # if

		# en we converteren het nieuwe type (field:operator:value) naar een array zodat we er makkelijk door kunnen lopen
		foreach($search['value'] as $value) {
			if (!empty($value)) {
				$tmpFilter = explode(':', $value);
				
				# als er geen comparison operator is opgegeven, dan
				# betekent dat een '=' operator, dus fix de array op
				# die manier.
				if (count($tmpFilter) < 3) {
					$tmpFilter = array($tmpFilter[0],
									   '=',
									   $tmpFilter[1]);
				} # if
				
				# maak de daadwerkelijke filter
				$filterValueTemp = Array('fieldname' => $tmpFilter[0],
										 'operator' => $tmpFilter[1],
										 'value' => join(":", array_slice($tmpFilter, 2)));
										 
				# en creeer een filtervaluelist, we checken eeerst
				# of een gelijkaardig item niet al voorkomt in de lijst
				# met filters - als je namelijk twee keer dezelfde filter
				# toevoegt wil MySQL wel eens onverklaarbaar traag worden
				if (!in_array($filterValueTemp, $filterValueList)) {
					$filterValueList[] = $filterValueTemp;
				} # if
			} # if
		} # for
		
		return $filterValueList;
	} # prepareFilterValues
	
	/*
	 * Converteert meerdere user opgegeven 'text' filters naar SQL statements
	 */
	private function filterValuesToSql($filterValueList, $currentSession) {
		# Add a list of possible text searches
		$filterValueSql = array();
		$additionalFields = array();
		$sortFields = array();
		
		# Een lookup tabel die de zoeknaam omzet naar een database veldnaam
		$filterFieldMapping = array('filesize' => 's.filesize',
								  'date' => 's.stamp',
								  'userid' => 'f.userid',
								  'moderated' => 's.moderated',
								  'poster' => 's.poster',
								  'titel' => 's.title',
								  'tag' => 's.tag',
								  'new' => 'new',
								  'downloaded' => 'downloaded', 
								  'watch' => 'watch', 
								  'seen' => 'seen');

		foreach($filterValueList as $filterRecord) {
			$tmpFilterFieldname = strtolower($filterRecord['fieldname']);
			$tmpFilterOperator = $filterRecord['operator'];
			$tmpFilterValue = $filterRecord['value'];

			# We proberen nu het opgegeven veldnaam te mappen naar de database
			# kolomnaam. Als dat niet kan, gaan we er van uit dat het een 
			# ongeldige zoekopdracht is, en dan interesseert ons heel de zoek
			# opdracht niet meer.
			if (!isset($filterFieldMapping[$tmpFilterFieldname])) {
				break;
			} # if

			# valideer eerst de operatoren
			if (!in_array($tmpFilterOperator, array('>', '<', '>=', '<=', '='))) {
				break;
			} # if

			# een lege zoekopdracht negeren we gewoon, 'empty' kunnen we niet
			# gebruiken omdat empty(0) ook true geeft, en 0 is wel een waarde
			# die we willen testen
			if (strlen($tmpFilterValue) == 0) {
				continue;
			} # if

			#
			# als het een pure textsearch is, die we potentieel kunnen optimaliseren,
			# met een fulltext search (engine), voer dan dit pad uit zodat we de 
			# winst er mee nemen.
			#
			if (in_array($tmpFilterFieldname, array('tag', 'poster', 'titel'))) {
				$parsedTextQueryResult = $this->_db->createTextQuery($filterFieldMapping[$tmpFilterFieldname], $tmpFilterValue);
				$filterValueSql[] = ' (' . $parsedTextQueryResult['filter'] . ') ';

				# We voegen deze extended textqueries toe aan de filterlist als
				# relevancy veld, hiermee kunnen we dan ook zoeken op de relevancy
				# wat het net wat interessanter maakt
				if ($parsedTextQueryResult['sortable']) {
					# We zouden in theorie meerdere van deze textsearches kunnen hebben, dan 
					# sorteren we ze in de volgorde waarop ze binnenkwamen 
					$tmpSortCounter = count($additionalFields);
					
					$additionalFields[] = $parsedTextQueryResult['filter'] . ' AS searchrelevancy' . $tmpSortCounter;
					$sortFields[] = array('field' => 'searchrelevancy' . $tmpSortCounter,
										  'direction' => 'DESC');
				} # if
			} elseif (in_array($tmpFilterFieldname, array('new', 'downloaded', 'watch', 'seen'))) {
				# 
				# Er zijn speciale veldnamen welke we gebruiken als dummies om te matchen 
				# met de spotstatelist. Deze veldnamen behandelen we hier
				#
				switch($tmpFilterFieldname) {
					case 'new' : {
							if ($currentSession['user']['prefs']['auto_markasread']) {
								$tmpFilterValue = ' ((s.stamp > ' . (int) $this->_db->safe( max($currentSession['user']['lastvisit'],$currentSession['user']['lastread']) ) . ')';
							} else {
								$tmpFilterValue = ' ((s.stamp > ' . (int) $this->_db->safe($currentSession['user']['lastread']) . ')';
							} # else
							$tmpFilterValue .= ' AND (l.seen IS NULL))';
							
							break;
					} # case 'new' 

					case 'downloaded' : $tmpFilterValue = ' (l.download IS NOT NULL)'; 	break;
					case 'watch' 	  : $tmpFilterValue = ' (l.watch IS NOT NULL)'; break;
					case 'seen' 	  : $tmpFilterValue = ' (l.seen IS NOT NULL)'; 	break;
				} # switch
				
				# en creeer de query string
				$filterValueSql[] = $tmpFilterValue;
			} else {
				# Anders is het geen textsearch maar een vergelijkings operator, 
				# eerst willen we de vergelijking eruit halen.
				#
				# De filters komen in de vorm: Veldnaam:Operator:Waarde, bv: 
				#   filesize:>=:4000000
				#
				if ($tmpFilterFieldname == 'date') {
					$tmpFilterValue = date("U",  strtotime($tmpFilterValue));
				} elseif (($tmpFilterFieldname == 'filesize') && (is_numeric($tmpFilterValue) === false)) {
					# We casten expliciet naar float om een afrondings bug in PHP op het 32-bits
					# platform te omzeilen.
					$val = (float) trim(substr($tmpFilterValue, 0, -1));
					$last = strtolower($tmpFilterValue[strlen($tmpFilterValue) - 1]);
					switch($last) {
						case 'g': $val *= (float) 1024;
						case 'm': $val *= (float) 1024;
						case 'k': $val *= (float) 1024;
					} # switch
					$tmpFilterValue = round($val, 0);
				} # if
					
				# als het niet numeriek is, zet er dan een quote by
				if (!is_numeric($tmpFilterValue)) {
					$tmpFilterValue = "'" . $this->_db->safe($tmpFilterValue) . "'";
				} else {
					$tmpFilterValue = $this->_db->safe($tmpFilterValue);
				} # if

				# en creeer de query string
				$filterValueSql[] = ' (' . $filterFieldMapping[$tmpFilterFieldname] . ' ' . $tmpFilterOperator . ' '  . $tmpFilterValue . ') ';
			} # if
		} # foreach
		
		return array($filterValueSql, $additionalFields, $sortFields);
	} # filterValuesToSql

	/*
	 * Genereert de lijst met te sorteren velden
	 */
	function prepareSortFields($sort, $sortFields) {
		$VALID_SORT_FIELDS = array('category', 'poster', 'title', 'filesize', 'stamp', 'subcata', 'spotrating', 'commentcount');

		if ((!isset($sort['field'])) || (in_array($sort['field'], $VALID_SORT_FIELDS) === false)) {
			# We sorteren standaard op stamp, maar alleen als er vanuit de query
			# geen expliciete sorteermethode is meegegeven
			if (empty($sortFields)) {
				$sortFields[] = array('field' => 's.stamp', 'direction' => 'DESC');
			} # if
		} else {
			if (strtoupper($sort['direction']) != 'ASC') {
				$sort['direction'] = 'DESC';
			} # if
			
			# Omdat deze sortering expliciet is opgegeven door de user, geven we deze voorrang
			# boven de automatisch toegevoegde sorteringen en zetten hem dus aan het begin
			# van de sorteer lijst.
			array_unshift($sortFields, array('field' => 's.' . $sort['field'], 'direction' => $sort['direction']));
		} # else
		
		return $sortFields;
	} # prepareSortFields

	/*
	 * Converteer een array met search termen (tree, type en value) naar een SQL
	 * statement dat achter een WHERE geplakt kan worden.
	 */
	function filterToQuery($search, $sort, $currentSession) {


/*
TODO:

* Een 'compress tree' functie bouwen die als een hele subcategory
  gekozen wordt deze samenvat in onze eigen samenvat taal.
* De links die we genereren voeden met onze eigen searchtree ipv die uit de 
  GEt parameters!
*/


		SpotTiming::start(__FUNCTION__);
		$categoryList = array();
		$categorySql = array();
		
		$strongNotList = array();
		$strongNotSql = array();
		
		$filterValueList = array();
		$filterValueSql = array();
		
		$additionalFields = array();
		$sortFields = array();
		
		# Als er geen enkele filter opgegeven is, filteren we niets
		if (empty($search)) {
			return array('filter' => '',
						 'search' => array(),
					     'additionalFields' => array(),
					     'sortFields' => array(array('field' => 'stamp', 'direction' => 'DESC')));
		} # if

		
		#
		# Verwerk de parameters in $search (zowel legacy parameters, als de nieuwe 
		# type filter waardes), naar een array met filter waarden
		#
		$filterValueList = $this->prepareFilterValues($search);
		list($filterValueSql, $additionalFields, $sortFields) = $this->filterValuesToSql($filterValueList, $currentSession);

		# als er gevraagd om de filters te vergeten (en enkel op het woord te zoeken)
		# resetten we gewoon de boom
		if ((isset($search['unfiltered'])) && (($search['unfiltered'] === 'true'))) {
			$search = array_merge($search, $this->_settings->get('index_filter'));
		} # if
		
		# 
		# Vertaal nu een eventueel opgegeven boom naar daadwerkelijke subcategorieen
		# en dergelijke
		#
		if (!empty($search['tree'])) {
			# explode the dynaList
			$dynaList = explode(',', $search['tree']);
			list($categoryList, $strongNotList) = $this->prepareCategorySelection($dynaList);

			# en converteer de lijst met subcategorieen naar een lijst met SQL
			# filters
			$categorySql = $this->categoryListToSql($categoryList);
			$strongNotSql = $this->strongNotListToSql($strongNotList);
		} # if

		# Kijk nu of we nog een expliciete sorteermethode moeten meegeven 
		$sortFields = $this->prepareSortFields($sort, $sortFields);

		$endFilter = array();
		if (!empty($categorySql)) {
			$endFilter[] = '(' . join(' OR ', $categorySql) . ') ';
		} # if
		if (!empty($filterValueSql)) {
			$endFilter[] = join(' AND ', $filterValueSql);
		} # if
		if (!empty($strongNotSql)) {
			$endFilter[] = join(' AND ', $strongNotSql);
		} # if
		
		SpotTiming::stop(__FUNCTION__, array(join(" AND ", $endFilter)));
		return array('filter' => join(" AND ", $endFilter),
					 'search' => $search,
					 'additionalFields' => $additionalFields,
					 'sortFields' => $sortFields);
	} # filterToQuery
	
	
} # class SpotOverview
