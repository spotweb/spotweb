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
		SpotTiming::start(__FUNCTION__);

		/*
		 * Initialize some basic values which are used as return values to
		 * make sure always return a valid set
		 */
		$filterValueSql = array();
		$sortFields = array();

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

		foreach($searchFields as $searchItem) {
			$hasTooShortWords = false;
			$hasLongEnoughWords = false;
			$hasStopWords = false;
			$hasNoStopWords = false;
			$hasSearchOpAsTerm = false;
			
			$searchMode = "match-natural";
			$searchValue = trim($searchItem['value']);
			$field = $searchItem['fieldname'];
			$tempSearchValue = str_replace(array('+', '-', 'AND', 'NOT', 'OR'), '', $searchValue);

			/*
			 * Look at each individual word. If it is shorter than $minWordLen, we have to perform
			 * a LIKE search as well
			 */
			$termList = explode(' ', $tempSearchValue);
			foreach($termList as $term) {
				if ((strlen($term) < $minWordLen) && (strlen($term) > 0)) {
					$hasTooShortWords = true;
				} # if

				if (strlen($term) >= $minWordLen) {
					$hasLongEnoughWords = true;
				} # if
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
			$termList = explode(' ', $searchValue);
			foreach($termList as $term) {
				/*
				 * We strip some characters because these are valid, but
				 * can cause the system to not regocnize them as operators.
				 *
				 * The string: "(<test)" is such an example -- we only check
				 * the first character so we need to remove the parenthesis
				 */
				$strippedTerm = trim($term, "()'\"");

				/*
				 * If after stripping the term of these characters, no string
				 * is left, make sure we juts abort the matching
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
				if (strpos('+-~<>', $strippedTerm[0]) !== false) {
					$searchMode = 'match-boolean';
				} # if
				
				if (strpos('*', substr($strippedTerm, -1)) !== false) {
					$searchMode = 'match-boolean';
				} # if

				if (strpos('"', substr($term, -1)) !== false) {
					$searchMode = 'match-boolean';
				} # if

				/*
				 * If the term is a stopword (things like: the, it, ...) we have to
				 * fallback to a like search as well. 
				 */
				if (in_array(strtolower($strippedTerm), $this->stop_words) !== false) {
					$hasStopWords = true;
				} else {
					/*
					 * This extra chcek is necessary because when a query was to be done
					 * for only short of stopwords (eg: "The Top") , we should fall back to
					 * a like anyway
					 */
					if (strlen($term) >= $minWordLen) {
						$hasNoStopWords = true;
					} # if
				} # else
			} # foreach
			
			# Actually determine the searchmode
			/* 
			 * Test cases:
			 *
			 * 		9th Company
			 *		Ubuntu 9
			 *		Top 40
			 *		South Park
			 *		Sex and the city 
			 *		Rio
			 *		"sex and the city 2"
 			 *		Just Go With It (fallback naar like, enkel stopwoorden of te kort)
			 *		"Just Go With It" (fallback naar like, en quotes gestripped)
			 *		+empire +sun
			 *		x-art (like search because it contains an -)
			 *		50/50 (like search because it contains an /)
			 */

/* 
			echo 'HasTooShortWords  : ' . (int) $hasTooShortWords . '<br>';
			echo 'hasStopWords      : ' . (int) $hasStopWords . '<br>';
			echo 'hasLongEnoughWords: ' . (int) $hasLongEnoughWords . '<br>';
			echo 'hasNoStopWords    : ' . (int) $hasNoStopWords . '<br>';
			echo 'hasSearchOpAsTerm : ' . (int) $hasSearchOpAsTerm . '<br>';
			echo 'searchmode        : ' . $searchMode . '<br>';
			die();
*/
			
			if (($hasTooShortWords || $hasStopWords) && ($hasLongEnoughWords || $hasNoStopWords) && (!$hasSearchOpAsTerm)) {
				if ($hasStopWords && !$hasNoStopWords) {
					$searchMode = 'normal';
				} else {
					$searchMode = 'both-' . $searchMode;
				} # else
			} elseif ((($hasTooShortWords || $hasStopWords) && (!$hasLongEnoughWords && !$hasNoStopWords)) || ($hasSearchOpAsTerm)) {
				$searchMode = 'normal';
			} # else
			
			/*
			 * Start constructing the query. Sometimes we construct the quer
			 * both with a LIKE and with a MATCh statement
			 */
			$queryPart = '';
			if (($searchMode == 'normal') || ($searchMode == 'both-match-natural') /* || ($searchMode == 'both-match-boolean')*/) {
				$filterValueSql[] = ' ' . $field . " LIKE '%" . $this->_db->safe(trim($searchValue, "\"'")) . "%'";
			} # if
			
			if (($searchMode == 'match-natural') || ($searchMode == 'both-match-natural')) {
				/* Natural language mode altijd default in MySQL 5.0 en 5.1, but cannot be explicitly defined in MySQL 5.0 */
				$queryPart = " MATCH(" . $field . ") AGAINST ('" . $this->_db->safe($searchValue) . "')"; 
				$filterValueSql[] = $queryPart;
			} # if 
			
			if (($searchMode == 'match-boolean') || ($searchMode == 'both-match-boolean')) {
				$queryPart = " MATCH(" . $field . ") AGAINST ('" . $this->_db->safe($searchValue) . "' IN BOOLEAN MODE)";
				$filterValueSql[] = $queryPart;
			} # if

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
				$tmpSortCounter = count($additionalFields);
				
				$additionalFields[] = $queryPart . ' AS searchrelevancy' . $tmpSortCounter;
			
				$sortFields[] = array('field' => 'searchrelevancy' . $tmpSortCounter,
									  'direction' => 'DESC',
									  'autoadded' => true,
									  'friendlyname' => null);
			} # if
		} # foreach

		SpotTiming::stop(__FUNCTION__, array($filterValueSql,$additionalFields,$sortFields));

		return array('filterValueSql' => $filterValueSql,
					 'additionalTables' => array(),
					 'additionalFields' => $additionalFields,
					 'sortFields' => $sortFields);
	} # createTextQuery()

}
