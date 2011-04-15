<?php
abstract class SpotStruct_abs {
	protected $_spotdb;
	protected $_dbcon;
	
	public function __construct($spotdb) {
		$this->_spotdb = $spotdb;
		$this->_dbcon = $spotdb->getDbHandle();
	} # __construct
	
	abstract function createDatabase();

	/* Add an index, kijkt eerst wel of deze index al bestaat */
	abstract function addIndex($idxname, $idxType, $tablename, $colList);
	
	/* dropt een index als deze bestaat */
	abstract function dropIndex($idxname, $tablename);
	
	/* voegt een column toe, kijkt wel eerst of deze nog niet bestaat */
	abstract function addColumn($colName, $tablename, $colDef);
	
	/* dropt een kolom (mits db dit ondersteunt) */
	abstract function dropColumn($colName, $tablename);
	
	/* controleert of een index bestaat */
	abstract function indexExists($tablename, $idxname);
	
	/* controleert of een kolom bestaat */
	abstract function columnExists($tablename, $colname);

	/* controleert of een tabel bestaat */
	abstract function tableExists($tablename);

	/* ceeert een lege tabel met enkel een ID veld */
	abstract function createTable($tablename);

	/* drop een table */
	abstract function dropTable($tablename);
	
	function updateSchema() {
		# Fulltext indexes
		$this->addIndex("idx_spots_fts_1", "FULLTEXT", "spots", "title");
		$this->addIndex("idx_spots_fts_2", "FULLTEXT", "spots", "poster");
		$this->addIndex("idx_spots_fts_3", "FULLTEXT", "spots", "tag");
		$this->addIndex("idx_spotsfull_fts_3", "FULLTEXT", "spotsfull", "userid");
		
		# We voegen een reverse timestamp toe omdat MySQL MyISAM niet goed kan reverse sorteren 
		if (!$this->columnExists('spots', 'reversestamp')) {
			$this->addColumn("reversestamp", "spots", "INTEGER DEFAULT 0");
			$this->_dbcon->rawExec("UPDATE spots SET reversestamp = (stamp*-1)");
		} # if
		$this->addIndex("idx_spots_6", "", "spots", "reversestamp");

		# voeg de subcatz kolom toe zodat we hier in een type spot kunnen kenmerken
		if (!$this->columnExists('spots', 'subcatz')) {
			$this->addColumn("subcatz", "spots", "VARCHAR(64)");
		} # if

		# commentsfull tabel aanmaken als hij nog niet bestaat
		if (!$this->tableExists('commentsfull')) {
			$this->createTable('commentsfull');
			
			$this->addColumn('messageid', 'commentsfull', 'VARCHAR(128)');
			$this->addColumn('fromhdr', 'commentsfull', 'VARCHAR(128)');
			$this->addColumn('stamp', 'commentsfull', 'INTEGER');
			$this->addColumn('usersignature', 'commentsfull', 'VARCHAR(128)');
			$this->addColumn('userkey', 'commentsfull', 'VARCHAR(128)');
			$this->addColumn('userid', 'commentsfull', 'VARCHAR(128)');
			$this->addColumn('hashcash', 'commentsfull', 'VARCHAR(128)');
			$this->addColumn('body', 'commentsfull', 'TEXT');
			$this->addColumn('verified', 'commentsfull', 'BOOLEAN');
			$this->addIndex("idx_commentsfull_1", "UNIQUE", "commentsfull", "messageid");
			$this->addIndex("idx_commentsfull_2", "", "commentsfull", "messageid,stamp");
		} # if

		# voeg de spotrating kolom toe
		if (!$this->columnExists('commentsxover', 'spotrating')) {
			$this->addColumn("spotrating", "commentsxover", "INTEGER DEFAULT 0");
		} # if

		# voeg de ouruserid kolom toe aan de watchlist tabel
		if (!$this->columnExists('watchlist', 'ouruserid')) {
			$this->addColumn("ouruserid", "watchlist", "INTEGER DEFAULT 0");
		} # if

		# voeg de ouruserid kolom toe aan de downloadlist tabel
		if (!$this->columnExists('downloadlist', 'ouruserid')) {
			$this->addColumn("ouruserid", "downloadlist", "INTEGER DEFAULT 0");
		} # if
		
		# als het schema 0.01 is, dan is value een varchar(128) veld, maar daar
		# past geen RSA key in dus dan droppen we de tabel
		if ($this->tableExists('settings')) {
			if ($this->_spotdb->getSchemaVer() == '0.01') {
				$this->dropTable('settings');
			} # if
		} # if
		
		# settings tabel aanmaken als hij nog niet bestaat
		if (!$this->tableExists('settings')) {
			$this->createTable('settings');
			
			$this->addColumn('name', 'settings', 'VARCHAR(128)');
			$this->addColumn('value', 'settings', 'text');
			$this->addIndex("idx_settings_1", "UNIQUE", "settings", "name");
		} # if
		
		# Collation en dergelijke zijn alleen van toepassing op MySQL, we 
		# zetten alle collation exact hetzelfde zodat de indexes beter
		# gebruikt kunnen worden.
		if (($this instanceof SpotStruct_mysql) && ($this->_spotdb->getSchemaVer() < 0.03)) {
			echo "Huge upgrade of database, this might take up to 60 minutes or more!" . PHP_EOL;
			echo "Converting default charset to UTF8 (1/10)" . PHP_EOL;

			# We veranderen eerst de standaard collation settings zodat we in de toekomst
			# hier niet al te veel meer op moeten letten
			$this->_dbcon->rawExec("ALTER TABLE commentsfull CHARSET=utf8 COLLATE=utf8_unicode_ci");
			$this->_dbcon->rawExec("ALTER TABLE commentsxover CHARSET=utf8 COLLATE=utf8_unicode_ci");
			$this->_dbcon->rawExec("ALTER TABLE downloadlist CHARSET=utf8 COLLATE=utf8_unicode_ci");
			$this->_dbcon->rawExec("ALTER TABLE nntp CHARSET=utf8 COLLATE=utf8_unicode_ci");
			$this->_dbcon->rawExec("ALTER TABLE settings CHARSET=utf8 COLLATE=utf8_unicode_ci");
			$this->_dbcon->rawExec("ALTER TABLE spots CHARSET=utf8 COLLATE=utf8_unicode_ci");
			$this->_dbcon->rawExec("ALTER TABLE spotsfull CHARSET=utf8 COLLATE=utf8_unicode_ci");
			$this->_dbcon->rawExec("ALTER TABLE watchlist CHARSET=utf8 COLLATE=utf8_unicode_ci");
		

			echo "Converting comments full fields to UTF8 (2/10)" . PHP_EOL;
	
			# en vervolgens alteren we elk tekst veld
			$this->_dbcon->rawExec("ALTER TABLE commentsfull MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE commentsfull MODIFY fromhdr VARCHAR(128) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE commentsfull MODIFY usersignature VARCHAR(128) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE commentsfull MODIFY userkey VARCHAR(200) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE commentsfull MODIFY userid VARCHAR(32) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE commentsfull MODIFY hashcash VARCHAR(128) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE commentsfull MODIFY body TEXT CHARACTER SET utf8");

			echo "Converting commentsxover fields to UTF8 (3/10)" . PHP_EOL;

			$this->_dbcon->rawExec("ALTER TABLE commentsxover MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE commentsxover MODIFY nntpref VARCHAR(128) CHARACTER SET ascii NOT NULL");

			$this->_dbcon->rawExec("ALTER TABLE downloadlist MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");

			$this->_dbcon->rawExec("ALTER TABLE nntp MODIFY server VARCHAR(128) CHARACTER SET utf8");

			$this->_dbcon->rawExec("ALTER TABLE settings MODIFY name VARCHAR(128) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE settings MODIFY value TEXT CHARACTER SET utf8");

			echo "Converting spots fields to UTF8 (3/10)" . PHP_EOL;

			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY poster VARCHAR(128) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY groupname VARCHAR(128) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY subcata VARCHAR(64) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY subcatb VARCHAR(64) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY subcatc VARCHAR(64) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY subcatd VARCHAR(64) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY title VARCHAR(128) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY tag VARCHAR(128) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY subcatz VARCHAR(64) CHARACTER SET utf8");

			echo "Converting spotsfull fields to UTF8 (4/10)" . PHP_EOL;

			$this->_dbcon->rawExec("ALTER TABLE spotsfull MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE spotsfull MODIFY userid VARCHAR(32) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spotsfull MODIFY usersignature VARCHAR(128) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spotsfull MODIFY userkey VARCHAR(200) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spotsfull MODIFY xmlsignature VARCHAR(128) CHARACTER SET utf8");
			$this->_dbcon->rawExec("ALTER TABLE spotsfull MODIFY fullxml TEXT CHARACTER SET utf8");

			$this->_dbcon->rawExec("ALTER TABLE watchlist MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE watchlist MODIFY comment TEXT CHARACTER SET utf8 NOT NULL");

			echo "Dropping indexes (5/10)" . PHP_EOL;

			# Nu droppen we alle indexes en bouwen die opnieuw op, we doen dit 
			# omdat legacy databases soms nog indexes hebben die niet meer kloppen 
			# doordat upgrades niet altijd goed zijn gegaan
			if ($this->indexExists('spots', 'idx_spots_1'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spots DROP INDEX idx_spots_1");
			if ($this->indexExists('spots', 'idx_spots_2'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spots DROP INDEX idx_spots_2");
			if ($this->indexExists('spots', 'idx_spots_3'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spots DROP INDEX idx_spots_3");
			if ($this->indexExists('spots', 'idx_spots_4'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spots DROP INDEX idx_spots_4");
			if ($this->indexExists('spots', 'idx_spots_5'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spots DROP INDEX idx_spots_5");
			if ($this->indexExists('spots', 'idx_spots_6'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spots DROP INDEX idx_spots_6");

			if ($this->indexExists('spotsfull', 'idx_spotsfull_1'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spotsfull DROP INDEX idx_spotsfull_1");
			if ($this->indexExists('spotsfull', 'idx_spotsfull_2'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spotsfull DROP INDEX idx_spotsfull_2");
			if ($this->indexExists('spotsfull', 'idx_spotsfull_3'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spotsfull DROP INDEX idx_spotsfull_3");
			if ($this->indexExists('spotsfull', 'idx_watchlist_1'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spotsfull DROP INDEX idx_watchlist_1");
			if ($this->indexExists('spotsfull', 'idx_spotsfull_fts_3'))
				$this->_dbcon->rawExec("ALTER IGNORE TABLE spotsfull DROP INDEX idx_spotsfull_fts_3");
			
			# en maak nieuwe indexen aan
			echo "Creating index on spots (6/10)" . PHP_EOL;
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spots_1 ON spots(messageid);");
			echo "Creating index on spots (7/10)" . PHP_EOL;
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_2 ON spots(stamp);");
			echo "Creating index on spots (8/10)" . PHP_EOL;
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_3 ON spots(reversestamp);");
			echo "Creating index on spots (9/10)" . PHP_EOL;
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_4 ON spots(category, subcata, subcatb, subcatc, subcatd, subcatz DESC);");

			echo "Creating index on spotsfull (10/10)" . PHP_EOL;
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spotsfull_1 ON spotsfull(messageid);");

			echo "Upgrade done." . PHP_EOL;
		} # if

		# Nu we subcatz hebben, update dan alle spots zodat dit ook ingevuld is om de database
		# helemaal consistent te houden, zie https://github.com/spotweb/spotweb/commit/d4351f7dc8665699c83c8571c850b08b72fe05d0
		if ($this->_spotdb->getSchemaVer() < 0.05) {
			# Films
			$this->_dbcon->rawExec("UPDATE spots SET subcatz = 'z0|'
										WHERE (Category = 0) ");

			# Erotiek
			$this->_dbcon->rawExec("UPDATE spots SET subcatz = 'z3|'
										WHERE (Category = 0) 
											AND 
										( (subcatd like '%d23|%') OR (subcatd like '%d24|%') OR (subcatd like '%d25|%') 
										   OR (subcatd like '%d72|%')  OR (subcatd like '%d73|%') OR (subcatd like '%d74|%')
										   OR (subcatd like '%d75|%')  OR (subcatd like '%d76|%') OR (subcatd like '%d77|%')
										   OR (subcatd like '%d78|%')  OR (subcatd like '%d79|%') OR (subcatd like '%d80|%')
										   OR (subcatd like '%d81|%')  OR (subcatd like '%d82|%') OR (subcatd like '%d83|%')
										   OR (subcatd like '%d84|%')  OR (subcatd like '%d85|%') OR (subcatd like '%d86|%')
										   OR (subcatd like '%d87|%')  OR (subcatd like '%d88|%') OR (subcatd like '%d89|%')
										)");

			# Series
			$this->_dbcon->rawExec("UPDATE spots SET subcatz = 'z1|'
										WHERE (Category = 0) 
											AND 
										( (subcatd like '%b4|%') OR (subcatd like '%d11|%') )");

			# Boeken
			$this->_dbcon->rawExec("UPDATE spots SET subcatz = 'z2|'
										WHERE (Category = 0) 
											AND 
										(subcata = 'a5|')");

			# Muziek
			$this->_dbcon->rawExec("UPDATE spots SET subcatz = 'z0|'
										WHERE (Category = 1) ");

			# de rest
			$this->_dbcon->rawExec("UPDATE spots SET subcatz = ''
										WHERE subcatz IS NULL");
		} # if
		
		# Collation en dergelijke zijn alleen van toepassing op MySQL, we 
		# zetten alle collation exact hetzelfde zodat de indexes beter
		# gebruikt kunnen worden.
		if (($this instanceof SpotStruct_mysql) && ($this->_spotdb->getSchemaVer() < 0.06)) {
			$this->_dbcon->rawExec("ALTER TABLE commentsfull MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE commentsxover MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE downloadlist MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE commentsxover MODIFY nntpref VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE spots MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE spotsfull MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
			$this->_dbcon->rawExec("ALTER TABLE watchlist MODIFY messageid VARCHAR(128) CHARACTER SET ascii NOT NULL");
		} # if
		
		if (($this instanceof SpotStruct_mysql) && ($this->_spotdb->getSchemaVer() < 0.07)) {
			$this->dropIndex("idx_downloadlist_1", "downloadlist");
			$this->addIndex("idx_downloadlist_1", "UNIQUE", "downloadlist", "messageid");
		} # if
		
		# voeg het database schema versie nummer toe
		$this->_spotdb->updateSetting('schemaversion', SPOTDB_SCHEMA_VERSION);
	} # updateSchema
	
} # class
