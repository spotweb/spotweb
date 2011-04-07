<?php

abstract class SpotStruct_abs {
	protected $_dbcon;
	
	public function __construct($dbCon) {
		$this->_dbcon = $dbCon;
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

		if (!$this->columnExists('spots', 'subcatz')) {
			$this->addColumn("subcatz", "spots", "VARCHAR(64)");
		} # if

	} # updateSchema
	
} # class