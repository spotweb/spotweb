<?php
class SpotStruct_mysql extends SpotStruct_abs {

	function createDatabase() {
		# spots
		if (!$this->tableExists('spots')) {
			$this->_dbcon->rawExec("CREATE TABLE spots(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
										messageid varchar(128) CHARACTER SET ascii NOT NULL,
										category INTEGER, 
										subcat INTEGER,
										groupname VARCHAR(128),
										subcata VARCHAR(64),
										subcatb VARCHAR(64),
										subcatc VARCHAR(64),
										subcatd VARCHAR(64),
										subcatz VARCHAR(64),
										stamp INTEGER(10) UNSIGNED,
										reversestamp INTEGER DEFAULT 0,
										filesize BIGINT UNSIGNED NOT NULL DEFAULT 0,
										moderated BOOLEAN,
										commentcount INTEGER DEFAULT 0,
										spotrating INTEGER DEFAULT 0) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spots_1 ON spots(messageid);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_2 ON spots(stamp);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_3 ON spots(reversestamp);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_4 ON spots(category, subcata, subcatb, subcatc, subcatd, subcatz DESC);");
		} # if

		# spotsfull
		if (!$this->tableExists('spotsfull')) {
			$this->_dbcon->rawExec("CREATE TABLE spotsfull(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
										messageid varchar(128) CHARACTER SET ascii NOT NULL,
										userid varchar(32),
										verified BOOLEAN,
										usersignature VARCHAR(128),
										userkey VARCHAR(200),
										xmlsignature VARCHAR(128),
										fullxml TEXT,
										filesize BIGINT UNSIGNED NOT NULL DEFAULT 0) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spotsfull_1 ON spotsfull(messageid);");
		} # if

		# spottexts
		if (!$this->tableExists('spottexts')) {
			$this->_dbcon->rawExec("CREATE TABLE spottexts(messageid varchar(128) CHARACTER SET ascii NOT NULL,
										poster varchar(128),
										title varchar(128),
										tag varchar(128)) ENGINE = MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spottexts_1 ON spottexts(messageid);");
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_spottexts_2 ON spottexts(poster);");
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_spottexts_3 ON spottexts(title);");
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_spottexts_4 ON spottexts(tag);");
		} # if

		# NNTP table
		if (!$this->tableExists('nntp')) {
			$this->_dbcon->rawExec("CREATE TABLE nntp(server varchar(128) PRIMARY KEY,
										   maxarticleid INTEGER UNIQUE,
										   nowrunning INTEGER DEFAULT 0,
										   lastrun INTEGER DEFAULT 0) ENGINE = InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci;");
		} # if 

		# commentsxover
		if (!$this->tableExists('commentsxover')) {
			$this->_dbcon->rawExec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY AUTO_INCREMENT,
										   messageid VARCHAR(128) CHARACTER SET ascii NOT NULL,
										   nntpref VARCHAR(128) CHARACTER SET ascii,
										   spotrating INTEGER DEFAULT 0) ENGINE = MYISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsxover_1 ON commentsxover(messageid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_commentsxover_2 ON commentsxover(nntpref)");
		} # if
			
		# spotstatelist
		if (!$this->tableExists('spotstatelist')) {
			$this->_dbcon->rawExec("CREATE TABLE spotstatelist(messageid VARCHAR(128) CHARACTER SET ascii NOT NULL,
										   ouruserid INTEGER DEFAULT 0,
										   download INTEGER,
										   watch INTEGER,
										   seen INTEGER) ENGINE = InnoDB CHARSET=ascii;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spotstatelist_1 ON spotstatelist(messageid,ouruserid);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spotstatelist_2 ON spotstatelist(download);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spotstatelist_3 ON spotstatelist(watch);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spotstatelist_4 ON spotstatelist(seen);");
		} # if

		# commentsfull
		if (!$this->tableExists('commentsfull')) {
			$this->_dbcon->rawExec("CREATE TABLE commentsfull (id INTEGER PRIMARY KEY AUTO_INCREMENT,
									  messageid VARCHAR(128) CHARACTER SET ascii NOT NULL,
									  fromhdr VARCHAR(128),
									  stamp INTEGER(10) UNSIGNED,
									  usersignature VARCHAR(128),
									  userkey VARCHAR(200),
									  userid VARCHAR(32),
									  hashcash VARCHAR(128),
									  body TEXT,
									  verified BOOLEAN) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsfull_1 ON commentsfull(messageid);");
		} # if

		# settings
		if (!$this->tableExists('settings')) {
			$this->_dbcon->rawExec("CREATE TABLE settings (id INTEGER PRIMARY KEY AUTO_INCREMENT,
									  name VARCHAR(128) NOT NULL,
									  value TEXT) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_settings_1 ON settings(name);");
		} # if

		# commentsposted
		if (!$this->tableExists('commentsposted')) {
			$this->_dbcon->rawExec("CREATE TABLE commentsposted (id INTEGER PRIMARY KEY AUTO_INCREMENT,
									  ouruserid INTEGER DEFAULT 0 NOT NULL,
									  messageid VARCHAR(128) CHARACTER SET ascii NOT NULL,
									  inreplyto VARCHAR(128) CHARACTER SET ascii NOT NULL,
									  randompart VARCHAR(32) CHARACTER SET ascii NOT NULL,
									  rating INTEGER DEFAULT 0 NOT NULL,
									  body TEXT,
									  stamp INTEGER DEFAULT 0 NOT NULL) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci;");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsposted_1 ON commentsposted(messageid);");
		} # if
	} # createDatabase

	/* 
	 * optimaliseer/analyseer een aantal tables welke veel veranderen, 
	 * deze functie wijzigt geen data!
  	 */
	function analyze() { 
		$this->_dbcon->rawExec("ANALYZE TABLE spotstatelist");
		$this->_dbcon->rawExec("ANALYZE TABLE sessions");
		$this->_dbcon->rawExec("ANALYZE TABLE users");
		$this->_dbcon->rawExec("ANALYZE TABLE commentsfull");
	} # analyze
	
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
			if ($idxType == "UNIQUE") {
				$this->_dbcon->rawExec("ALTER IGNORE TABLE " . $tablename . " ADD " . $idxType . " INDEX " . $idxname . "(" . $colList . ");");
			} else {
				$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ADD " . $idxType . " INDEX " . $idxname . "(" . $colList . ");");
			}
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
	
	/* verandert een storage engine (concept dat enkel mysql kent :P ) */
	function alterStorageEngine($tablename, $engine) {
		$q = $this->_dbcon->singleQuery("SELECT ENGINE 
										FROM information_schema.TABLES 
										WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $tablename . "'");
		
	
		if (strtolower($q) != strtolower($engine)) {
			$this->_dbcon->rawExec("ALTER TABLE " . $tablename . " ENGINE=" . $engine);
		} # if
	} # alterStorageEngine

	
} # class