<?php
class SpotStruct_sqlite extends SpotStruct_abs {

	/*
	 * Optimize / analyze (database specific) a number of hightraffic
	 * tables.
	 * This function does not modify any schema or data
	 */
	function analyze() { 
		$this->_dbcon->rawExec("ANALYZE spots");
		$this->_dbcon->rawExec("ANALYZE spotsfull");
		$this->_dbcon->rawExec("ANALYZE commentsxover");
		$this->_dbcon->rawExec("ANALYZE commentsfull");
		$this->_dbcon->rawExec("ANALYZE spotstatelist");
		$this->_dbcon->rawExec("ANALYZE sessions");
		$this->_dbcon->rawExec("ANALYZE filters");
		$this->_dbcon->rawExec("ANALYZE spotteridblacklist");
		$this->_dbcon->rawExec("ANALYZE filtercounts");
		$this->_dbcon->rawExec("ANALYZE users");
		$this->_dbcon->rawExec("ANALYZE cache");
        $this->_dbcon->rawExec("ANALYZE moderatedringbuffer");
        $this->_dbcon->rawExec("ANALYZE usenetstate");
	} # analyze

	/*
	 * Returns a database specific representation of a boolean value
	 */
	function bool2dt($b) {
		if ($b) {
			return '1';
		} # if

		return '0';
	} # bool2dt

	/*
	 * Converts a 'spotweb' internal datatype to a 
	 * database specific datatype
	 */
	public function swDtToNative($colType) {
		switch(strtoupper($colType)) {
			case 'INTEGER'				: $colType = 'INTEGER'; break;
			case 'UNSIGNED INTEGER'		: $colType = 'INTEGER'; break;
			case 'BIGINTEGER'			: $colType = 'BIGINT'; break;
			case 'UNSIGNED BIGINTEGER'	: $colType = 'BIGINT'; break;
			case 'BOOLEAN'				: $colType = 'BOOLEAN'; break;
			case 'MEDIUMBLOB'			: $colType = 'BLOB'; break;
		} # switch
		
		return $colType;
	} # swDtToNative

	/*
	 * Converts a database native datatype to a spotweb native
	 * datatype
	 */
	public function nativeDtToSw($colInfo) {
		switch(strtolower($colInfo)) {
			case 'blob'				: $colInfo = 'MEDIUMBLOB'; break;
		} # switch
	
		return $colInfo;
	} # nativeDtToSw 
	
	/* checks if an index exists */
	function indexExists($idxname, $tablename) {
		$q = $this->_dbcon->arrayQuery("PRAGMA index_info(" . $idxname . ")");
		return !empty($q);
	} # indexExists

	/* checks if a column exists */
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
	
	/* controleert of een full text index bestaat */
	/* checks if a fts text index exists */
	function ftsExists($ftsname, $tablename, $colList) {
		foreach($colList as $colName) {
			$colInfo = $this->getColumnInfo($ftsname, $colName);
			
			if (empty($colInfo)) {
				return false;
			} # if
		} # foreach

        return true;
	} # ftsExists
			
	/* creates a full text index */
	function createFts($ftsname, $tablename, $colList) {
		/*
		 * Drop any tables (fts's are special tables/views in sqlite)
		 * which are linked to this FTS because we cannot alter those
		 * tables.
		 *
		 * This is rather slow, but it works
		 */
		$this->dropTable($ftsname);
		$this->_dbcon->rawExec("DROP TRIGGER IF EXISTS " . $ftsname . "_insert");
		
		# and recreate the virtual table and link the update trigger to it
		$this->_dbcon->rawExec("CREATE VIRTUAL TABLE " . $ftsname . " USING FTS4(CONTENT='spots'," . implode(',', $colList) . ", matchinfo=fts3)");

		$this->_dbcon->rawExec("INSERT INTO " . $ftsname . "(rowid, " . implode(',', $colList) . ") SELECT rowid," . implode(',', $colList) . " FROM " . $tablename);
		$this->_dbcon->rawExec("CREATE TRIGGER " . $ftsname . "_insert AFTER INSERT ON " . $tablename . " FOR EACH ROW
								BEGIN
								   INSERT INTO " . $ftsname . "(rowid," . implode(',', $colList) . ") VALUES (new.rowid, new." . implode(', new.', $colList) . ");
								END");
		$this->_dbcon->rawExec("CREATE TRIGGER " . $ftsname . "_delete AFTER DELETE ON " . $tablename . " FOR EACH ROW
								BEGIN
								   DELETE FROM " . $ftsname . " WHERE rowid=old.rowid;
								END");
	} # createFts
	
	/* drops a fulltext index */
	function dropFts($ftsname, $tablename, $colList) {
		$this->dropTable($ftsname);
	} # dropFts
	
	/* returns FTS info  */
	function getFtsInfo($ftsname, $tablename, $colList) {
		$ftsList = array();
		
		foreach($colList as $num => $col) {
			$tmpColInfo = $this->getColumnInfo($ftsname, $col);
			
			if (!empty($tmpColInfo)) {
				$tmpColInfo['column_name'] = $tmpColInfo['COLUMN_NAME'];
				$ftsList[] = $tmpColInfo;
			} # if
		} # foreach
		
		return $ftsList;
	} # getFtsInfo
	
	/*
	 * Adds an index, but first checks if the index doesn't
	 * exist already.
	 *
	 * $idxType can be either 'UNIQUE', '' or 'FULLTEXT'
	 */
	function addIndex($idxname, $idxType, $tablename, $colList) {
		if (!$this->indexExists($idxname, $tablename)) {
			
			$this->_dbcon->rawExec("PRAGMA synchronous = OFF;");
			
			switch(strtolower($idxType)) {
				case ''		  : $this->_dbcon->rawExec("CREATE INDEX " . $idxname . " ON " . $tablename . "(" . implode(",", $colList) . ");"); break;
				case 'unique'  : $this->_dbcon->rawExec("CREATE UNIQUE INDEX " . $idxname . " ON " . $tablename . "(" . implode(",", $colList) . ");"); break;
			} # switch
		} # if
	} # addIndex

	/* drops an index if it exists */
	function dropIndex($idxname, $tablename) {
		/*
		 * Make sure the table exists, else this will return an error
		 * and return a fatal
		 */
		if (!$this->tableExists($tablename)) {
			return ;
		} # if
		
		if ($this->indexExists($idxname, $tablename)) {
			$this->_dbcon->rawExec("DROP INDEX " . $idxname);
		} # if
	} # dropIndex
	
	/* adds a column if the column doesn't exist yet */
	function addColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation) {
		if (!$this->columnExists($tablename, $colName)) {
			# set the DEFAULT value
			if (strlen($colDefault) != 0) {
				$colDefault = 'DEFAULT ' . $colDefault;
			} # if

			# We don't support collation in sqlite
			$colSetting = '';
			
			# Convert the column type to a type we use in sqlite
			$colType = $this->swDtToNative($colType);
			
			# and define the 'NOT NULL' part
			switch($notNull) {
				case true		: $nullStr = 'NOT NULL'; break;
				default			: $nullStr = '';
			} # switch
			
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . 
						" ADD COLUMN " . $colName . " " . $colType . " " . $colSetting . " " . $colDefault . " " . $nullStr);
		} # if
	} # addColumn
	
	/* drops a column */
	function dropColumn($colName, $tablename) {
		if ($this->columnExists($tablename, $colName)) {
			throw new Exception("Dropping of columns is not supported in sqlite");
		} # if
	} # dropColumn
	
	/* checks if a table exists */
	function tableExists($tablename) {
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(" . $tablename . ")");
		return !empty($q);
	} # tableExists

	/* creates an empty table with only an ID field. Collation should be either UTF8 or ASCII */
	function createTable($tablename, $collation) {
		if (!$this->tableExists($tablename)) {
			$this->_dbcon->rawExec("CREATE TABLE " . $tablename . " (id INTEGER PRIMARY KEY ASC)");
		} # if
	} # createTable
	
	/* drop a table */
	function dropTable($tablename) {
		if ($this->tableExists($tablename)) {
			$this->_dbcon->rawExec("DROP TABLE " . $tablename);
		} # if
	} # dropTable

	/* changes storage engine (sqlite doesn't know anything about storage engines) */
	function alterStorageEngine($tablename, $engine) {
		return ; // null operatie
	} # alterStorageEngine

	/* create a foreign key constraint - not supported in spotweb+sqlite */
	function addForeignKey($tablename, $colname, $reftable, $refcolumn, $action) {
		return ; // null
	} # addForeignKey

	/* drop a foreign key constraint */
	function dropForeignKey($tablename, $colname, $reftable, $refcolumn, $action) {
		return ; // null
	} # dropForeignKey
	
	/* rename a table */
	function renameTable($tablename, $newTableName) {
		$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " RENAME TO " . $newTableName);
	} # renameTable

	/* alters a column - does not check if the column doesn't adhere to the given definition */
	function modifyColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation, $what) {
		/*
		 * if the change is either not null, charset or default we ignore the
		 * change request because these re not worth dropping the whole database
		 * for
		 */
		if (($what == 'not null') || ($what == 'charset') | ($what == 'default')) {
			return ;
		} # if
		
		# sqlite doesn't adhere types, so we can safely ignore those kind of changes
		if ($what == 'type') {
			return ;
		} # if
		
		throw new Exception("sqlite does not support modifying the schema of a column");
	} # modifyColumn
	
	/* Returns in a fixed format, column information */
	function getColumnInfo($tablename, $colname) {
		/*
		 * sqlite doesn't know a real way to gather this information, so we ask
		 * the table info and mangle the information in php to return a correct array
		 */
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info('" . $tablename . "')");
		
		# find the tablename
		$colIndex = -1;
		for($i = 0; $i < count($q); $i++) {
			if ($q[$i]['name'] == $colname) {
				$colIndex = $i;
				break;
			} # if
		} # for
		
		# when the column cannot be found, it's empty
		if ($colIndex < 0) {
			return array();
		} # if
		
		# translate sqlite tpe of information to the mysql format
		$colInfo = array();
		$colInfo['COLUMN_NAME'] = $colname;
		$colInfo['COLUMN_DEFAULT'] = $q[$colIndex]['dflt_value'];
		$colInfo['NOTNULL'] = $q[$colIndex]['notnull'];
		$colInfo['COLUMN_TYPE'] = $this->nativeDtToSw($q[$colIndex]['type']);
		$colInfo['CHARACTER_SET_NAME'] = 'bin';
		$colInfo['COLLATION_NAME'] = 'bin';
		
		return $colInfo;
	} # getColumnInfo
	
	/* Returns in a fixed format, index information */
	function getIndexInfo($idxname, $tablename) {
		/*
		 * sqlite doesn't know a real way to gather this information, so we ask
		 * the index info and mangle the information in php to return a correct array
		 */
		$q = $this->_dbcon->arrayQuery("SELECT * FROM sqlite_master 
										  WHERE type = 'index' 
										    AND name = '" . $idxname . "' 
											AND tbl_name = '" . $tablename . "'");
		if (empty($q)) {
			return array();
		} # if
		
		# a index name is globally unique in the database
		$q = $q[0];
											
		# unique index?
		$tmpAr = explode(" ", $q['sql']);
		$isNotUnique = (strtolower($tmpAr[1]) != 'unique');
		
		# retrieve column list and definition
		preg_match_all("/\((.*)\)/", $q['sql'], $tmpAr);
		$colList = explode(",", $tmpAr[1][0]);
		$colList = array_map('trim', $colList);
		
		# and translate column information to the desired format
		$idxInfo = array();
		for($i = 0; $i < count($colList); $i++) {
			$idxInfo[] = array('column_name' => $colList[$i],
							   'non_unique' => (int) $isNotUnique,
							   'index_type' => 'BTREE'
						);
		} # foreach

		return $idxInfo;
	} # getIndexInfo
	
} # class
