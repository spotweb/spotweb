<?php

class dbfts_mysql extends dbfts_abs {
	// LIST OF STOP WORDS: http://dev.mysql.com/doc/refman/5.0/en/fulltext-stopwords.html  
	// Array copied from http://www.linein.org/blog/2008/11/10/mysql-full-text-stopwords-array/
	private $stop_words = array('a\'s', 'able', 'about', 'above', 'according', 'accordingly', 'across', 'actually',
							'after', 'afterwards', 'again', 'against', 'ain\'t', 'all', 'allow', 'allows', 'almost', 
							'alone', 'along', 'already', 'also', 'although', 'always', 'am', 'among', 'amongst', 'an',  
							'and', 'another', 'any', 'anybody', 'anyhow', 'anyone', 'anything', 'anyway', 'anyways', 
							'anywhere', 'apart', 'appear', 'appreciate', 'appropriate', 'are', 'aren\'t', 'around', 'as', 
							'aside', 'ask', 'asking', 'associated', 'at', 'available', 'away', 'awfully', 'be', 'became', 
							'because', 'become', 'becomes', 'becoming', 'been', 'before', 'beforehand', 'behind', 'being',
							'believe', 'below', 'beside', 'besides', 'best', 'better', 'between', 'beyond', 'both', 'brief',
							'but', 'by', 'c\'mon', 'c\'s', 'came', 'can', 'can\'t', 'cannot', 'cant', 'cause', 'causes', 
							'certain', 'certainly', 'changes', 'clearly', 'co', 'com', 'come', 'comes', 'concerning', 
							'consequently', 'consider', 'considering', 'contain', 'containing', 'contains', 'corresponding', 
							'could', 'couldn\'t', 'course', 'currently', 'definitely', 'described', 'despite', 'did', 'didn\'t', 
							'different', 'do', 'does', 'doesn\'t', 'doing', 'don\'t', 'done', 'down', 'downwards', 'during', 
							'each', 'edu', 'eg', 'eight', 'either', 'else', 'elsewhere', 'enough', 'entirely', 'especially', 
							'et', 'etc', 'even', 'ever', 'every', 'everybody', 'everyone', 'everything', 'everywhere', 'ex', 
							'exactly', 'example', 'except', 'far', 'few', 'fifth', 'first', 'five', 'followed', 'following', 
							'follows', 'for', 'former', 'formerly', 'forth', 'four', 'from', 'further', 'furthermore', 'get', 
							'gets', 'getting', 'given', 'gives', 'go', 'goes', 'going', 'gone', 'got', 'gotten', 'greetings', 
							'had', 'hadn\'t', 'happens', 'hardly', 'has', 'hasn\'t', 'have', 'haven\'t', 'having', 'he', 
							'he\'s', 'hello', 'help', 'hence', 'her', 'here', 'here\'s', 'hereafter', 'hereby', 'herein', 
							'hereupon', 'hers', 'herself', 'hi', 'him', 'himself', 'his', 'hither', 'hopefully', 'how', 
							'howbeit', 'however', 'i\'d', 'i\'ll', 'i\'m', 'i\'ve', 'ie', 'if', 'ignored', 'immediate', 
							'in', 'inasmuch', 'inc', 'indeed', 'indicate', 'indicated', 'indicates', 'inner', 'insofar', 
							'instead', 'into', 'inward', 'is', 'isn\'t', 'it', 'it\'d', 'it\'ll', 'it\'s', 'its', 'itself', 
							'just', 'keep', 'keeps', 'kept', 'know', 'knows', 'known', 'last', 'lately', 'later', 'latter', 
							'latterly', 'least', 'less', 'lest', 'let', 'let\'s', 'like', 'liked', 'likely', 'little', 
							'look', 'looking', 'looks', 'ltd', 'mainly', 'many', 'may', 'maybe', 'me', 'mean', 'meanwhile', 
							'merely', 'might', 'more', 'moreover', 'most', 'mostly', 'much', 'must', 'my', 'myself', 'name', 
							'namely', 'nd', 'near', 'nearly', 'necessary', 'need', 'needs', 'neither', 'never', 'nevertheless', 
							'new', 'next', 'nine', 'no', 'nobody', 'non', 'none', 'noone', 'nor', 'normally', 'not', 'nothing', 
							'novel', 'now', 'nowhere', 'obviously', 'of', 'off', 'often', 'oh', 'ok', 'okay', 'old', 'on', 'once', 
							'one', 'ones', 'only', 'onto', 'or', 'other', 'others', 'otherwise', 'ought', 'our', 'ours', 
							'ourselves', 'out', 'outside', 'over', 'overall', 'own', 'particular', 'particularly', 'per', 
							'perhaps', 'placed', 'please', 'plus', 'possible', 'presumably', 'probably', 'provides', 'que', 
							'quite', 'qv', 'rather', 'rd', 're', 'really', 'reasonably', 'regarding', 'regardless', 'regards', 
							'relatively', 'respectively', 'right', 'said', 'same', 'saw', 'say', 'saying', 'says', 'second', 
							'secondly', 'see', 'seeing', 'seem', 'seemed', 'seeming', 'seems', 'seen', 'self', 'selves', 
							'sensible', 'sent', 'serious', 'seriously', 'seven', 'several', 'shall', 'she', 'should', 'shouldn\'t', 
							'since', 'six', 'so', 'some', 'somebody', 'somehow', 'someone', 'something', 'sometime', 'sometimes', 
							'somewhat', 'somewhere', 'soon', 'sorry', 'specified', 'specify', 'specifying', 'still', 'sub', 'such', 
							'sup', 'sure', 't\'s', 'take', 'taken', 'tell', 'tends', 'th', 'than', 'thank', 'thanks', 'thanx', 
							'that', 'that\'s', 'thats', 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'thence', 'there', 
							'there\'s', 'thereafter', 'thereby', 'therefore', 'therein', 'theres', 'thereupon', 'these', 'they', 
							'they\'d', 'they\'ll', 'they\'re', 'they\'ve', 'think', 'third', 'this', 'thorough', 'thoroughly', 
							'those', 'though', 'three', 'through', 'throughout', 'thru', 'thus', 'to', 'together', 'too', 'took', 
							'toward', 'towards', 'tried', 'tries', 'truly', 'try', 'trying', 'twice', 'two', 'un', 'under', 
							'unfortunately', 'unless', 'unlikely', 'until', 'unto', 'up', 'upon', 'us', 'use', 'used', 
							'useful', 'uses', 'using', 'usually', 'value', 'various', 'very', 'via', 'viz', 'vs', 'want', 
							'wants', 'was', 'wasn\'t', 'way', 'we', 'we\'d', 'we\'ll', 'we\'re', 'we\'ve', 'welcome', 'well', 
							'went', 'were', 'weren\'t', 'what', 'what\'s', 'whatever', 'when', 'whence', 'whenever', 'where', 
							'where\'s', 'whereafter', 'whereas', 'whereby', 'wherein', 'whereupon', 'wherever', 'whether', 
							'which', 'while', 'whither', 'who', 'who\'s', 'whoever', 'whole', 'whom', 'whose', 'why', 'will', 
							'willing', 'wish', 'with', 'within', 'without', 'won\'t', 'wonder', 'would', 'would', 'wouldn\'t', 
							'yes', 'yet', 'you', 'you\'d', 'you\'ll', 'you\'re', 'you\'ve', 'your', 'yours', 'yourself', 
							'yourselves', 'zero'); 


	/*
	 * Constructs a query part to match textfields. Abstracted so we can use
	 * a database specific FTS engine if one is provided by the DBMS
	 */
	function createTextQuery($searchFields, $additionalFields) {
		SpotTiming::start(__CLASS__ . '::' . __FUNCTION__);

		/*
		 * Initialize some basic values which are used as return values to
		 * make sure always return a valid set
		 */
		$filterValueSql = array();
		$sortFields = array();
        $addFields = array();

		/*
		 * MySQL's fultxt search has a minimum length of words for indexes. Per default this is 
		 * a minimum word length of 4. This means that a searchstring like 'Top 40' will not
		 * be found because both 'Top' and '40' are shorter than 4 characters.
		 *
		 * We query the server setting, and if this is the case, we fall back to a basic LIKE
		 * search because it has no such limitation
		 */
		$serverSetting = $this->_db->arrayQuery("SHOW VARIABLES WHERE variable_name = 'ft_min_word_len'");
		$minWordLen = $serverSetting[0]['Value'];
                
        //var_dump($searchFields);

		foreach($searchFields as $searchItem) {
			$hasTooShortWords = false;
			$hasLongEnoughWords = false;
			$hasStopWords = false;
			$hasNoStopWords = false;
			$hasSearchOpAsTerm = false;
            $hasPhraseWithOnlyInvalids = false;

			$searchMode = "match-natural";
			$searchValue = trim($searchItem['value']);
			$field = $searchItem['fieldname'];

			/*
			 * Look at each individual word. If it is shorter than $minWordLen, we have to perform
			 * a LIKE search as well
			 *
			 * We do not use splitWords() here, because we need to have the lengths of the
			 * individual words not of the phrase search.
			 *
			 * There is one exception, if a phrase search contains ONLY stop words, we cannot
			 * use the FTS at all so we should be aware of that.
			 */
            $tempSearchValue = str_replace(array('+', '-', '"', '(', ')', 'AND', 'NOT', 'OR'), '', $searchValue);
			$termList = explode(' ', $tempSearchValue);
			foreach($termList as $term) {
				if ((strlen($term) < $minWordLen) && (strlen($term) > 0)) {
					$hasTooShortWords = true;
				} # if

				if (strlen($term) >= $minWordLen) {
                    $hasLongEnoughWords = true;
				} # if

                /*
                 * If the term is a stopword (things like: the, it, ...) we have to
                 * fallback to a like search as well.
                 */
                if (in_array(strtolower($term), $this->stop_words) !== false) {
                    $hasStopWords = true;
                } else {
                    $hasNoStopWords = true;
                }# if
			} # foreach

			/*
			 * remove any double whitespace because else the MySQL matcher will never
			 * find anything
			 */
			$searchValue = str_replace('  ', ' ', $searchValue);
			
			/*
			 * MySQL has several types of searches - both boolean and natural matching for
			 * FTS is possible.
			 *
			 * We try some heuristics to select the most appropriate type of search.
			 * If a word starts with either an '+' or an '-', we switch to boolean match
			 */
			$termList = $this->splitWords($searchValue);
			foreach($termList as $term) {
				/*
				 * We strip some characters because these are valid, but
				 * can cause the system to not recognize them as operators.
				 *
				 * The string: "(<test)" is such an example -- we only check
				 * the first character so we need to remove the parenthesis.
				 */
				$strippedTerm = trim($term, "()'\"");

				/*
				 * If after stripping the term of these characters, no string
				 * is left, make sure we just abort the matching
				 */
				if (strlen($strippedTerm) < 1) {
					continue;
				} # if

				/*
				 * + and - are only allowed at the beginning of the search to 
				 * enforce it as an search operator. If they are in the 
				 * words themselves, we fall back to LIKE
				 */
				if ((strpos($strippedTerm, '-') > 0) || (strpos($strippedTerm, '+') > 0) || (strpos($strippedTerm, '/') > 0)) {
					$hasSearchOpAsTerm = true;
				} # if

				/*
				 * When there are boolean operators in the string, it's an 
				 * boolean search
				 */
                if (strlen($strippedTerm[0]) > 0) {
				    if (strpos('+-~<>', $strippedTerm[0]) !== false) {
					    $searchMode = 'match-boolean';
                        $strippedTerm = trim($strippedTerm, "+-");
				    } # if
                }

                if (strlen(substr($strippedTerm,-1)) > 0) {
				    if (strpos('*', substr($strippedTerm, -1)) !== false) {
					    $searchMode = 'match-boolean';
                        $strippedTerm = trim($strippedTerm, "*");
				    } # if
                }

                if (strlen(substr($term,-1)) > 0) {
				    if (strpos('"', substr($term, -1)) !== false) {
					    $searchMode = 'match-boolean';
				    } # if
                }

                /*
                 * We get the complete phrase here, we need to look into
                 * the phrase terms, because if it only contains invalid terms
                 * (eg: only stopwords, only shortwords, or a combination thereof),
                 * we must disable the FTS completely.
                 *
                 */
                if ((!$hasPhraseWithOnlyInvalids) && ($term[0] == '"')) {
                    $tmpFoundValidTerms = false;

                    $tmpTermList = explode(' ', $strippedTerm);
                    foreach($tmpTermList as $tmpTerm) {
                        if (strlen($tmpTerm) >= $minWordLen) {
                            if (in_array(strtolower($tmpTerm), $this->stop_words) === false) {
                                $tmpFoundValidTerms = true;
                            } # if
                        } # if
                    } # foreach

                    if (!$tmpFoundValidTerms) {
                        $hasPhraseWithOnlyInvalids = true;
                    } # if
                } # if
			} # foreach

			# Actually determine the searchmode
			/* 
			 * Test cases:
			 *
			 * 		9th Company
			 *		Ubuntu 11
			 *		Top 40
			 *		"Top 40"
			 *		South Park
			 *		Sex and the city 
			 *		Rio
			 *		"sex and the city 2"
 			 *		Just Go With It (fallback naar like, enkel stopwoorden of te kort)
			 *		"Just Go With It" (fallback naar like, en quotes gestripped)
			 *      +"taken 2" +(2012) (fallback naar like, en quotes gestripped - enkel stop woorden maar operators)
			 *		+empire +sun
			 *		x-art (like search because it contains an -)
			 *		50/50 (like search because it contains an /)
			 *      Arvo -Lamentate (natural without like)
			 *      +"Phantom" +(2013)          <- Shouldn't use a LIKE per se
			 *      "The Top"                   <- Should use a LIKE as its only keywords
			 *      +"Warehouse 13" +S04        <- Shouldn't use a LIKE per se
			 */

            if ($hasPhraseWithOnlyInvalids) {
                $searchMode = 'normal';
            } elseif (($hasTooShortWords || $hasStopWords) && ($hasLongEnoughWords || $hasNoStopWords) && (!$hasSearchOpAsTerm)) {
				if (($hasStopWords && !$hasNoStopWords) || ($hasTooShortWords && !$hasLongEnoughWords)) {
					$searchMode = 'normal';
				} else {
					$searchMode = 'both-' . $searchMode;
				} # else
			} elseif ((($hasTooShortWords || $hasStopWords) && (!$hasLongEnoughWords && !$hasNoStopWords)) || ($hasSearchOpAsTerm)) {
				$searchMode = 'normal';
			} # else

/*
            echo 'hasStopWords              : ' . (int) $hasStopWords . '<br>';
            echo 'hasLongEnoughWords        : ' . (int) $hasLongEnoughWords . '<br>';
            echo 'hasTooShortWords          : ' . (int) $hasTooShortWords . '<br>';
            echo 'hasNoStopWords            : ' . (int) $hasNoStopWords . '<br>';
            echo 'hasSearchOpAsTerm         : ' . (int) $hasSearchOpAsTerm . '<br>';
            echo 'hasPhraseWithOnlyInvalids : ' . (int) $hasPhraseWithOnlyInvalids . '<br>';
            echo 'searchmode                : ' . $searchMode . '<br>';
            die();
*/

            /*
             * Start constructing the query. Sometimes we construct the query
             * both with a LIKE and with a MATCH statement
             */
			$queryPart = array();
            $matchPart = '';
            if (($searchMode == 'normal') || ($searchMode == 'both-match-natural') || ($searchMode == 'both-match-boolean')) {
                $splitted = $this->splitWords($searchValue);
                foreach ($splitted as $splittedTerm) {
                    /*
                     * If the term contains an boolean operator in the beginning,
                     * strip it
                     */
                    $filteredTerm = trim($splittedTerm, "\"");
                    $filteredTerm = ltrim($filteredTerm, "+-~<>");
                    $filteredTerm = rtrim($filteredTerm, "*");

                    if (!empty($filteredTerm)) {
                        $filteredTerm = str_replace(' ','_',$filteredTerm );
                        $filteredTerm = stripslashes ($filteredTerm );
                        $filteredTerm = str_replace('"','',$filteredTerm );
                        $filteredTerm = str_replace('+','',$filteredTerm );
                        $queryPart[] = ' ' . $field . " LIKE " . $this->_db->safe('%' . $filteredTerm . '%');
                    } # if
                } # foreach
            } # if
			
			if (($searchMode == 'match-natural') || ($searchMode == 'both-match-natural')) {
				/* Natural language mode always defaults in MySQL 5.0 en 5.1, but cannot be explicitly defined in MySQL 5.0 */
				$matchPart = " MATCH(" . $field . ") AGAINST (" . $this->_db->safe($searchValue) . ")";
				$queryPart[] = $matchPart;
			} # if 

            /*
             * Boolean searches with required or missing terms, will never match if the terms are
             * stopwords because stopwords are not in the index and cannot be found
             */
			if (($searchMode == 'match-boolean') || ($searchMode == 'both-match-boolean')) {
                $matchPart = " MATCH(" . $field . ") AGAINST (" . $this->_db->safe($searchValue) . " IN BOOLEAN MODE)";
				$queryPart[] = $matchPart;
			} # if

            /*
             * Add the textqueries with an AND per search term
             */
            $filterValueSql[] = ' (' . implode(' AND ', $queryPart) . ') ';

			/*
			 * We add these extended textqueries as a column to the filterlist
			 * and use it as a relevance column. This allows us to sort on
			 * relevance
			 */
			if ($searchMode != 'normal') {
				/*
				 * if we get multiple textsearches, we sort them per order
				 * in the system
				 */
				$tmpSortCounter = count($additionalFields) + count($addFields);
				
				$addFields[] = $matchPart . ' AS searchrelevancy' . $tmpSortCounter;
			
				$sortFields[] = array('field' => 'searchrelevancy' . $tmpSortCounter,
									  'direction' => 'DESC',
									  'autoadded' => true,
									  'friendlyname' => null);
			}  # if
		} # foreach

		SpotTiming::stop(__CLASS__ . '::' . __FUNCTION__, array($filterValueSql,$addFields,$sortFields));

        //var_dump($filterValueSql);
        //var_dump($addFields);
        //var_dump($sortFields);
        //die();

		return array('filterValueSql' => $filterValueSql,
					 'additionalTables' => array(),
					 'additionalFields' => $addFields,
					 'sortFields' => $sortFields);
	} # createTextQuery()

} # dbfts_mysql