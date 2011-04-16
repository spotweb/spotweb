<?php
class SpotStruct_mysql extends SpotStruct_abs {

	function createDatabase() {
		$q = $this->_dbcon->arrayQuery("SHOW TABLES");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE spots(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
										messageid varchar(128) CHARACTER SET ascii NOT NULL,
										category INTEGER, 
										subcat INTEGER,
										poster VARCHAR(128) NOT NULL,
										groupname VARCHAR(128),
										subcata VARCHAR(64),
										subcatb VARCHAR(64),
										subcatc VARCHAR(64),
										subcatd VARCHAR(64),
										subcatz VARCHAR(64),
										title VARCHAR(128) NOT NULL,
										tag VARCHAR(128),
										stamp INTEGER(10) UNSIGNED,
										reversestamp INTEGER DEFAULT 0,
										filesize BIGINT UNSIGNED NOT NULL DEFAULT 0,
										moderated BOOLEAN) ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spots_1 ON spots(messageid);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_2 ON spots(stamp);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_3 ON spots(reversestamp);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_4 ON spots(category, subcata, subcatb, subcatc, subcatd, subcatz DESC);");

			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_spots_fts_1 ON spots(title);");
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_spots_fts_2 ON spots(poster);");
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_spots_fts_3 ON spots(tag);");

			# spotsfull
			$this->_dbcon->rawExec("CREATE TABLE spotsfull(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
										messageid varchar(128) CHARACTER SET ascii NOT NULL,
										userid varchar(32),
										verified BOOLEAN,
										usersignature VARCHAR(128),
										userkey VARCHAR(200),
										xmlsignature VARCHAR(128),
										fullxml TEXT,
										filesize BIGINT UNSIGNED NOT NULL DEFAULT 0) ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spotsfull_1 ON spotsfull(messageid);");
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_spotsfull_fts_1 ON spotsfull(userid);");
			
			# NNTP table
			$this->_dbcon->rawExec("CREATE TABLE nntp(server varchar(128) PRIMARY KEY,
										   maxarticleid INTEGER UNIQUE,
										   nowrunning INTEGER DEFAULT 0,
										   lastrun INTEGER DEFAULT 0) ENGINE = MYISAM CHARSET=utf8 COLLATE=utf8_general_ci;");

			# commentsxover
			$this->_dbcon->rawExec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY AUTO_INCREMENT,
										   messageid VARCHAR(128) CHARACTER SET ascii NOT NULL,
										   nntpref VARCHAR(128) CHARACTER SET ascii,
										   spotrating INTEGER DEFAULT 0) ENGINE = MYISAM CHARSET=utf8 COLLATE=utf8_general_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsxover_1 ON commentsxover(messageid);");
			$this->_dbcon->rawExec("CREATE INDEX idx_commentsxover_2 ON commentsxover(nntpref);");
			
			# downloadlist
			$this->_dbcon->rawExec("CREATE TABLE downloadlist(id INTEGER PRIMARY KEY AUTO_INCREMENT,
										   messageid VARCHAR(128) CHARACTER SET ascii NOT NULL,
										   stamp INTEGER,
										   ouruserid INTEGER DEFAULT 0) ENGINE = MYISAM CHARSET=utf8 COLLATE=utf8_general_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_downloadlist_1 ON downloadlist(messageid);");

			# watchlist
			$this->_dbcon->rawExec("CREATE TABLE watchlist(id INTEGER PRIMARY KEY AUTO_INCREMENT,
												   messageid VARCHAR(128) CHARACTER SET ascii NOT NULL,
												   dateadded INTEGER,
												   comment TEXT,
												   ouruserid INTEGER DEFAULT 0) ENGINE = MYISAM CHARSET=utf8 COLLATE=utf8_general_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_watchlist_1 ON watchlist(messageid);");
			
			# commentsfull
			$this->_dbcon->rawExec("CREATE TABLE commentsfull (id INTEGER PRIMARY KEY AUTO_INCREMENT,
									  messageid VARCHAR(128) CHARACTER SET ascii NOT NULL,
									  fromhdr VARCHAR(128),
									  stamp INTEGER(10) UNSIGNED,
									  usersignature VARCHAR(128),
									  userkey VARCHAR(200),
									  userid VARCHAR(32),
									  hashcash VARCHAR(128),
									  body TEXT,
									  verified BOOLEAN) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsfull_1 ON commentsfull(messageid);");

			# settings
			$this->_dbcon->rawExec("CREATE TABLE settings (id INTEGER PRIMARY KEY AUTO_INCREMENT,
									  name VARCHAR(128) NOT NULL,
									  value TEXT) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_settings_1 ON settings(name);");
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
			$this->_dbcon->rawExec("DROP INDEX " . $idxname . " ON " . $tablename);
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
	function createTable($tablename, $collations) {
		if (!$this->tableExists($tablename)) {
			$this->_dbcon->rawExec("CREATE TABLE " . $tablename . " (id INTEGER PRIMARY KEY AUTO_INCREMENT) " . $collations);
		} # if
	} # createTable
	
	/* drop een table */
	function dropTable($tablename) {
		if ($this->tableExists($tablename)) {
			$this->_dbcon->rawExec("DROP TABLE " . $tablename);
		} # if
	} # dropTable
	
} # class
