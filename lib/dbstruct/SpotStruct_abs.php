<?php
define('SPOTDB_SCHEMA_VERSION', '0.02');

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
		if ($this->_spotdb->getSchemaVer() == '0.01') {
			$this->dropTable('settings');
		} # if
		
		# settings tabel aanmaken als hij nog niet bestaat
		if (!$this->tableExists('settings')) {
			$this->createTable('settings');
			
			$this->addColumn('name', 'settings', 'VARCHAR(128)');
			$this->addColumn('value', 'settings', 'text');
			$this->addIndex("idx_settings_1", "UNIQUE", "settings", "name");
		} # if
		
		# voeg het database schema versie nummer toe
		$this->_spotdb->updateSetting('schemaversion', SPOTDB_SCHEMA_VERSION);
	} # updateSchema
	
} # class
