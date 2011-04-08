<?php
require_once "lib/dbstruct/SpotStruct_abs.php";

class SpotStruct_mysql extends SpotStruct_abs {

	function createDatabase() {
		$q = $this->_dbcon->arrayQuery("SHOW TABLES");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE spots(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
										messageid varchar(128),
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
										moderated BOOLEAN DEFAULT FALSE) ENGINE = MYISAM;");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_1 ON spots(id, category, subcata, subcatd, stamp DESC)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_2 ON spots(id, category, subcatd, stamp DESC)");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spots_3 ON spots(messageid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_4 ON spots(stamp);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_5 ON spots(poster);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_6 ON spots(reversestamp);");

			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_spots_fts_1 ON spots(title);");
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_spots_fts_2 ON spots(poster);");
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_spots_fts_3 ON spots(tag);");

			# spotsfull
			$this->_dbcon->rawExec("CREATE TABLE spotsfull(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
										messageid varchar(128),
										userid varchar(32),
										verified BOOLEAN,
										usersignature TEXT,
										userkey TEXT,
										xmlsignature TEXT,
										fullxml TEXT,
										filesize BIGINT) ENGINE = MYISAM;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spotsfull_1 ON spotsfull(messageid, userid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spotsfull_2 ON spotsfull(userid);");
			
			# NNTP table
			$this->_dbcon->rawExec("CREATE TABLE nntp(server varchar(128) PRIMARY KEY,
										   maxarticleid INTEGER UNIQUE,
										   nowrunning INTEGER DEFAULT 0,
										   lastrun INTEGER DEFAULT 0) ENGINE = MYISAM;");

			# commentsxover
			$this->_dbcon->rawExec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY AUTO_INCREMENT,
										   messageid VARCHAR(128),
										   nntpref VARCHAR(128)) ENGINE = MYISAM;");
			$this->_dbcon->rawExec("CREATE INDEX idx_commentsxover_1 ON commentsxover(nntpref, messageid)");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsxover_2 ON commentsxover(messageid)");
			
			# downloadlist
			$this->_dbcon->rawExec("CREATE TABLE downloadlist(id INTEGER PRIMARY KEY AUTO_INCREMENT,
										   messageid VARCHAR(128),
										   stamp INTEGER) ENGINE = MYISAM;");
			$this->_dbcon->rawExec("CREATE INDEX idx_downloadlist_1 ON downloadlist(messageid)");

			# watchlist
			$this->_dbcon->rawExec("CREATE TABLE watchlist(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
												   messageid VARCHAR(128),
												   dateadded INTEGER,
												   comment TEXT) ENGINE = MYISAM;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_watchlist_1 ON watchlist(messageid)");
			
			# commentsfull
			$this->_dbcon->rawExec("CREATE TABLE `commentsfull` (
									  `id` int(11) NOT NULL AUTO_INCREMENT,
									  `messageid` varchar(128) DEFAULT NULL,
									  `fromhdr` varchar(128) DEFAULT NULL,
									  `stamp` int(11) DEFAULT NULL,
									  `usersignature` varchar(128) DEFAULT NULL,
									  `userkey` varchar(128) DEFAULT NULL,
									  `userid` varchar(128) DEFAULT NULL,
									  `verified` tinyint(1) DEFAULT NULL,
									  PRIMARY KEY (`id`)
									) ENGINE=MyISAM");
			$this->_dbcon->rawExec("CREATE INDEX idx_commentsfull ON commentsfull(messageid, stamp)");
		} # if
	} # createDatabase

	/* controleert of een index bestaat */
	function indexExists($tablename, $idxname) {
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
		if (!$this->indexExists($tablename, $idxname)) {
			$this->_dbcon->rawExec("CREATE " . $idxType . " INDEX " . $idxname . " ON " . $tablename . "(" . $colList . ");");
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
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ADD COLUMN(" . $colName . " " . $colDef . ")");
		} # if
	} # addColumn

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

	/* ceeert een lege tabel met enkel een ID veld */
	function createTable($tablename) {
		if (!$this->tableExists($tablename)) {
			$this->_dbcon->rawExec("CREATE TABLE " . $tablename . " (id INTEGER PRIMARY KEY AUTO_INCREMENT)");
		} # if
	} # createTable
	
	
} # class
