<?php
class SpotStruct_mysql extends SpotStruct_abs {

	/* 
	 * optimaliseer/analyseer een aantal tables welke veel veranderen, 
	 * deze functie wijzigt geen data!
  	 */
	function analyze() { 
		$this->_dbcon->rawExec("ANALYZE TABLE spotstatelist");
		$this->_dbcon->rawExec("ANALYZE TABLE sessions");
		$this->_dbcon->rawExec("ANALYZE TABLE users");
		$this->_dbcon->rawExec("ANALYZE TABLE commentsfull");
		$this->_dbcon->rawExec("ANALYZE TABLE spots");
		$this->_dbcon->rawExec("ANALYZE TABLE spotsfull");
		$this->_dbcon->rawExec("ANALYZE TABLE commentsxover");
	} # analyze
	
	/* converteert een "spotweb" datatype naar een mysql datatype */
	function swDtToNative($colType) {
		switch(strtoupper($colType)) {
			case 'INTEGER'				: $colType = 'int(11)'; break;
			case 'UNSIGNED INTEGER'		: $colType = 'int(10) unsigned'; break;
			case 'BIGINTEGER'			: $colType = 'bigint(20)'; break;
			case 'UNSIGNED BIGINTEGER'	: $colType = 'bigint(20) unsigned'; break;
			case 'BOOLEAN'				: $colType = 'tinyint(1)'; break;
		} # switch
		
		return $colType;
	} # swDtToNative 

	/* converteert een mysql datatype naar een "spotweb" datatype */
	function nativeDtToSw($colInfo) {
		switch(strtolower($colInfo)) {
			case 'int(11)'				: $colInfo = 'INTEGER'; break;
			case 'int(10) unsigned'		: $colInfo = 'UNSIGNED INTEGER'; break;
			case 'bigint(20)'			: $colInfo = 'BIGINTEGER'; break;
			case 'bigint(20) unsigned'	: $colInfo = 'UNSIGNED BIGINTEGER'; break;
			case 'tinyint(1)'			: $colInfo = 'BOOLEAN'; break;
		} # switch
		
		return $colInfo;
	} # nativeDtToSw 
	
	/* controleert of een index bestaat */
	function indexExists($idxname, $tablename) {
		$q = $this->_dbcon->arrayQuery("SHOW INDEXES FROM " . $tablename . " WHERE key_name = '%s'", Array($idxname));
		return !empty($q);
	} # indexExists

	/* controleert of een column bestaat */
	function columnExists($tablename, $colname) {
		$q = $this->_dbcon->arrayQuery("SHOW COLUMNS FROM " . $tablename . " WHERE Field = '%s'", Array($colname));
		return !empty($q);
	} # columnExists


	/* Add an index, kijkt eerst wel of deze index al bestaat */
	function addIndex($idxname, $idxType, $tablename, $colList) {
		if (!$this->indexExists($idxname, $tablename)) {
			if ($idxType == "UNIQUE") {
				$this->_dbcon->rawExec("ALTER IGNORE TABLE " . $tablename . " ADD " . $idxType . " INDEX " . $idxname . "(" . implode(",", $colList) . ");");
			} else {
				$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ADD " . $idxType . " INDEX " . $idxname . "(" . implode(",", $colList) . ");");
			} # else
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
			$this->_dbcon->rawExec("DROP INDEX " . $idxname . " ON " . $tablename);
		} # if
	} # dropIndex
	
	/* voegt een column toe, kijkt wel eerst of deze nog niet bestaat */
	function addColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation) {
		if (!$this->columnExists($tablename, $colName)) {
			# zet de DEFAULT waarde
			if (strlen($colDefault) != 0) {
				$colDefault = 'DEFAULT ' . $colDefault;
			} # if

			# converteer het kolom type naar het type dat wij gebruiken
			$colType = $this->swDtToNative($colType);

			# Zet de collation om naar iets dat we begrijpen
			switch(strtolower($collation)) {
				case 'utf8'		: $colSetting = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci'; break;
				case 'ascii'	: $colSetting = 'CHARACTER SET ascii'; break;
				case ''			: $colSetting = ''; break;
				default			: throw new Exception("Invalid collation setting");
			} # switch
			
			# en zet de 'NOT NULL' om naar een string
			switch($notNull) {
				case true		: $nullStr = 'NOT NULL'; break;
				default			: $nullStr = '';
			} # switch
			
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . 
						" ADD COLUMN(" . $colName . " " . $colType . " " . $colSetting . " " . $colDefault . " " . $nullStr . ")");
		} # if
	} # addColumn
	
	/* wijzigt een column - controleert *niet* of deze voldoet aan het prototype */
	function modifyColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation, $what) {
		# zet de DEFAULT waarde
		if (strlen($colDefault) != 0) {
			$colDefault = 'DEFAULT ' . $colDefault;
		} # if

		# converteer het kolom type naar het type dat wij gebruiken
		$colType = $this->swDtToNative($colType);

		# Zet de collation om naar iets dat we begrijpen
		switch(strtolower($collation)) {
			case 'utf8'		: $colSetting = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci'; break;
			case 'ascii'	: $colSetting = 'CHARACTER SET ascii'; break;
			case ''			: $colSetting = ''; break;
			default			: throw new Exception("Invalid collation setting");
		} # switch
		
		# en zet de 'NOT NULL' om naar een string
		switch($notNull) {
			case true		: $nullStr = 'NOT NULL'; break;
			default			: $nullStr = '';
		} # switch
		
		$this->_dbcon->rawExec("ALTER TABLE " . $tablename . 
					" MODIFY COLUMN " . $colName . " " . $colType . " " . $colSetting . " " . $colDefault . " " . $nullStr);
	} # modifyColumn


	/* dropt een kolom (mits db dit ondersteunt) */
	function dropColumn($colName, $tablename) {
		if ($this->columnExists($tablename, $colName)) {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " DROP COLUMN " . $colName);
		} # if
	} # dropColumn

	/* controleert of een tabel bestaat */
	function tableExists($tablename) {
		$q = $this->_dbcon->arrayQuery("SHOW TABLES LIKE '" . $tablename . "'");
		return !empty($q);
	} # tableExists

	/* ceeert een lege tabel met enkel een ID veld, collation kan UTF8 of ASCII zijn */
	function createTable($tablename, $collation) {
		if (!$this->tableExists($tablename)) {
			switch(strtolower($collation)) {
				case 'utf8'		: $colSetting = 'CHARSET=utf8 COLLATE=utf8_unicode_ci'; break;
				case 'ascii'	: $colSetting = 'CHARSET=ascii'; break;
				default			: throw new Exception("Invalid collation setting");
			} # switch
		
			$this->_dbcon->rawExec("CREATE TABLE " . $tablename . " (id INTEGER PRIMARY KEY AUTO_INCREMENT) " . $colSetting);
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
		$q = $this->_dbcon->singleQuery("SELECT ENGINE 
										FROM information_schema.TABLES 
										WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $tablename . "'");

		if (strtolower($q) != strtolower($engine)) {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ENGINE=" . $engine);
		} # if
	} # alterStorageEngine
	
	/* rename een table */
	function renameTable($tablename, $newTableName) {
		$this->_dbcon->rawExec("RENAME TABLE " . $tablename . " TO " . $newTableName);
	} # renameTable

	/* dropped een foreign key constraint */
	function dropForeignKey($tablename, $colname, $reftable, $refcolumn, $action) {
		$q = $this->_dbcon->arrayQuery("SELECT CONSTRAINT_NAME FROM information_schema.key_column_usage 
										WHERE TABLE_SCHEMA = DATABASE() 
										  AND TABLE_NAME = '" . $tablename . "' 
										  AND COLUMN_NAME = '" . $colname . "'
										  AND REFERENCED_TABLE_NAME = '" . $reftable . "' 
										  AND REFERENCED_COLUMN_NAME = '" . $refcolumn . "'");
		if (!empty($q)) {
			foreach($q as $res) {
				$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " DROP FOREIGN KEY " . $res['CONSTRAINT_NAME']);
			} # foreach
		} # if
	} # dropForeignKey

	/* creeert een foreign key constraint */
	function addForeignKey($tablename, $colname, $reftable, $refcolumn, $action) {
		$q = $this->_dbcon->arrayQuery("SELECT * FROM information_schema.key_column_usage 
										WHERE TABLE_SCHEMA = DATABASE() 
										  AND TABLE_NAME = '" . $tablename . "' 
										  AND COLUMN_NAME = '" . $colname . "'
										  AND REFERENCED_TABLE_NAME = '" . $reftable . "' 
										  AND REFERENCED_COLUMN_NAME = '" . $refcolumn . "'");
		if (empty($q)) {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ADD FOREIGN KEY (" . $colname . ") 
										REFERENCES " . $reftable . " (" . $refcolumn . ") " . $action);
		} # if
	} # addForeignKey

	/* Geeft, in een afgesproken formaat, de index formatie terug */
	function getColumnInfo($tablename, $colname) {
		$q = $this->_dbcon->arrayQuery("SELECT COLUMN_NAME, 
											   COLUMN_DEFAULT, 
											   IS_NULLABLE, 
											   COLUMN_TYPE, 
											   CHARACTER_SET_NAME, 
											   COLLATION_NAME 
										FROM information_schema.COLUMNS 
										WHERE TABLE_NAME = '" . $tablename . "'
										  AND COLUMN_NAME = '" . $colname . "'
										  AND TABLE_SCHEMA = DATABASE()");
		if (!empty($q)) {
			$q = $q[0];
			$q['NOTNULL'] = ($q['IS_NULLABLE'] != 'YES');

			# converteer het default waarde naar iets anders
			if ((strlen($q['COLUMN_DEFAULT']) == 0) && (is_string($q['COLUMN_DEFAULT']))) {	
				$q['COLUMN_DEFAULT'] = "''";
			} # if
		} # if
		
		return $q;
	} # getColumnInfo
	
	/* Geeft, in een afgesproken formaat, de index informatie terug */
	function getIndexInfo($idxname, $tablename) {
		$q = $this->_dbcon->arrayQuery("SELECT 
											column_name, 
											non_unique, 
											lower(index_type) as index_type
										FROM information_schema.STATISTICS 
										WHERE TABLE_SCHEMA = DATABASE() 
										  AND table_name = '" . $tablename . "' 
										  AND index_name = '" . $idxname . "' 
										ORDER BY seq_in_index");
		return $q;
	} # getIndexInfo
	
} # class