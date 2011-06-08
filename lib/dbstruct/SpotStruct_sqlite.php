<?php
class SpotStruct_sqlite extends SpotStruct_abs {
	/* 
	 * optimaliseer/analyseer een aantal tables welke veel veranderen, 
	 * deze functie wijzigt geen data!
  	 */
	function analyze() { 
		$this->_dbcon->rawExec("ANALYZE spotstatelist");
		$this->_dbcon->rawExec("ANALYZE sessions");
		$this->_dbcon->rawExec("ANALYZE users");
		$this->_dbcon->rawExec("ANALYZE commentsfull");
	} # analyze

	/* converteert een "spotweb" datatype naar een mysql datatype */
	public function swDtToNative($colType) {
		switch(strtoupper($colType)) {
			case 'INTEGER'				: $colType = 'INTEGER'; break;
			case 'UNSIGNED INTEGER'		: $colType = 'INTEGER'; break;
			case 'BIGINTEGER'			: $colType = 'BIGINT'; break;
			case 'UNSIGNED BIGINTEGER'	: $colType = 'BIGINT'; break;
			case 'BOOLEAN'				: $colType = 'BOOLEAN'; break;
		} # switch
		
		return $colType;
	} # swDtToNative

	/* converteert een mysql datatype naar een "spotweb" datatype */
	public function nativeDtToSw($colInfo) {
		return $colInfo;
	} # nativeDtToSw 
	
	/* controleert of een index bestaat */
	function indexExists($idxname, $tablename) {
		$q = $this->_dbcon->arrayQuery("PRAGMA index_info(" . $idxname . ")");
		return !empty($q);
	} # indexExists

	/* controleert of een column bestaat */
	function columnExists($tablename, $colname) {
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(" . $tablename . ")");
		
		$foundCol = false;
		foreach($q as $row) {
			if ($row['name'] == $colname) {
				$foundCol = true;
				break;
			} # if
		} # foreach
		
		return $foundCol;
	} # columnExists
	

	/* Add an index, kijkt eerst wel of deze index al bestaat */
	function addIndex($idxname, $idxType, $tablename, $colList) {
		if (!$this->indexExists($idxname, $tablename)) {
			
			$this->_dbcon->rawExec("PRAGMA synchronous = OFF;");
			
			switch(strtolower($idxType)) {
				case ''		  : $this->_dbcon->rawExec("CREATE INDEX " . $idxname . " ON " . $tablename . "(" . implode(",", $colList) . ");"); break;
				case 'unique'  : $this->_dbcon->rawExec("CREATE UNIQUE INDEX " . $idxname . " ON " . $tablename . "(" . implode(",", $colList) . ");"); break;
			} # switch
		} # if
	} # addIndex

	/* dropt een index als deze bestaat */
	function dropIndex($idxname, $tablename) {
		# Check eerst of de tabel bestaat, anders kan
		# indexExists mislukken en een fatal error geven
		if (!$this->tableExists($tablename)) {
			return ;
		} # if
		
		if ($this->indexExists($idxname, $tablename)) {
			$this->_dbcon->rawExec("DROP INDEX " . $idxname);
		} # if
	} # dropIndex
	
	/* voegt een column toe, kijkt wel eerst of deze nog niet bestaat */
	function addColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation) {
		if (!$this->columnExists($tablename, $colName)) {
			# zet de DEFAULT waarde
			if (strlen($colDefault) != 0) {
				$colDefault = 'DEFAULT ' . $colDefault;
			} # if

			# Collation doen we niet in sqlite
			$colSetting = '';
			
			# converteer het kolom type naar het type dat wij gebruiken
			$colType = $this->swDtToNative($colType);
			
			# en zet de 'NOT NULL' om naar een string
			switch($notNull) {
				case true		: $nullStr = 'NOT NULL'; break;
				default			: $nullStr = '';
			} # switch
			
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . 
						" ADD COLUMN " . $colName . " " . $colType . " " . $colSetting . " " . $colDefault . " " . $nullStr);
		} # if
	} # addColumn
	
	/* dropt een kolom (mits db dit ondersteunt) */
	function dropColumn($colName, $tablename) {
		if ($this->columnExists($tablename, $colName)) {
			throw new Exception("Dropping of columns is not supported in sqlite");
		} # if
	} # dropColumn
	
	/* controleert of een tabel bestaat */
	function tableExists($tablename) {
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(" . $tablename . ")");
		return !empty($q);
	} # tableExists

	/* ceeert een lege tabel met enkel een ID veld, collation kan UTF8 of ASCII zijn */
	function createTable($tablename, $collation) {
		if (!$this->tableExists($tablename)) {
			$this->_dbcon->rawExec("CREATE TABLE " . $tablename . " (id INTEGER PRIMARY KEY ASC)");
		} # if
	} # createTable
	
	/* drop een table */
	function dropTable($tablename) {
		if ($this->tableExists($tablename)) {
			$this->_dbcon->rawExec("DROP TABLE " . $tablename);
		} # if
	} # dropTable

	/* verandert een storage engine (concept dat enkel mysql kent :P ) */
	function alterStorageEngine($tablename, $engine) {
		return ; // null operatie
	} # alterStorageEngine
	
	/* creeert een foreign key constraint */
	function addForeignKey($tablename, $colname, $reftable, $refcolumn, $action) {
		return ; // null
	} # addForeignKey

	/* dropped een foreign key constraint */
	function dropForeignKey($tablename, $colname, $reftable, $refcolumn, $action) {
		return ; // null
	} # dropForeignKey
	
	/* rename een table */
	function renameTable($tablename, $newTableName) {
		$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " RENAME TO " . $newTableName);
	} # renameTable

	/* wijzigt een column - controleert *niet* of deze voldoet aan het prototype */
	function modifyColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation, $what) {
		# als het de NOT NULL is of de charset, dan negeren we de gevraagde wijziging
		if (($what == 'not null') || ($what == 'charset') | ($what == 'default')) {
			return ;
		} # if
		
		throw new Exception("sqlite ondersteund het wijzigen van kolommen niet");
	} # modifyColumn
	
	/* Geeft, in een afgesproken formaat, de index formatie terug */
	function getColumnInfo($tablename, $colname) {
		# sqlite kent niet echt een manier om deze informatie in z'n geheel terug te geven, 
		# we vragen dus de index op en manglen hem vervolgens zodat het beeld klopt
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info('" . $tablename . "')");
		
		# find the keyname
		$colIndex = -1;
		for($i = 0; $i < count($q); $i++) {
			if ($q[$i]['name'] == $colname) {
				$colIndex = $i;
				break;
			} # if
		} # for
		
		# als de kolom niet gevonden is, geef dit ook terug
		if ($colIndex < 0) {
			return array();
		} # if
		
		# en vertaal de sqlite info naar het mysql-achtige formaat
		$colInfo = array();
		$colInfo['COLUMN_NAME'] = $colname;
		$colInfo['COLUMN_DEFAULT'] = $q[$colIndex]['dflt_value'];
		$colInfo['NOTNULL'] = $q[$colIndex]['notnull'];
		$colInfo['COLUMN_TYPE'] = $this->nativeDtToSw($q[$colIndex]['type']);
		$colInfo['CHARACTER_SET_NAME'] = 'bin';
		$colInfo['COLLATION_NAME'] = 'bin';
		
		return $colInfo;
	} # getColumnInfo
	
	/* Geeft, in een afgesproken formaat, de index informatie terug */
	function getIndexInfo($idxname, $tablename) {
		# sqlite kent niet echt een manier om deze informatie in z'n geheel terug te geven, 
		# we vragen dus de index op en manglen hem vervolgens zodat het beeld klopt
		$q = $this->_dbcon->arrayQuery("SELECT * FROM sqlite_master 
										  WHERE type = 'index' 
										    AND name = '" . $idxname . "' 
											AND tbl_name = '" . $tablename . "'");
		if (empty($q)) {
			return array();
		} # if
		
		# er is maar 1 index met die naam
		$q = $q[0];
											
		# eerst kijken we of de index unique gemarkeerd is
		$tmpAr = explode(" ", $q['sql']);
		$isNotUnique = (strtolower($tmpAr[1]) != 'unique');
		
		# vraag nu de kolom lijst op, en explode die op commas
		preg_match_all("/\((.*)\)/", $q['sql'], $tmpAr);
		$colList = explode(",", $tmpAr[1][0]);
		$colList = array_map('trim', $colList);
		
		# en nu bouwen we een array aan het formaat wat er verwacht wordt
		$idxInfo = array();
		for($i = 0; $i < count($colList); $i++) {
			$idxInfo[] = array('index_name' => $idxname,
			                   'seq_in_index' => $i + 1,
							   'column_name' => $colList[$i],
							   'non_unique' => (int) $isNotUnique,
							   'index_type' => 'BTREE'
						);
		} # foreach

		return $idxInfo;
	} # getIndexInfo
	
} # class
