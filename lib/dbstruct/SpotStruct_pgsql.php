<?php
class SpotStruct_pgsql extends SpotStruct_abs {

	/* 
	 * optimaliseer/analyseer een aantal tables welke veel veranderen, 
	 * deze functie wijzigt geen data!
  	 */
	function analyze() { 
		$this->_dbcon->rawExec("VACUUM ANALYZE spotstatelist");
		$this->_dbcon->rawExec("VACUUM ANALYZE sessions");
		$this->_dbcon->rawExec("VACUUM ANALYZE users");
		$this->_dbcon->rawExec("VACUUM ANALYZE commentsfull");
		$this->_dbcon->rawExec("VACUUM ANALYZE spots");
		$this->_dbcon->rawExec("VACUUM ANALYZE spotsfull");
		$this->_dbcon->rawExec("VACUUM ANALYZE commentsxover");
	} # analyze
	
	/* converteert een "spotweb" datatype naar een mysql datatype */
	function swDtToNative($colType) {
		switch(strtoupper($colType)) {
			case 'INTEGER'				: $colType = 'integer'; break;
			case 'UNSIGNED INTEGER'		: $colType = 'bigint'; break;
			case 'BIGINTEGER'			: $colType = 'bigint'; break;
			case 'UNSIGNED BIGINTEGER'	: $colType = 'bigint'; break;
			case 'BOOLEAN'				: $colType = 'boolean'; break;
		} # switch
		
		return $colType;
	} # swDtToNative 

	/* converteert een mysql datatype naar een "spotweb" datatype */
	function nativeDtToSw($colInfo) {
		switch(strtolower($colInfo)) {
			case 'integer'				: $colInfo = 'INTEGER'; break;
			case 'bigint'				: $colInfo = 'BIGINTEGER'; break;
			case 'boolean'				: $colInfo = 'BOOLEAN'; break;
		} # switch
		
		return $colInfo;
	} # nativeDtToSw 
	
	/* controleert of een index bestaat */
	function indexExists($idxname, $tablename) {
		$q = $this->_dbcon->arrayQuery("SELECT indexname FROM pg_indexes WHERE schemaname = CURRENT_SCHEMA() AND tablename = '%s' AND indexname = '%s'",
				Array($tablename, $idxname));
		return !empty($q);
	} # indexExists

	/* controleert of een column bestaat */
	function columnExists($tablename, $colname) {
		$q = $this->_dbcon->arrayQuery("SELECT column_name FROM information_schema.columns 
											WHERE table_schema = CURRENT_SCHEMA() AND table_name = '%s' AND column_name = '%s'",
									Array($tablename, $colname));
		return !empty($q);
	} # columnExists

	/* controleert of een full text index bestaat */
	function ftsExists($ftsname, $tablename) {
		return $this->indexExists($ftsname, $tablename);
	} # ftsExists
	
	/* maakt een full text index aan */
	function createFts($ftsname, $tablename, $colname) {
		return $this->addIndex($ftsname, 'FULLTEXT', $tablename, array($colname));
	} # createFts
	
	/* dropt en fulltext index */
	function dropFts($ftsname, $tablename) {
		$this->dropIndex($ftsname, $tablename);
	} # dropFts
	
	/* geeft FTS info terug */
	function getFtsInfo($ftsname, $tablename, $colname) {
		$tmpIndex = $this->getIndexInfo($ftsname, $tablename);
		if (strtolower($tmpIndex[0]['index_type']) != 'fulltext') {
			return array();
		} else {
			return $tmpIndex[0];
		} # if
	} # getFtsInfo

	/* Add an index, kijkt eerst wel of deze index al bestaat */
	function addIndex($idxname, $idxType, $tablename, $colList) {
		if (!$this->indexExists($idxname, $tablename)) {
			switch($idxType) {
				case 'UNIQUE': {
					$this->_dbcon->rawExec("CREATE UNIQUE INDEX " . $idxname . " ON " . $tablename . "(" . implode(",", $colList) . ")");
					break;
				} # case
				
				case 'FULLTEXT' : {
					$this->_dbcon->rawExec("CREATE INDEX " . $idxname . " ON " . $tablename . " USING gin(to_tsvector('dutch', " . implode(",", $colList) . "))");
					break;
				} # case
				
				default	: {
					$this->_dbcon->rawExec("CREATE INDEX " . $idxname . " ON " . $tablename . "(" . implode(",", $colList) . ")");
				} # default
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

			# converteer het kolom type naar het type dat wij gebruiken
			$colType = $this->swDtToNative($colType);

			# Enkel pgsql 9.1 (op dit moment beta) ondersteunt per column collation,
			# dus daar doen we voor nu niks mee.
			switch(strtolower($collation)) {
				case 'utf8'		: 
				case 'ascii'	: 
				case ''			: $colSetting = ''; break;
				default			: throw new Exception("Invalid collation setting");
			} # switch
			
			# en zet de 'NOT NULL' om naar een string
			switch($notNull) {
				case true		: $nullStr = 'NOT NULL'; break;
				default			: $nullStr = '';
			} # switch
			
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . 
						" ADD COLUMN " . $colName . " " . $colType . " " . $colSetting . " " . $colDefault . " " . $nullStr);
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

		# Enkel pgsql 9.1 (op dit moment beta) ondersteunt per column collation,
		# dus daar doen we voor nu niks mee.
		switch(strtolower($collation)) {
			case 'utf8'		: 
			case 'ascii'	: 
			case ''			: $colSetting = ''; break;
			default			: throw new Exception("Invalid collation setting");
		} # switch
		
		# en zet de 'NOT NULL' om naar een string
		switch($notNull) {
			case true		: $nullStr = 'NOT NULL'; break;
			default			: $nullStr = '';
		} # switch
		
		# zet de koloms type
		$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ALTER COLUMN " . $colName . " TYPE " . $colType);
		
		# zet de default value
		if (strlen($colDefault) > 0) {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ALTER COLUMN " . $colName . " SET " . $colDefault);
		} else {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ALTER COLUMN " . $colName . " DROP DEFAULT");
		} # if
		
		# en zet de null/not-null constraint
		if (strlen($notNull) > 0) {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ALTER COLUMN " . $colName . " SET NOT NULL");
		} else {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ALTER COLUMN " . $colName . " DROP NOT NULL");
		} # if
	} # modifyColumn


	/* dropt een kolom (mits db dit ondersteunt) */
	function dropColumn($colName, $tablename) {
		if ($this->columnExists($tablename, $colName)) {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " DROP COLUMN " . $colName);
		} # if
	} # dropColumn

	/* controleert of een tabel bestaat */
	function tableExists($tablename) {
		$q = $this->_dbcon->arrayQuery("SELECT tablename FROM pg_tables WHERE schemaname = CURRENT_SCHEMA() AND (tablename = '%s')", array($tablename));
		return !empty($q);
	} # tableExists

	/* ceeert een lege tabel met enkel een ID veld, collation kan UTF8 of ASCII zijn */
	function createTable($tablename, $collation) {
		if (!$this->tableExists($tablename)) {
			# Enkel pgsql 9.1 (op dit moment beta) ondersteunt per column collation,
			# dus daar doen we voor nu niks mee.
			switch(strtolower($collation)) {
				case 'utf8'		: 
				case 'ascii'	: 
				case ''			: $colSetting = ''; break;
				default			: throw new Exception("Invalid collation setting");
			} # switch
		
			$this->_dbcon->rawExec("CREATE TABLE " . $tablename . " (id SERIAL PRIMARY KEY) " . $colSetting);
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
		return false;
	} # alterStorageEngine
	
	/* rename een table */
	function renameTable($tablename, $newTableName) {
		$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " RENAME TO " . $newTableName);
	} # renameTable

	/* dropped een foreign key constraint */
	function dropForeignKey($tablename, $colname, $reftable, $refcolumn, $action) {
		/* SQL from http://stackoverflow.com/questions/1152260/postgres-sql-to-list-table-foreign-keys */
		$q = $this->_dbcon->arrayQuery("SELECT
											tc.constraint_name AS CONSTRAINT_NAME,
											tc.table_name AS TABLE_NAME,
											tc.constraint_schema AS TABLE_SCHEMA,
											kcu.column_name AS COLUMN_NAME,
											ccu.table_name AS REFERENCED_TABLE_NAME,
											ccu.column_name AS REFERENCED_COLUMN_NAME
										FROM
											information_schema.table_constraints AS tc
											JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
											JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
										WHERE constraint_type = 'FOREIGN KEY' 
										  AND tc.TABLE_SCHEMA = CURRENT_SCHEMA()
										  AND tc.TABLE_NAME = '%s'
										  AND kcu.COLUMN_NAME = '%s'
										  AND ccu.table_name = '%s'
										  AND ccu.column_name = '%s'",
								Array($tablename, $colname, $reftable, $refcolumn));
		if (!empty($q)) {
			foreach($q as $res) {
				$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " DROP FOREIGN KEY " . $res['CONSTRAINT_NAME']);
			} # foreach
		} # if
	} # dropForeignKey

	/* creeert een foreign key constraint */
	function addForeignKey($tablename, $colname, $reftable, $refcolumn, $action) {
		/* SQL from http://stackoverflow.com/questions/1152260/postgres-sql-to-list-table-foreign-keys */
		$q = $this->_dbcon->arrayQuery("SELECT
											tc.constraint_name AS CONSTRAINT_NAME,
											tc.table_name AS TABLE_NAME,
											tc.constraint_schema AS TABLE_SCHEMA,
											kcu.column_name AS COLUMN_NAME,
											ccu.table_name AS REFERENCED_TABLE_NAME,
											ccu.column_name AS REFERENCED_COLUMN_NAME
										FROM
											information_schema.table_constraints AS tc
											JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
											JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
										WHERE constraint_type = 'FOREIGN KEY' 
										  AND tc.TABLE_SCHEMA = CURRENT_SCHEMA()
										  AND tc.TABLE_NAME = '%s'
										  AND kcu.COLUMN_NAME = '%s'
										  AND ccu.table_name = '%s'
										  AND ccu.column_name = '%s'",
								Array($tablename, $colname, $reftable, $refcolumn));
		if (empty($q)) {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ADD FOREIGN KEY (" . $colname . ") 
										REFERENCES " . $reftable . " (" . $refcolumn . ") " . $action);
		} # if
	} # addForeignKey

	/* Geeft, in een afgesproken formaat, de column formatie terug */
	function getColumnInfo($tablename, $colname) {
		$q = $this->_dbcon->arrayQuery("SELECT column_name AS \"COLUMN_NAME\",
											   column_default AS \"COLUMN_DEFAULT\", 
											   is_nullable AS \"IS_NULLABLE\", 
											   data_type AS \"DATA_TYPE\", 
											   numeric_precision AS \"NUMERIC_PRECISION\", 
											   CASE 
													WHEN (data_type = 'character varying') THEN 'varchar(' || character_maximum_length || ')' 
													WHEN (data_type = 'integer') THEN 'integer' 
													WHEN (data_type = 'bigint') THEN 'bigint' 
													WHEN (data_type = 'boolean') THEN 'boolean' 
													WHEN (data_type = 'text') THEN 'text'
											   END as \"COLUMN_TYPE\",
   											   character_set_name AS \"CHARACTER_SET_NAME\", 
											   collation_name AS \"COLLATION_NAME\"
										FROM information_schema.COLUMNS 
										WHERE TABLE_SCHEMA = CURRENT_SCHEMA() 
										  AND TABLE_NAME = '%s'
										  AND COLUMN_NAME = '%s'",
							Array($tablename, $colname));
		if (!empty($q)) {
			$q = $q[0];

			$q['NOTNULL'] = ($q['IS_NULLABLE'] != 'YES');
			
			# converteer het default waarde naar iets anders
			if ((strlen($q['COLUMN_DEFAULT']) == 0) && (is_string($q['COLUMN_DEFAULT']))) {	
				$q['COLUMN_DEFAULT'] = "''";
			} # if

			# pgsql typecast de default waarde standaard, maar
			# wij gaar daar niet van uit, dus strip dat
			if (strpos($q['COLUMN_DEFAULT'], ':') !== false) {
				$elems = explode(':', $q['COLUMN_DEFAULT']);
				
				$q['COLUMN_DEFAULT'] = $elems[0];
			} # if
		} # if
		
		return $q;
	} # getColumnInfo
	
	/* Geeft, in een afgesproken formaat, de index informatie terug */
	function getIndexInfo($idxname, $tablename) {
		$q = $this->_dbcon->arrayQuery("SELECT * 
										FROM pg_indexes 
										WHERE schemaname = CURRENT_SCHEMA()
										  AND tablename = '%s'
										  AND indexname = '%s'", Array($tablename, $idxname));
		if (empty($q)) {
			return array();
		} # if
		
		# er is maar 1 index met die naam
		$q = $q[0];
											
		# eerst kijken we of de index unique gemarkeerd is
		$tmpAr = explode(" ", $q['indexdef']);
		$isNotUnique = (strtolower($tmpAr[1]) != 'unique');

		# vraag nu de kolom lijst op, en explode die op commas
		preg_match_all("/\((.*)\)/", $q['indexdef'], $tmpAr);
		
		$colList = explode(",", $tmpAr[1][0]);
		$colList = array_map('trim', $colList);
		
		# gin indexes (fulltext search) mogen maar 1 kolom beslaan, dus daar maken we 
		# een uitzondering voor
		$idxInfo = array();
		if (stripos($tmpAr[1][0], 'to_tsvector') === false) {
			for($i = 0; $i < count($colList); $i++) {
				$idxInfo[] = array('column_name' => $colList[$i],
								   'non_unique' => (int) $isNotUnique,
								   'index_type' => 'BTREE'
							);
			} # foreach
		} else {
			# extract de kolom naam
			preg_match_all("/\((.*)\)/U", $colList[1], $tmpAr);
			
			# en creer de indexinfo
			$idxInfo[] = array('column_name' => $tmpAr[1][0],
							   'non_unique' => (int) $isNotUnique,
							   'index_type' => 'FULLTEXT');
		} # else

		return $idxInfo;
	} # getIndexInfo
	
} # class