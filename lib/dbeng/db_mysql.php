<?php
# a mess
require_once "lib/dbeng/db_abs.php";
require_once "lib/dbstruct/SpotStruct_mysql.php";

class db_mysql extends db_abs {
	private $_db_host;
	private $_db_user;
	private $_db_pass;
	private $_db_db;
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
							 
	
	private $_conn;
	
	function __construct($host, $user, $pass, $db)
    {
		$this->_db_host = $host;
		$this->_db_user = $user;
		$this->_db_pass = $pass;
		$this->_db_db = $db;
	}
	
	function connect() {
		$this->_conn = @mysql_connect($this->_db_host, $this->_db_user, $this->_db_pass);
		
		if (!$this->_conn) {
			throw new Exception("Unable to connect to MySQL server: " . mysql_error());
		} # if 
				
		if (!@mysql_select_db($this->_db_db, $this->_conn)) {
			throw new Exception("Unable to select MySQL db: " . mysql_error($this->_conn));
			return false;
		} # if
    } # connect()
		
	function safe($s) {
		return mysql_real_escape_string($s);
	} # safe

	function rawExec($s) {
		$tmpRes = @mysql_unbuffered_query($s, $this->_conn);
		if ($tmpRes === false) {
			throw new Exception("Error executing query: " . mysql_error($this->_conn));
		} # if
		
		return $tmpRes;
	} # rawExec

	function singleQuery($s, $p = array()) {
		$startT = microtime(true);
		
		$res = $this->exec($s, $p);
		$row = mysql_fetch_array($res);
		mysql_free_result($res);
		
		#$endT = microtime(true);
		#echo "QUERY: " . $s . " ==> " . ($endT - $startT) . "<br>";
		
		return $row[0];
	} # singleQuery

	function arrayQuery($s, $p = array()) {
		#$startT = microtime(true);
		$rows = array();

		$res = $this->exec($s, $p); 
		while ($rows[] = mysql_fetch_assoc($res));

		# remove last element (false element)
		array_pop($rows); 
		
		mysql_free_result($res);
		#$endT = microtime(true);
		#echo "QUERY: " . $s . " ==> " . ($endT - $startT) . "<br>";
		return $rows;
	} # arrayQuery

	/* 
	 * Begins an transaction
	 */
	function beginTransaction() {
		$this->exec('BEGIN;');
	} # beginTransaction
	
	/* 
	 * Commits an transaction
	 */
	function commit() {
		$this->exec('COMMIT;');
	} # commit
	
	/* 
	 * Rolls back an transaction
	 */
	function rollback() {
		$this->exec('ROLLBACK;');
	} # rollback
	
	/*
	 * Utility functie omdat MySQL 0 rows affected teruggeeft als je
	 * een update uitvoert op een rij die hetzelfde blijft.
	 * 
	 * Copied from:
	 *    http://nl.php.net/manual/en/function.mysql-info.php#36008
	 */
	function get_mysql_info() {
		#$startT = microtime(true);
		$strInfo = mysql_info($this->_conn);
	   
		$return = array();
		ereg("Records: ([0-9]*)", $strInfo, $records);
		ereg("Duplicates: ([0-9]*)", $strInfo, $dupes);
		ereg("Warnings: ([0-9]*)", $strInfo, $warnings);
		ereg("Deleted: ([0-9]*)", $strInfo, $deleted);
		ereg("Skipped: ([0-9]*)", $strInfo, $skipped);
		ereg("Rows matched: ([0-9]*)", $strInfo, $rows_matched);
		ereg("Changed: ([0-9]*)", $strInfo, $changed);
	   
		$return['records'] = $records[1];
		$return['duplicates'] = $dupes[1];
		$return['warnings'] = $warnings[1];
		$return['deleted'] = $deleted[1];
		$return['skipped'] = $skipped[1];
		$return['rows_matched'] = $rows_matched[1];
		$return['changed'] = $changed[1];
   		#$endT = microtime(true);
		#echo "MYSQLINFO:  ==> " . ($endT - $startT) . "<br>";

		return $return;
	} # get_mysql_info()
	
	function rows() {
		$rows = $this->get_mysql_info();
		return $rows['rows_matched'];
	} # rows()
	
	/*
	 * Construeert een stuk van een query om op text velden te matchen, geabstraheerd
	 * zodat we eventueel gebruik kunnen maken van FTS systemen in een db
	 */
	function createTextQuery($field, $searchValue) {
		$searchMode = "match-natural";
		$searchValue = trim($searchValue);
		$tempSearchValue = str_replace(array('+', '-', 'AND', 'NOT', 'OR'), '', $searchValue);

		# MySQL fulltext search kent een minimum aan lengte voor woorden dat het indexeert,
		# standaard staat dit op 4 en dat betekent bv. dat een zoekstring als 'Top 40' niet gevonden
		# zal worden omdat zowel Top als 40 onder de 4 karakters zijn. We kijken hier wat de server
		# instelling is, en vallen eventueel terug op een normale 'LIKE' zoekopdracht.
		$serverSetting = $this->arrayQuery("SHOW VARIABLES WHERE variable_name = 'ft_min_word_len'");
		$minWordLen = $serverSetting[0]['Value'];

		# bekijk elk woord individueel, is het korter dan $minWordLen, gaan we terug naar normale 
		# LIKE  modus
		$termList = explode(' ', $tempSearchValue);
		foreach($termList as $term) {
			if ((strlen($term) < $minWordLen) && (strlen($term) > 0)) {
				$searchValue = $tempSearchValue;
				$searchMode = "normal";
				break;
			} # if
		} # foreach
		
		# bekijk elk woord opnieuw individueel, als we een + of - sign aan het begin van een woord
		# vinden, schakelen we over naar boolean match
		$termList = explode(' ', $searchValue);
		foreach($termList as $term) {
			# We strippen een aantal karakters omdat dat niet de search 
			# methode mag beinvloeden, bv. (<test) oid.
			$strippedTerm = trim($term, "()'\"");
		
			# als er boolean phrases in zitten, is het een boolean search
			if (strpos('+-~<>', $strippedTerm[0]) !== false) {
				$searchMode = 'match-boolean';
				break;
			} # if
			
			if (strpos('*', substr($strippedTerm, -1)) !== false) {
				$searchMode = 'match-boolean';
				break;
			} # if

			# als het een stop word is, dan vallen we ook terug naar de like search
			if (in_array($strippedTerm, $this->stop_words) !== false) {
				$searchMode = 'normal';
				break;
			} # if
		} # foreach
		
		switch($searchMode) {
			case 'normal'			: $queryPart = ' ' . $field . " LIKE '%" . $this->safe($searchValue) . "%'"; break;
			
			/* Natural language mode is altijd het default in MySQL 5.0 en 5.1, maar kan in 5.0 niet expliciet opgegeven worden */
			case 'match-natural'	: $queryPart = " MATCH(" . $field . ") AGAINST ('" . $this->safe($searchValue) . "')"; break;
			case 'match-boolean'	: $queryPart = " MATCH(" . $field . ") AGAINST ('" . $this->safe($searchValue) . "' IN BOOLEAN MODE)"; break;
		} # else
		
		return $queryPart;
	} # createTextQuery()
	

} # class
