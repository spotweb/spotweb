<?php
class SpotStruct_sqlite extends SpotStruct_abs {
	
	function createDatabase() {
		# spots
		if (!$this->tableExists('spots')) {
			$this->_dbcon->rawExec("CREATE TABLE spots(id INTEGER PRIMARY KEY ASC, 
											messageid VARCHAR(128),
											category INTEGER, 
											subcat INTEGER,
											poster VARCHAR(128),
											groupname VARCHAR(128),
											subcata VARCHAR(64),
											subcatb VARCHAR(64),
											subcatc VARCHAR(64),
											subcatd VARCHAR(64),
											subcatz VARCHAR(64),
											title VARCHAR(128),
											tag VARCHAR(128),
											stamp INTEGER,
											reversestamp INTEGER DEFAULT 0,
											filesize BIGINT DEFAULT 0,
											moderated BOOLEAN DEFAULT FALSE,
											commentcount INTEGER DEFAULT 0,
											spotrating INTEGER DEFAULT 0);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_1 ON spots(id, category, subcata, subcatd, stamp DESC)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_2 ON spots(id, category, subcatd, stamp DESC)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_3 ON spots(messageid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_4 ON spots(stamp);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_5 ON spots(reversestamp);");
		} # if

		# spotsfull table
		if (!$this->tableExists('spotsfull')) {
			$this->_dbcon->rawExec("CREATE TABLE spotsfull(id INTEGER PRIMARY KEY, 
										messageid varchar(128),
										userid varchar(32),
										verified BOOLEAN,
										usersignature TEXT,
										userkey TEXT,
										xmlsignature TEXT,
										fullxml TEXT,
										filesize BIGINT);");										

			# create indices
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spotsfull_1 ON spotsfull(messageid, userid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spotsfull_2 ON spotsfull(userid);");
		} # if

		# spottexts table
		if (!$this->tableExists('spottexts')) {
			$this->_dbcon->rawExec("CREATE TABLE spottexts(messageid varchar(128),
										poster varchar(128),
										title varchar(128),
										tag varchar(128));");										

			# create indices
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spottexts_1 ON spottexts(messageid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spottexts_2 ON spottexts(poster);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spottexts_3 ON spottexts(title);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spottexts_4 ON spottexts(tag);");

		} # if

		# NNTP table
		if (!$this->tableExists('nntp')) {
			$this->_dbcon->rawExec("CREATE TABLE nntp(server TEXT PRIMARY KEY,
										maxarticleid INTEGER UNIQUE,
										nowrunning INTEGER DEFAULT 0,
										lastrun INTEGER DEFAULT 0);");
		} # if

		# commentsxover table
		if (!$this->tableExists('commentsxover')) {
			$this->_dbcon->rawExec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY ASC,
										   messageid VARCHAR(128),
										   nntpref VARCHAR(128),
										   spotrating INTEGER DEFAULT 0);");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsxover_1 ON commentsxover(messageid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_commentsxover_2 ON commentsxover(nntpref)");
		} # if
			
		# spotstatelist table
		if (!$this->tableExists('spotstatelist')) {
			$this->_dbcon->rawExec("CREATE TABLE spotstatelist(messageid VARCHAR(128),
										   ouruserid INTEGER DEFAULT 0,
										   download INTEGER,
										   watch INTEGER,
										   seen INTEGER);");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spotstatelist_1 ON spotstatelist(messageid,ouruserid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spotstatelist_2 ON spotstatelist(download);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spotstatelist_3 ON spotstatelist(watch);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spotstatelist_4 ON spotstatelist(seen);");
		} # if

		# commentsfull
		if (!$this->tableExists('commentsfull')) {
			$this->_dbcon->rawExec("CREATE TABLE `commentsfull` (
									  `id` integer PRIMARY KEY,
									  `messageid` varchar(128) DEFAULT NULL,
									  `fromhdr` varchar(128) DEFAULT NULL,
									  `stamp` int(11) DEFAULT NULL,
									  `usersignature` varchar(128) DEFAULT NULL,
									  `userkey` varchar(128) DEFAULT NULL,
									  `userid` varchar(128) DEFAULT NULL,
									  `hashcash` varchar(128) DEFAULT NULL,
									  `body` TEXT DEFAULT '',
									  `verified` tinyint(1) DEFAULT NULL)");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsfull_1 ON commentsfull(messageid)");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsfull_2 ON commentsfull(messageid,stamp)");
		} # if

		# settings
		if (!$this->tableExists('settings')) {
			$this->_dbcon->rawExec("CREATE TABLE settings (id INTEGER PRIMARY KEY,
									  name VARCHAR(128) NOT NULL,
									  value TEXT)");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_settings_1 ON settings(name)");
		} # if

		# commentsposted
		if (!$this->tableExists('commentsposted')) {
			$this->_dbcon->rawExec("CREATE TABLE commentsposted (id INTEGER PRIMARY KEY,
									  ouruserid INTEGER DEFAULT 0 NOT NULL,
									  messageid VARCHAR(128) NOT NULL,
									  inreplyto VARCHAR(128) NOT NULL,
									  randompart VARCHAR(32) NOT NULL,
									  rating INTEGER DEFAULT 0 NOT NULL,
									  body TEXT,
									  stamp INTEGER DEFAULT 0 NOT NULL)");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsposted_1 ON commentsposted(messageid);");
		} # if
	} # createDatabase

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
	
	/* controleert of een index bestaat */
	function indexExists($tablename, $idxname) {
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
		if (!$this->indexExists($tablename, $idxname)) {
			$this->_dbcon->rawExec("CREATE INDEX " . $idxname . " ON " . $tablename . "(" . $colList . ");");
		} # if
	} # addIndex

	/* dropt een index als deze bestaat */
	function dropIndex($idxname, $tablename) {
		if ($this->indexExists($tablename, $idxname)) {
			$this->_dbcon->rawExec("DROP INDEX " . $idxname);
		} # if
	} # dropIndex
	
	/* voegt een column toe, kijkt wel eerst of deze nog niet bestaat */
	function addColumn($colName, $tablename, $colDef) {
		if (!$this->columnExists($tablename, $colName)) {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ADD COLUMN " . $colName . " " . $colDef);
		} # if
	} # addColumn
	
	/* dropt een kolom (mits db dit ondersteunt) */
	function dropColumn($colName, $tablename) {
		throw new Exception("Dropping of columns is not supported in sqlite");
	} # dropColumn
	
	/* controleert of een tabel bestaat */
	function tableExists($tablename) {
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(" . $tablename . ")");
		return !empty($q);
	} # tableExists

	/* creeert een lege tabel met enkel een ID veld */
	function createTable($tablename, $collations) {
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

	
} # class
