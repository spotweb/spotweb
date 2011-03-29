<?php
require_once "lib/dbstruct/SpotStruct_abs.php";

class SpotStruct_sqlite extends SpotStruct_abs {
	
	function createDatabase() {
		# Controleer of de 'spots' tabel wel recent is, de oude versie had 14 kolommen
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(spots)");
		if (count($q) == 14) {
			$this->_dbcon->rawExec("DROP TABLE spots;");
		} # if
		
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(spots)");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE spots(id INTEGER PRIMARY KEY ASC, 
											messageid VARCHAR(128),
											spotid INTEGER,
											category INTEGER, 
											subcat INTEGER,
											poster VARCHAR(128),
											groupname VARCHAR(128),
											subcata VARCHAR(64),
											subcatb VARCHAR(64),
											subcatc VARCHAR(64),
											subcatd VARCHAR(64),
											title VARCHAR(128),
											tag VARCHAR(128),
											stamp INTEGER,
											filesize BIGINT DEFAULT 0,
											moderated BOOLEAN DEFAULT FALSE);");
			$this->_dbcon->rawExec("CREATE TABLE nntp(server TEXT PRIMARY KEY,
										maxarticleid INTEGER UNIQUE,
										nowrunning INTEGER DEFAULT 0);");
			
			# create indices
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_1 ON spots(id, category, subcata, subcatd, stamp DESC)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_2 ON spots(id, category, subcatd, stamp DESC)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_3 ON spots(messageid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_4 ON spots(stamp);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_5 ON spots(poster);");
		} # if


		# Controleer of de 'commentsxover' tabel wel recent is, de oude versie had 3 kolommen, die droppen wij volledig
		try {
			$q = $this->_dbcon->arrayQuery("PRAGMA table_info(commentsxover)");
			if (count($q) == 4) {
				$this->_dbcon->rawExec("DROP TABLE commentsxover");
			} # if
		} catch(Exception $x) {
		 ;
		}
		
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(commentsxover)");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY ASC,
										   messageid VARCHAR(128),
										   nntpref VARCHAR(128));");
			$this->_dbcon->rawExec("CREATE INDEX idx_commentsxover_1 ON commentsxover(nntpref, messageid)");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsxover_2 ON commentsxover(messageid)");
		} # if
		
		# Controleer of de 'nntp' tabel wel recent is, de oude versie had 2 kolommen (server,maxarticleid)
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(nntp)");
		if (count($q) == 2) {
			# Niet alle SQLite versies ondersteunen alter table, dus we lezen de data in, droppen de tabel en 
			# inserten de data opnieuw
			$nntpData = $this->_dbcon->arrayQuery("SELECT server,maxarticleid FROM nntp");
			
			# Drop de nntp table en creeer hem opnieuw
			$this->_dbcon->rawExec("DROP TABLE nntp");
			$this->_dbcon->rawExec("CREATE TABLE nntp(server TEXT PRIMARY KEY,
													maxarticleid INTEGER UNIQUE,
													nowrunning INTEGER DEFAULT 0,
													lastrun INTEGER DEFAULT 0);");
													
			foreach($nntpData as $nntp) {
				$this->exec("INSERT INTO nntp(server,maxarticleid) VALUES('%s','%s')", 
						Array($nntp['server'],
							  $nntp['maxarticleid']));
			} # foreach
		} # if
		
		# Controleer of er wel een index zit op 'spots' tabel 
		$q = $this->_dbcon->arrayQuery("PRAGMA index_info(idx_spots_4)");
		if (empty($q)) {
			$q = $this->_dbcon->arrayQuery("CREATE INDEX idx_spots_4 ON spots(stamp);");
		}# if

		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(downloadlist)");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE downloadlist(id INTEGER PRIMARY KEY ASC,
										   messageid VARCHAR(128),
										   stamp INTEGER);");
			$this->_dbcon->rawExec("CREATE INDEX idx_downloadlist_1 ON downloadlist(messageid)");
		} # if

		# Controleer of de 'nntp' tabel wel recent is, de oude versie had 2 kolommen (server,maxarticleid)
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(nntp)");
		if (count($q) == 3) {
			# Niet alle SQLite versies ondersteunen alter table, dus we lezen de data in, droppen de tabel en 
			# inserten de data opnieuw
			$nntpData = $this->_dbcon->arrayQuery("SELECT server,maxarticleid FROM nntp");
			
			# Drop de nntp table en creeer hem opnieuw
			$this->_dbcon->rawExec("DROP TABLE nntp");
			$this->_dbcon->rawExec("CREATE TABLE nntp(server TEXT PRIMARY KEY,
													maxarticleid INTEGER UNIQUE,
													nowrunning INTEGER DEFAULT 0,
													lastrun INTEGER DEFAULT 0);");
													
			foreach($nntpData as $nntp) {
				$this->exec("INSERT INTO nntp(server,maxarticleid) VALUES('%s','%s')", 
						Array($nntp['server'],
							  $nntp['maxarticleid']));
			} # foreach
		} # if

		# Controleer of de 'spotsfull' tabel wel recent is, de oude versie had geen index op de userid
		$q = $this->_dbcon->arrayQuery("PRAGMA index_info(idx_spotsfull_2)");
		if (count($q) == 2) {
			$this->_dbcon->rawExec("CREATE INDEX idx_spotsfull_2 ON spotsfull(userid);");
		} # if
		
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(spotsfull)");
		if (empty($q)) {
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
		
		$q = $this->_dbcon->arrayQuery("PRAGMA table_info(watchlist)");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE watchlist(id INTEGER PRIMARY KEY, 
												   messageid VARCHAR(128),
												   dateadded INTEGER,
												   comment TEXT);");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_watchlist_1 ON watchlist(messageid)");
		} # if
		
	} # Createdatabase
	
} # class