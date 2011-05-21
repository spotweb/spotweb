<?php
class SpotPreferencesUpdater {
	private $_db;

	function __construct(SpotDb $db) {
		$this->_db = $db;
	} # ctor

	function update() {
		$userList = $this->_db->listUsers("", 0, 999999999999999);
		
		# loop through every user and fix it 
		foreach($userList['list'] as $user) {
			# Omdat we vanuti listUsers() niet alle velden meekrijgen
			# vragen we opnieuw het user record op
			$user = $this->_db->getUser($user['userid']);
			
			# set the users' preferences
			$this->setIfNot($user['prefs'], 'perpage', '50');
			$this->setIfNot($user['prefs'], 'date_formatting', 'human');
			$this->setIfNot($user['prefs'], 'template', 'we1rdo');
			$this->setIfNot($user['prefs'], 'perpage', '50');
			$this->setIfNot($user['prefs'], 'count_newspots', true);
			$this->setIfNot($user['prefs'], 'keep_seenlist', true);
			$this->setIfNot($user['prefs'], 'auto_markasread', true);
			$this->setIfNot($user['prefs'], 'keep_downloadlist', true);
			$this->setIfNot($user['prefs'], 'keep_watchlist', true);
			$this->setIfNot($user['prefs'], 'search_url', 'http://nzbindex.nl/search/?q=$SPOTFNAME');
			
			# update the user record in the database			
			$this->_db->setUser($user);
		} # foreach
	} # update()
	
	/*
	 * Set een setting alleen als hij nog niet bestaat
	 */
	function setIfNot(&$pref, $name, $value) {
		if (isset($pref[$name])) {
			return ;
		} # if
		
		$pref[$name] = $value;
	} # setIfNot
	
} # SpotPreferencesUpdater
