<?php
abstract class SpotStruct_abs {
	protected $_spotdb;
	protected $_dbcon;

	public function __construct(SpotDb $spotdb) {
		$this->_spotdb = $spotdb;
		$this->_dbcon = $spotdb->getDbHandle();
	} # __construct

	/*
	 * optimaliseer/analyseer een aantal tables welke veel veranderen,
	 * deze functie wijzigt geen data!
	 */
	abstract function analyze();

	/* converteert een "spotweb" datatype naar een mysql datatype */
	abstract function swDtToNative($colType);

	/* converteert een mysql datatype naar een "spotweb" datatype */
	abstract function nativeDtToSw($colInfo);
	
	/*
	 * Add an index, kijkt eerst wel of deze index al bestaat,
	 * $idxType kan danwel 'UNIQUE' danwel 'FULLTEXT' zijn
	 */
	abstract function addIndex($idxname, $idxType, $tablename, $colList);

	/* dropt een index als deze bestaat */
	abstract function dropIndex($idxname, $tablename);

	/* voegt een column toe, kijkt wel eerst of deze nog niet bestaat */
	abstract function addColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation);

	/* wijzigt een column - controleert *niet* of deze voldoet aan het prototype */
	abstract function modifyColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation, $what);

	/* dropt een kolom (mits db dit ondersteunt) */
	abstract function dropColumn($colName, $tablename);

	/* controleert of een index bestaat */
	abstract function indexExists($idxname, $tablename);

	/* controleert of een kolom bestaat */
	abstract function columnExists($tablename, $colname);

	/* controleert of een tabel bestaat */
	abstract function tableExists($tablename);

	/* ceeert een lege tabel met enkel een ID veld, collation kan UTF8 of ASCII zijn */
	abstract function createTable($tablename, $collation);

	/* creeert een foreign key constraint */
	abstract function addForeignKey($tablename, $colname, $reftable, $refcolumn, $action);
	
	/* dropped een foreign key constraint */
	abstract function dropForeignKey($tablename, $colname, $reftable, $refcolumn, $action);
	
	/* verandert een storage engine (concept dat enkel mysql kent :P ) */
	abstract function alterStorageEngine($tablename, $engine);

	/* drop een table */
	abstract function dropTable($tablename);
	
	/* rename een table */
	abstract function renameTable($tablename, $newTableName);

	/* Geeft, in een afgesproken formaat, de index informatie terug */
	abstract function getIndexInfo($idxname, $tablename);
	
	/* Geeft, in een afgesproken formaat, de index formatie terug */
	abstract function getColumnInfo($tablename, $colname);
	
	/* controleert of de index structuur hetzelfde is als de gewenste, zo niet, maak hem opnieuw aan */
	function validateIndex($idxname, $type, $tablename, $colList) {
		echo "\tValidating index " . $idxname . PHP_EOL;
		
		if (!$this->compareIndex($idxname, $type, $tablename, $colList)) {
			# Drop de index
			if ($this->indexExists($idxname, $tablename)) {
				echo "\t\tDropping index " . $idxname . PHP_EOL;
				$this->dropIndex($idxname, $tablename);
			} # if
			
			echo "\t\tAdding index " . $idxname . PHP_EOL;
			
			# en creeer hem opnieuw
			$this->addIndex($idxname, $type, $tablename, $colList);
		} # if
	} # validateIndex

	/* controleert of de index structuur hetzelfde is als de gewenste, zo niet, maak hem opnieuw aan */
	function validateColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation) {
		echo "\tValidating " . $tablename . "(" . $colName . ")" . PHP_EOL;

		$compResult = $this->compareColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation);
		if ($compResult !== true) {
			if ($this->columnExists($tablename, $colName)) {
				echo "\t\tModifying column " . $colName . " (" . $compResult . ") on " . $tablename . PHP_EOL;
				$this->modifyColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation, $compResult);
			} else {
				echo "\t\tAdding column " . $colName . "(" . $colType . ") to " . $tablename . PHP_EOL;
				$this->addColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation);
			} # else
		} # if
	} # validateIndex
	
	/* vergelijkt een column met de gewenste structuur */
	function compareColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation) {
		# Vraag nu de column informatie op
		$q = $this->getColumnInfo($tablename, $colName);
		
		# Als de column helemaal niet gevonden wordt..
		if (empty($q)) {
			return false;
		} # if
		
		# controleer het type
		if (strtolower($q['COLUMN_TYPE']) != strtolower($this->swDtToNative($colType))) {
			#var_dump($q);
			#var_dump($colType);
			#var_dump($this->swDtToNative($colType));
			return 'type';
		} # if

		# controleer default
		if (strtolower($q['COLUMN_DEFAULT']) != strtolower($colDefault)) {
			return 'default';
		} # if

		# controleer NOT NULL setting
		if (strtolower($q['NOTNULL']) != $notNull) {
			return 'not null';
		} # if

		# controleer NOT NULL setting
		if (strtolower($q['CHARACTER_SET_NAME']) != $collation) {
			return 'charset';
		} # if
		
		return true;
	} # compareColumn

	
	/* vergelijkt een index met de gewenste structuur */
	function compareIndex($idxname, $type, $tablename, $colList) {
		# Vraag nu de index informatie op
		$q = $this->getIndexInfo($idxname, $tablename);
		
		# Als het aantal kolommen niet gelijk is
		if (count($q) != count($colList)) {
			return false;
		} # if
		
		# we loopen vervolgens door elke index kolom heen, en vergelijken
		# dan of ze in dezelfde volgorde staan en dezelfde eigenschappen hebben
		for($i = 0; $i < count($colList); $i++) {
			$same = true;
			
			if ($colList[$i] != $q[$i]['column_name']) {
				$same = false;
			} # if

			if ($same) {
				switch(strtolower($type)) {
					case 'fulltext'		: $same = ($q[$i]['index_type'] == 'fulltext'); break;
					case 'unique'		: $same = ($q[$i]['non_unique'] == 0); break;
					case ''				: $same = ($q[$i]['index_type'] != 'fulltext') && ($q[$i]['non_unique'] == 1);
				} # switch
			} # if
			
			if (!$same) {
				return false;
			} # if
		} # for
		
		return true;
	} # compareIndex
	

	function updateSchema() {
		# drop eventueel FTS indexes op de spotsfull tabel
		$this->dropIndex("idx_spotsfull_fts_1", "spotsfull");
		$this->dropIndex("idx_spotsfull_fts_2", "spotsfull");
		$this->dropIndex("idx_spotsfull_fts_3", "spotsfull");
		
		# ---- spots table ---- #
		$this->createTable('spots', "utf8"); 
		$this->validateColumn('messageid', 'spots', 'VARCHAR(128)', "''", true, 'ascii');
		$this->validateColumn('poster', 'spots', 'VARCHAR(128)', NULL, false, 'utf8');
		$this->validateColumn('title', 'spots', 'VARCHAR(128)', NULL, false, 'utf8');
		$this->validateColumn('tag', 'spots', 'VARCHAR(128)', NULL, false, 'utf8');
		$this->validateColumn('category', 'spots', 'INTEGER', NULL, false, '');
		$this->validateColumn('subcata', 'spots', 'VARCHAR(64)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('subcatb', 'spots', 'VARCHAR(64)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('subcatc', 'spots', 'VARCHAR(64)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('subcatd', 'spots', 'VARCHAR(64)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('subcatz', 'spots', 'VARCHAR(64)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('stamp', 'spots', 'UNSIGNED INTEGER', NULL, false, '');
		$this->validateColumn('reversestamp', 'spots', 'INTEGER', "0", false, '');
		$this->validateColumn('filesize', 'spots', 'UNSIGNED BIGINTEGER', "0", true, '');
		$this->validateColumn('moderated', 'spots', 'BOOLEAN', NULL, false, '');
		$this->validateColumn('commentcount', 'spots', 'INTEGER', "0", false, '');
		$this->validateColumn('spotrating', 'spots', 'INTEGER', "0", false, '');
		$this->alterStorageEngine("spots", "MyISAM");
		
		# ---- spotsfull table ---- #
		$this->createTable('spotsfull', "utf8"); 
		$this->validateColumn('messageid', 'spotsfull', 'VARCHAR(128)', "''", true, 'ascii');
		$this->validateColumn('userid', 'spotsfull', 'VARCHAR(32)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('verified', 'spotsfull', 'BOOLEAN', NULL, false, '');
		$this->validateColumn('usersignature', 'spotsfull', 'VARCHAR(128)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('userkey', 'spotsfull', 'VARCHAR(200)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('xmlsignature', 'spotsfull', 'VARCHAR(128)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('fullxml', 'spotsfull', 'TEXT', NULL, false, 'utf8');
		$this->alterStorageEngine("spotsfull", "InnoDB");
	
		# ---- nntp table ---- #
		$this->createTable('nntp', "utf8"); 
		$this->validateColumn('server', 'nntp', 'VARCHAR(128)', "''", true, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('maxarticleid', 'nntp', 'INTEGER', NULL, false, '');
		$this->validateColumn('nowrunning', 'nntp', 'INTEGER', "0", false, '');
		$this->validateColumn('lastrun', 'nntp', 'INTEGER', "0", false, '');
		$this->alterStorageEngine("spotsfull", "InnoDB");
		
		# ---- commentsxover table ---- #
		$this->createTable('commentsxover', "ascii"); 
		$this->validateColumn('messageid', 'commentsxover', 'VARCHAR(128)', "''", true, 'ascii');
		$this->validateColumn('nntpref', 'commentsxover', 'VARCHAR(128)', "''", true, 'ascii');
		$this->validateColumn('spotrating', 'commentsxover', 'INTEGER', "0", false, '');
		$this->alterStorageEngine("commentsxover", "InnoDB");

		# ---- spotstatelist table ---- #
		$this->createTable('spotstatelist', "ascii"); 
		$this->validateColumn('messageid', 'spotstatelist', 'VARCHAR(128)', "''", true, 'ascii');
		$this->validateColumn('ouruserid', 'spotstatelist', 'INTEGER', "0", false, '');
		$this->validateColumn('download', 'spotstatelist', 'INTEGER', NULL, false, '');
		$this->validateColumn('watch', 'spotstatelist', 'INTEGER', NULL, false, '');
		$this->validateColumn('seen', 'spotstatelist', 'INTEGER', NULL, false, '');
		$this->alterStorageEngine("spotstatelist", "InnoDB");
		
		# ---- commentsfull table ---- #
		$this->createTable('commentsfull', "ascii"); 
		$this->validateColumn('messageid', 'commentsfull', 'VARCHAR(128)', "''", true, 'ascii');
		$this->validateColumn('fromhdr', 'commentsfull', 'VARCHAR(128)', NULL, false, 'utf8');
		$this->validateColumn('stamp', 'commentsfull', 'INTEGER', NULL, false, '');
		$this->validateColumn('usersignature', 'commentsfull', 'VARCHAR(128)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('userkey', 'commentsfull', 'VARCHAR(200)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('userid', 'commentsfull', 'VARCHAR(32)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('hashcash', 'commentsfull', 'VARCHAR(128)', NULL, false, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('body', 'commentsfull', 'TEXT', NULL, false, 'utf8');
		$this->validateColumn('verified', 'commentsfull', 'BOOLEAN', NULL, false, '');
		$this->alterStorageEngine("commentsfull", "InnoDB");
											
		# ---- settings table ---- #
		$this->createTable('settings', "ascii"); 
		$this->validateColumn('name', 'settings', 'VARCHAR(128)', "''", true, 'utf8'); # FIXME: charset kan ook ascii worden
		$this->validateColumn('value', 'settings', 'TEXT', NULL, false, 'utf8');
		$this->validateColumn('serialized', 'settings', 'boolean', NULL, false, '');
		$this->alterStorageEngine("settings", "InnoDB");

		# ---- commentsposted table ---- #
		$this->createTable('commentsposted', "ascii"); 
		$this->validateColumn('ouruserid', 'commentsposted', 'INTEGER', "0", true, '');
		$this->validateColumn('messageid', 'commentsposted', 'VARCHAR(128)', "''", true, 'ascii');
		$this->validateColumn('inreplyto', 'commentsposted', 'VARCHAR(128)', "''", true, 'ascii');
		$this->validateColumn('randompart', 'commentsposted', 'VARCHAR(32)', "''", true, 'ascii');
		$this->validateColumn('rating', 'commentsposted', 'INTEGER', 0, true, '');
		$this->validateColumn('body', 'commentsposted', 'TEXT', NULL, false, 'utf8');
		$this->validateColumn('stamp', 'commentsposted', 'INTEGER', "0", true, '');
		$this->alterStorageEngine("commentsposted", "InnoDB");
		
		# ---- usersettings table ---- #
		$this->createTable('usersettings', "utf8"); 
		$this->validateColumn('userid', 'usersettings', 'INTEGER', '0', true, '');
		$this->validateColumn('privatekey', 'usersettings', "TEXT", NULL, false, 'ascii');
		$this->validateColumn('publickey', 'usersettings', "TEXT", NULL, false, 'ascii');
		$this->validateColumn('otherprefs', 'usersettings', "TEXT", NULL, false, 'utf8');
		$this->alterStorageEngine("usersettings", "InnoDB");
	
		# ---- users table ---- #
		$this->createTable('users', "utf8"); 
		$this->validateColumn('username', 'users', "VARCHAR(128)", "''", true, 'utf8');
		$this->validateColumn('firstname', 'users', "VARCHAR(128)", "''", true, 'utf8');
		$this->validateColumn('passhash', 'users', "VARCHAR(40)", "''", true, 'ascii');
		$this->validateColumn('lastname', 'users', "VARCHAR(128)", "''", true, 'utf8');
		$this->validateColumn('mail', 'users', "VARCHAR(128)", "''", true, 'utf8');
		$this->validateColumn('apikey', 'users', "VARCHAR(32)", "''", true, 'ascii');
		$this->validateColumn('lastlogin', 'users', "INTEGER", "0", true, '');
		$this->validateColumn('lastvisit', 'users', "INTEGER", "0", true, '');
		$this->validateColumn('lastread', 'users', "INTEGER", "0", true, '');
		$this->validateColumn('lastapiusage', 'users', "INTEGER", "0", true, '');
		$this->validateColumn('deleted', 'users', "BOOLEAN", "0", true, '');
		$this->alterStorageEngine("users", "InnoDB");

		# ---- sessions ---- #
		$this->createTable('sessions', "ascii");
		$this->validateColumn('sessionid', 'sessions', 'VARCHAR(128)', NULL, false, 'ascii');
		$this->validateColumn('userid', 'sessions', 'INTEGER', NULL, false, '');
		$this->validateColumn('hitcount', 'sessions', 'INTEGER', NULL, false, '');
		$this->validateColumn('lasthit', 'sessions', 'INTEGER', NULL, false, '');
		$this->alterStorageEngine("sessions", "InnoDB");

		# ---- securitygroups ----
		$this->createTable('securitygroups', "ascii"); 
		$this->validateColumn('name', 'securitygroups', 'VARCHAR(128)', NULL, false, 'ascii');

		# ---- grouppermissions ----
		$this->createTable('grouppermissions', "ascii"); 
		$this->validateColumn('groupid', 'grouppermissions', 'INTEGER', "0", true, '');
		$this->validateColumn('permissionid', 'grouppermissions', 'INTEGER', "0", true, '');
		$this->validateColumn('objectid', 'grouppermissions', "VARCHAR(128)", "''", true, 'ascii');
		$this->validateColumn('deny', 'grouppermissions', "BOOLEAN", "0", true, ''); 
		
		# ---- usergroups ----
		$this->createTable('usergroups', "ascii"); 
		$this->validateColumn('userid', 'usergroups', 'INTEGER', "0", true, '');
		$this->validateColumn('groupid', 'usergroups', 'INTEGER', "0", true, '');
		$this->validateColumn('prio', 'usergroups', 'INTEGER', '1', true, '');

		##############################################################################################
		### deprecation van oude Spotweb versies #####################################################
		##############################################################################################
		if ($this->_spotdb->getSchemaVer() > 0.00 && ($this->_spotdb->getSchemaVer() < 0.30)) {
			throw new Exception("Je hudige Spotweb database installatie is te oud om in een keer te upgraden naar deze versie." . PHP_EOL .
							    "Download een eerdere versie van spotweb (https://download.github.com/spotweb-spotweb-da6ba29.zip), " . PHP_EOL . 
								"draai daarmee upgrade-db.php en als die succesvol is, start dan nogmaals de upgrade via deze versie");
		} # if

		##############################################################################################
		# Opschonen data #############################################################################
		##############################################################################################
		if (($this instanceof SpotStruct_mysql) && (false)) {
			echo "Cleaning up old data..." . PHP_EOL;
			$this->_dbcon->rawExec("DELETE usersettings FROM usersettings LEFT JOIN users ON usersettings.userid=users.id WHERE users.id IS NULL;");
			$this->_dbcon->rawExec("DELETE sessions FROM sessions LEFT JOIN users ON sessions.userid=users.id WHERE users.id IS NULL;");
			$this->_dbcon->rawExec("DELETE spotstatelist FROM spotstatelist LEFT JOIN users ON spotstatelist.ouruserid=users.id WHERE users.id IS NULL;");
			$this->_dbcon->rawExec("DELETE usergroups FROM usergroups LEFT JOIN users ON usergroups.userid=users.id WHERE users.id IS NULL;");
			$this->_dbcon->rawExec("DELETE usergroups FROM usergroups LEFT JOIN securitygroups ON usergroups.groupid=securitygroups.id WHERE securitygroups.id IS NULL;");
			$this->_dbcon->rawExec("DELETE grouppermissions FROM grouppermissions LEFT JOIN securitygroups ON grouppermissions.groupid=securitygroups.id WHERE securitygroups.id IS NULL;");
			$this->_dbcon->rawExec("DELETE commentsposted FROM commentsposted LEFT JOIN users ON commentsposted.ouruserid=users.id WHERE users.id IS NULL;");
			$this->_dbcon->rawExec("DELETE commentsposted FROM commentsposted LEFT JOIN spots ON commentsposted.inreplyto=spots.messageid WHERE spots.messageid IS NULL;");
			$this->_dbcon->rawExec("DELETE spotsfull FROM spotsfull LEFT JOIN spots ON spotsfull.messageid=spots.messageid WHERE spots.messageid IS NULL;");
			$this->_dbcon->rawExec("DELETE spotstatelist FROM spotstatelist LEFT JOIN spots ON spotstatelist.messageid=spots.messageid WHERE spots.messageid IS NULL;");
		} # if

		# Tabellen terug samenvoegen en naar MyISAM converteren samenvoegen
		if (($this->_spotdb->getSchemaVer() < 0.34) && ($this->tableExists('spottexts'))) {
			$this->_dbcon->rawExec("CREATE TABLE spotstmp(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
										messageid varchar(128) CHARACTER SET ascii NOT NULL,
										poster varchar(128),
										title varchar(128),
										tag varchar(128),
										category INTEGER, 
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
										spotrating INTEGER DEFAULT 0) ENGINE = MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
			
			# Copieer de data uit de andere tabellen
			$this->_dbcon->rawExec("INSERT INTO spotstmp(messageid, poster, title, tag, category, 
														 subcata, subcatb, subcatc, subcatd, 
														 subcatz, stamp, reversestamp, filesize, 
														 moderated, commentcount, spotrating) 
										(SELECT s.messageid, t.poster, t.title, t.tag, 
												s.category, 
												s.subcata, s.subcatb, s.subcatc, 
												s.subcatd, s.subcatz, s.stamp, 
												s.reversestamp, s.filesize, s.moderated, 
												s.commentcount, s.spotrating 
											FROM spots s 
											JOIN spottexts t ON (s.messageid = t.messageid))");

			# relaties wissen
			$this->dropForeignKey('spotsfull', 'messageid', 'spots', 'messageid', 'ON DELETE CASCADE ON UPDATE CASCADE');
			$this->dropForeignKey('spotstatelist', 'messageid', 'spots', 'messageid', 'ON DELETE CASCADE ON UPDATE CASCADE');
			$this->dropForeignKey('commentsposted', 'inreplyto', 'spots', 'messageid', 'ON DELETE CASCADE ON UPDATE CASCADE');
			$this->dropForeignKey('commentsposted', 'messageid', 'spots', 'messageid', 'ON DELETE CASCADE ON UPDATE CASCADE');
			$this->dropForeignKey('commentsxover', 'messageid', 'spots', 'messageid', 'ON DELETE CASCADE ON UPDATE CASCADE');
			$this->dropForeignKey('commentsfull', 'messageid', 'spots', 'messageid', 'ON DELETE CASCADE ON UPDATE CASCADE');

			# drop de 'oude' tabellen
			$this->dropTable('spots');
			$this->dropTable('spottexts');
			
			# rename deze tabel
			$this->renameTable('spotstmp', 'spots');
		} # if

		# En creeer de diverse indexen
		# ---- Indexen op spots -----
		$this->validateIndex("idx_spots_1", "UNIQUE", "spots", array("messageid"));
		$this->validateIndex("idx_spots_2", "", "spots", array("stamp"));
		$this->validateIndex("idx_spots_3", "", "spots", array("reversestamp"));
		$this->validateIndex("idx_spots_4", "", "spots", array("category", "subcata", "subcatb", "subcatc", "subcatd", "subcatz"));
		$this->validateIndex("idx_fts_spots_1", "FULLTEXT", "spots", array("poster"));
		$this->validateIndex("idx_fts_spots_2", "FULLTEXT", "spots", array("title"));
		$this->validateIndex("idx_fts_spots_3", "FULLTEXT", "spots", array("tag"));

		# ---- Indexen op nntp ----
		$this->validateIndex("idx_nntp_1", "UNIQUE", "nntp", array("server"));
		
		# ---- Indexen op spotsfull ----
		$this->validateIndex("idx_spotsfull_1", "UNIQUE", "spotsfull", array("messageid"));
		$this->validateIndex("idx_spotsfull_2", "", "spotsfull", array("userid"));

		# ---- Indexen op commentsfull ----
		$this->validateIndex("idx_commentsfull_1", "UNIQUE", "commentsfull", array("messageid"));

		# ---- Indexen op commentsxover ----
		$this->validateIndex("idx_commentsxover_1", "UNIQUE", "commentsxover", array("messageid"));
		$this->validateIndex("idx_commentsxover_2", "", "commentsxover", array("nntpref"));

		# ---- Indexen op commentsposted ----
		$this->validateIndex("idx_commentsposted_1", "UNIQUE", "commentsposted", array("messageid"));

		# ---- Indexen op settings ----
		$this->validateIndex("idx_settings_1", "UNIQUE", "settings", array("name"));

		# ---- Indexen op usersettings ----
		$this->validateIndex("idx_usersettings_1", "UNIQUE", "usersettings", array("userid"));

		# ---- Indexen op users ----
		$this->validateIndex("idx_users_1", "UNIQUE", "users", array("username"));
		$this->validateIndex("idx_users_2", "UNIQUE", "users", array("mail"));
		$this->validateIndex("idx_users_3", "", "users", array("deleted"));
		$this->validateIndex("idx_users_4", "UNIQUE", "users", array("apikey"));

		# ---- Indexen op sessions
		$this->validateIndex("idx_sessions_1", "UNIQUE", "sessions", array("sessionid"));
		$this->validateIndex("idx_sessions_2", "", "sessions", array("lasthit"));
		$this->validateIndex("idx_sessions_3", "", "sessions", array("sessionid", "userid"));
		$this->validateIndex("idx_sessionsrel_1", "", "sessions", array("userid"));

		# ---- Indexen op spotstatelist ----
		$this->validateIndex("idx_spotstatelistrel_1", "", "spotstatelist", array("ouruserid"));

		# ---- Indexen op securitygroups ----
		$this->validateIndex("idx_securitygroups_1", "UNIQUE", "securitygroups", array("name"));

		# ---- Indexen op grouppermissions ----
		$this->validateIndex("idx_grouppermissions_1", "UNIQUE", "grouppermissions", array("groupid", "permissionid", "objectid"));

		# ---- Indexen op usergroups ----
		$this->validateIndex("idx_usergroups_1", "UNIQUE", "usergroups", array("userid", "groupid"));
		$this->validateIndex("idx_usergroupsrel_1", "", "usergroups", array("groupid"));

		# leg foreign keys aan
		$this->addForeignKey('usersettings', 'userid', 'users', 'id', 'ON DELETE CASCADE ON UPDATE CASCADE');
		$this->addForeignKey('sessions', 'userid', 'users', 'id', 'ON DELETE CASCADE ON UPDATE CASCADE');
		$this->addForeignKey('spotstatelist', 'ouruserid', 'users', 'id', 'ON DELETE CASCADE ON UPDATE CASCADE');
		$this->addForeignKey('usergroups', 'userid', 'users', 'id', 'ON DELETE CASCADE ON UPDATE CASCADE');
		$this->addForeignKey('usergroups', 'groupid', 'securitygroups', 'id', 'ON DELETE CASCADE ON UPDATE CASCADE');
		$this->addForeignKey('grouppermissions', 'groupid', 'securitygroups', 'id', 'ON DELETE CASCADE ON UPDATE CASCADE');
		
		##############################################################################################
		# Hier droppen we kolommen ###################################################################
		##############################################################################################
		$this->dropColumn('filesize', 'spotsfull');
		
		# voeg het database schema versie nummer toe
		$this->_spotdb->updateSetting('schemaversion', SPOTDB_SCHEMA_VERSION);
	} # updateSchema

} # class
