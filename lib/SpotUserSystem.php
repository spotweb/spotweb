<?php
define('SPOTWEB_ANONYMOUS_USERID', 1);
define('SPOTWEB_ADMIN_USERID', 2);

class SpotUserSystem {
	private $_db;
	private $_settings;
	
	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
	} # ctor

	/*
	 * Genereer een sessionid
	 */
	function generateUniqueId() {
		$sessionId = '';
		
		for($i = 0; $i < 10; $i++) {
			$sessionId .= base_convert(mt_rand(), 10, 36);
		} # for
		
		return $sessionId;
	} # generateUniqueId
	
	/*
	 * Creeer een nieuwe session
	 */
	private function createNewSession($userid) {
		# Als de user ingelogged is, creeer een sessie
		$tmpUser = $this->getUser($userid);
		
		# Als dit een anonieme user is, of als de user nog nooit
		# ingelogt heeft dan is het laatste bezoek altijd het 
		# moment van sessie creatie
		if (($userid == SPOTWEB_ANONYMOUS_USERID) || ($tmpUser['lastlogin'] == 0)) {
			$tmpUser['lastvisit'] = time();
		} else {
			$tmpUser['lastvisit'] = $tmpUser['lastlogin'];
		} # if

		# Creer een session record
		$session = array('sessionid' => $this->generateUniqueId(),
						 'userid' => $userid,
						 'hitcount' => 1,
						 'lasthit' => time());
		$this->_db->addSession($session);
		
		return array('user' => $tmpUser,
					 'session' => $session);
	} # createNewSession

	/* 
	 * Stuur een cookie met de sessionid mee
	 */
	function updateCookie($userSession) {
		SetCookie("spotsession",
				  $userSession['session']['sessionid'] . '.' . $userSession['user']['userid'],
				  time()+60*60*24*30,
				  '', # path: The default value is the current directory that the cookie is being set in.
				  $this->_settings->get('cookie_host'),
				  false,	# Indicates if the cookie should only be transmitted over a secure HTTPS connection from the client.
				  true);	# Only available to the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript.
	} # updateCookie
	
	/*
	 * Verwijdert een sessie
	 */
	function removeSession($sessionId) {
		$this->_db->deleteSession($sessionId);
	} # removeSession
	
	/*
	 * Verwijdert alle users' sessies
	 */
	function removeAllUserSessions($userId) {
		$this->_db->deleteAllUserSessions($userId);
	} # removeAllUserSessions
	
	/*
	 * Kijk of de user een sessie heeft, als hij die heeft gebruik die dan,
	 * anders creeeren we een sessie voor de anonieme user
	 */
	function useOrStartSession() {
		$userSession = false;
		
		if (isset($_COOKIE['spotsession'])) {
			$userSession = $this->validSession($_COOKIE['spotsession']);
		} # if

		if ($userSession === false) {
			# als er nu nog geen sessie bestaat, creeer dan een nieuwe
			# anonieme sessie
			# userid 1 is altijd onze anonymous user. 
			# In de settings.php kan de beheerder van Spotweb dit overriden maar
			# als resultaat van de vele klachten.
			$userSession = $this->createNewSession( $this->_settings->get('nonauthenticated_userid') );
		} # if
		
		# initialiseer het security systeem
		$spotSec = new SpotSecurity($this->_db, $this->_settings, $userSession['user']);
		$userSession['security'] = $spotSec;
		
		# update de sessie cookie zodat die niet spontaan gaat
		# expiren
		$this->updateCookie($userSession);
		
		return $userSession;
	} # useOrStartSession

	/*
	 * Password to hash
	 */
	function passToHash($password) {
		return sha1(strrev(substr($this->_settings->get('pass_salt'), 1, 3)) . $password . $this->_settings->get('pass_salt'));
	} # passToHash

	/*
	 * Probeert de user aan te loggen met de gegeven credentials,
	 * geeft user record terug of false als de user niet geauth kan
	 * worden
	 */
	function login($user, $password) {
		# Salt het password met het unieke salt in settings.php
		$password = $this->passToHash($password);

		# authenticeer de user?
		$userId = $this->_db->authUser($user, $password);
		if ($userId !== false) {
			# Als de user ingelogged is, creeer een sessie.
			# Volgorde is hier belangrijk omdat in de newsession
			# de lastvisit tijd op lastlogon gezet wordt moeten
			# we eerst de sessie creeeren.
			$userSession = $this->createNewSession($userId);
			$this->updateCookie($userSession);

			# nu gebruiken we het user record om de lastlogin te fixen
			$userSession['user']['lastlogin'] = time();
			$this->_db->setUser($userSession['user']);

			# initialiseer het security systeem
			$userSession['security'] = new SpotSecurity($this->_db, $this->_settings, $userSession['user']);

			return $userSession;
		} else {
			return false;
		} # else
	} # login

	function verifyApi($apikey) {
		# authenticeer de user?
		$userId = $this->_db->authUser(false, $apikey);
		if ($userId !== false && $userId > SPOTWEB_ADMIN_USERID && $apikey != '') {
			# Waar bij een normale login het aanmaken van
			# een sessie belangrijk is, doen we het hier
			# expliciet niet. Daarom halen we de gegevens
			# van de user direct op.
			$userRecord['user'] = $this->getUser($userId);

			# nu gebruiken we het user record om lastapiusage te fixen
			$userRecord['user']['lastapiusage'] = time();
			$this->_db->setUser($userRecord['user']);

			# initialiseer het security systeem
			$userRecord['security'] = new SpotSecurity($this->_db, $this->_settings, $userRecord['user']);

			return $userRecord;
		} else {
			return false;
		} # else
	} # verifyApi

	/*
	 * Reset the lastvisit timestamp
	 */
	function resetLastVisit($user) {
		$user['lastvisit'] = time();
		$this->_db->setUser($user);

		return $user;
	} # resetLastVisit

	/*
	 * Reset the seenstamp timestamp
	 */
	function resetReadStamp($user) {
		$user['lastread'] = $this->_db->getMaxMessageTime();
		$this->_db->setUser($user);

		return $user;
	} # resetReadStamp

	/*
	 * Controleert een session cookie, en als de sessie geldig
	 * is, geeft een user record terug
	 */
	function validSession($sessionCookie) {
		$sessionParts = explode(".", $sessionCookie);
		if (count($sessionParts) != 2) {
			return false;
		} # if

		# controleer of de sessie geldig is
		$sessionValid = $this->_db->getSession($sessionParts[0], $sessionParts[1]);
		if ($sessionValid === false) {
			return false;
		} # if
		
		# het is een geldige sessie, haal userrecord op
		$this->_db->hitSession($sessionParts[0]);
		$userRecord = $this->getUser($sessionValid['userid']);
		
		# als het user record niet gevonden kan worden, is het toch geen geldige
		# sessie
		if ($userRecord === false) {
			return false;
		} # if
		
		#
		# Controleer nu of we de lastvisit timestamp moeten updaten?
		# 
		# Als de lasthit (wordt geupdate bij elke hit) langer dan 15 minuten
		# geleden is, dan updaten we de lastvisit timestamp naar de lasthit time,
		# en resetten we daarmee effectief de lastvisit time.
		#
		if ($sessionValid['lasthit'] < (time() - 900)) {
			$userRecord['lastvisit'] = $sessionValid['lasthit'];
			$this->_db->setUser($userRecord);
		} # if
		
		return array('user' => $userRecord,
					 'session' => $sessionValid);
	} # validSession
	
	/*
	 * Geeft een boolean terug die aangeeft of een username geldig is of niet 
	 */
	function validUsername($user) {
		$invalidNames = array('god', 'mod', 'modje', 'spot', 'spotje', 'spotmod', 
							  'admin', 'drazix', 'moderator', 'superuser', 'supervisor', 
							  'spotnet', 'spotnetmod', 'administrator',  'spotweb',
							  'root', 'anonymous');

		$validUsername = !in_array(strtolower($user), $invalidNames);
		if ($validUsername) {
			$validUsername = strlen($user) >= 3;
		} # if
		
		return $validUsername;
	} # validUsername

	/*
	 * Voegt een gebruiker toe aan de database 
	 */
	function addUser($user) {
		if (!$this->validUsername($user['username'])) {
			throw new Exception("Invalid username");
		} # if

		# converteer het password naar een pass hash
		$user['passhash'] = $this->passToHash($user['newpassword1']);

		# Creëer een API key
		$user['apikey'] = md5($this->generateUniqueId());

		# en voeg het record daadwerkelijk toe
		$tmpUser = $this->_db->addUser($user);
		$this->_db->setUserRsaKeys($tmpUser['userid'], $user['publickey'], $user['privatekey']);
		
		# Geef de user default preferences en settingss
		$anonUser = $this->_db->getUser(SPOTWEB_ANONYMOUS_USERID);
		$tmpUser = array_merge($anonUser, $tmpUser);
		$this->_db->setUser($tmpUser);
		
		# en geef de gebruiker de nodige groepen
		$this->_db->setUserGroupList($tmpUser['userid'], $this->_settings->get('newuser_grouplist'));
		
		# en de nodige filters
		$this->_db->copyFilterList(SPOTWEB_ANONYMOUS_USERID, $tmpUser['userid']);
	} # addUser()

	/*
	 * Update een gebruikers' group membership
	 */
	function setUserGroupList($user, $groupList) {
		$this->_db->setUserGroupList($user['userid'], $groupList);
	} # setUserGroupList
	 
	/*
	 * Update een gebruikers' password
	 */
	function setUserPassword($user) {
		# converteer het password naar een pass hash
		$user['passhash'] = $this->passToHash($user['newpassword1']);
		
		$this->_db->setUserPassword($user);
	} # setUserPassword

	/*
	 * Update een gebruikers' API key
	 */
	function resetUserApi($user) {
		$user['apikey'] = md5($this->generateUniqueId());
		
		$this->_db->setUser($user);
		return $user;
	} # setUserApi

	/* 
	 * Cleanup van user preferences
	 */
	function cleanseUserPreferences($prefs, $tpl) {
		# we willen nu zeker weten dat er in _editUserPrefsForm geen preferences gegeven zijn
		# welke we helemaal niet ondersteunen.
		foreach(array_diff_key($prefs, $tpl) as $keys => $values) {
			unset($prefs[$keys]);
		} # foreach
		
		return $prefs;
	} # cleanseUserPreferences
	
	/*
	 * Valideer de user preferences
	 */
	function validateUserPreferences($prefs, $currentPrefs) {
		$errorList = array();
		
		# Definieer een aantal arrays met valid settings
		$validDateFormats = array('human', '%a, %d-%b-%Y (%H:%M)', '%d-%m-%Y (%H:%M)');
		$validTemplates = array('we1rdo');
		
		# Controleer de per page setting
		$prefs['perpage'] = (int) $prefs['perpage'];
		if (($prefs['perpage'] < 2) || ($prefs['perpage'] > 250)) {
			$errorList[] = array('validateuser_invalidpreference', array('perpage'));
		} # if
		
		# Controleer basis settings
		if (in_array($prefs['date_formatting'], $validDateFormats) === false) {
			$errorList[] = array('validateuser_invalidpreference', array('date_formatting')); 
		} # if
		
		if (in_array($prefs['template'], $validTemplates) === false) { 	
			$errorList[] = array('validateuser_invalidpreference', array('template'));
		} # if
		
		# Als nzbhandling instellingen totaal niet opgegeven zijn, defaulten we naar disable
		if (!isset($prefs['nzbhandling'])) {
			$prefs['nzbhandling'] = array('action' => 'disable',
										  'prepare_action' => 'merge');										  
		} # if
		
		# als er een sabnzbd host opgegeven is, moet die geldig zijn
		if ( ($prefs['nzbhandling']['action'] == 'client-sabnzbd') || ($prefs['nzbhandling']['action'] == 'push-sabnzbd') ) {
			$tmpHost = parse_url($prefs['nzbhandling']['sabnzbd']['url']);
			
			if ( ($tmpHost === false) | (!isset($tmpHost['scheme'])) || (($tmpHost['scheme'] != 'http') && ($tmpHost['scheme'] != 'https')) ) {
				$errorList[] = array('validateuser_invalidpreference', array('sabnzbd url'));
			} # if
			
			# SABnzbd URL moet altijd eindigen met een slash
			if(substr($prefs['nzbhandling']['sabnzbd']['url'], -1) !== '/') {
				$prefs['nzbhandling']['sabnzbd']['url'] .= '/';
			} # if
		} # if

		# converteer overige settings naar boolean zodat we gewoon al weten wat er uitkomt
		$prefs['count_newspots'] = (isset($prefs['count_newspots'])) ? true : false;
		$prefs['keep_seenlist'] = (isset($prefs['keep_seenlist'])) ? true : false;
		$prefs['auto_markasread'] = (isset($prefs['auto_markasread'])) ? true : false;
		$prefs['keep_downloadlist'] = (isset($prefs['keep_downloadlist'])) ? true : false;
		$prefs['keep_watchlist'] = (isset($prefs['keep_watchlist'])) ? true : false;
		$prefs['show_filesize'] = (isset($prefs['show_filesize'])) ? true : false;
		$prefs['show_multinzb'] = (isset($prefs['show_multinzb'])) ? true : false;
		
		$notifProviders = Notifications_Factory::getActiveServices();
		foreach ($notifProviders as $notifProvider) {
			$prefs['notifications'][$notifProvider]['enabled'] = (isset($prefs['notifications'][$notifProvider]['enabled'])) ? true : false;
			$prefs['notifications'][$notifProvider]['events']['watchlist_handled'] = (isset($prefs['notifications'][$notifProvider]['events']['watchlist_handled'])) ? true : false;
			$prefs['notifications'][$notifProvider]['events']['nzb_handled'] = (isset($prefs['notifications'][$notifProvider]['events']['nzb_handled'])) ? true : false;
			$prefs['notifications'][$notifProvider]['events']['retriever_finished'] = (isset($prefs['notifications'][$notifProvider]['events']['retriever_finished'])) ? true : false;
			$prefs['notifications'][$notifProvider]['events']['user_added'] = (isset($prefs['notifications'][$notifProvider]['events']['user_added'])) ? true : false;
		}

		# Twitter tokens komen niet binnen via het form, maar mogen perse niet weggegooid worden.
		$prefs['notifications']['twitter']['screen_name'] = $currentPrefs['notifications']['twitter']['screen_name'];
		$prefs['notifications']['twitter']['access_token'] = $currentPrefs['notifications']['twitter']['access_token'];
		$prefs['notifications']['twitter']['access_token_secret'] = $currentPrefs['notifications']['twitter']['access_token_secret'];
		$prefs['notifications']['twitter']['request_token'] = $currentPrefs['notifications']['twitter']['request_token'];
		$prefs['notifications']['twitter']['request_token_secret'] = $currentPrefs['notifications']['twitter']['request_token_secret'];

		# We willen geen megabytes aan custom CSS opslaan, dus controleer dat dit niet te groot is
		if (strlen($prefs['customcss'] > 1024 * 10)) { 
			$errorList[] = array('validateuser_invalidpreference', array('customcss'));
		} # if		
		
		# als men runcommand of save wil, moet er een local_dir opgegeven worden
		if (($prefs['nzbhandling']['action'] == 'save') || ($prefs['nzbhandling']['action'] == 'runcommand')) {
			if (empty($prefs['nzbhandling']['local_dir'])) {
				$errorList[] = array('validateuser_invalidpreference', array('local_dir'));
			} # if
		} # if

		# als men Growl wil gebruiken, moet er een host opgegeven worden
		if ($prefs['notifications']['growl']['enabled']) {
			if (empty($prefs['notifications']['growl']['host'])) {
				$errorList[] = array('validateuser_invalidpreference', array('growl host'));
			} # if
		} # if

		# als men Notifo wil gebruiken, moet er een username & apikey opgegeven worden
		if ($prefs['notifications']['notifo']['enabled']) {
			if (empty($prefs['notifications']['notifo']['username'])) {
				$errorList[] = array('validateuser_invalidpreference', array('notifo username'));
			} # if
			if (empty($prefs['notifications']['notifo']['api'])) {
				$errorList[] = array('validateuser_invalidpreference', array('notifo api'));
			} # if
		} # if

		# als men Prowl wil gebruiken, moet er een apikey opgegeven worden
		if ($prefs['notifications']['prowl']['enabled']) {
			if (empty($prefs['notifications']['prowl']['apikey'])) {
				$errorList[] = array('validateuser_invalidpreference', array('prowl apikey'));
			} # if
		} # if

		# als men Twitter wil gebruiken, moet er er een account zijn geverifieerd
		if ($prefs['notifications']['twitter']['enabled']) {
			if (empty($prefs['notifications']['twitter']['access_token']) || empty($prefs['notifications']['twitter']['access_token_secret'])) {
				$errorList[] = array('validateuser_invalidpreference', array('Er is geen account geverifi&euml;erd voor Twitter notificaties.'));
			} # if
		} # if

		return array($errorList, $prefs);
	} # validateUserPreferences

	/*
	 * Valideer het user record, kan gebruikt worden voor het toegevoegd word of
	 * geupdate wordt
	 */
	function validateUserRecord($user, $isEdit) {
		$errorList = array();
		
		# Controleer de username
		if (!$isEdit) {
			if (!$this->validUsername($user['username'])) {
				$errorList[] = array('validateuser_invalidusername', array());
			} # if
		} # if
		
		# controleer de firstname
		if (strlen($user['firstname']) < 3) {
			$errorList[] = array('validateuser_invalidfirstname', array());
		} # if
		
		# controleer de lastname
		if (strlen($user['lastname']) < 3) {
			$errorList[] = array('validateuser_invalidlastname', array());
		} # if

		# controleer het password, als er een opgegeven is
		if (strlen($user['newpassword1'] > 0)) {
			if (strlen($user['newpassword1']) < 5){
				$errorList[] = array('validateuser_passwordtooshort', array());
			} # if 
		} # if

		# password1 en password2 moeten hetzelfde zijn (password en bevestig password)
		if ($user['newpassword1'] != $user['newpassword2']) {
			$errorList[] = array('validateuser_passworddontmatch', array());
		} # if

		# anonymous user editten mag niet
		if ($user['userid'] == SPOTWEB_ANONYMOUS_USERID) {
			$errorList[] = array('edituser_cannoteditanonymous', array());
		} # if
		
		# controleer het mailaddress
		if (!filter_var($user['mail'], FILTER_VALIDATE_EMAIL)) {
			$errorList[] = array('validateuser_invalidmail', array());
		} # if

		# Is er geen andere uset met dezelfde mailaddress?
		$emailExistResult = $this->_db->userEmailExists($user['mail']);
		if (($emailExistResult !== $user['userid']) && ($emailExistResult !== false)) {
			$errorList[] = array('validateuser_mailalreadyexist', array());
		} # if
		
		return $errorList;
	} # validateUserRecord
	
	/*
	 * Stel de users' public en private keys in
	 */
	function setUserRsaKeys($user, $privateKey, $publicKey) {
		$this->_db->setUserRsaKeys($user['userid'], $privateKey, $publicKey);
	} # setUserRsaKeys
	
	/*
	 * Valideert een group record
	 */
	function validateSecGroup($group) {
		$errorList = array();

		# Verwijder overbodige spaties e.d.
		$group['name'] = trim($group['name']);
		
		# Controleer of er een usergroup opgegeven is en of de 
		# naam niet te kort is
		if (strlen($group['name']) < 3) {
			$errorList[] = array('validatesecgroup_invalidname', array('name'));
		} # if
		
		# Vraag nu alle security groepen om, om er zeker van te zijn
		# dat deze security groep nog niet voorkomt. Niet het meest efficient
		# maar het aantal verwachtte securitygroepen zal meevallen
		$secGroupList = $this->_db->getGroupList(null);
		foreach($secGroupList as $secGroup) {
			if ($secGroup['name'] == $group['name']) {
				if ($secGroup['id'] != $group['id']) {
					$errorList[] = array('validatesecgroup_duplicatename', array('name'));
				} # if
			} # if
		} # foreach
		
		return array($errorList, $group);
	} # validateSecGroup

	/*
	 * Verwijdert een permissie uit een security group
	 */
	function removePermFromSecGroup($groupId, $perm) {
		$this->_db->removePermFromSecGroup($groupId, $perm);
	} # removePermFromSecGroup
	
	/*
	 * Voegt een permissie aan een security group toe
	 */
	function addPermToSecGroup($groupId, $perm) {
		$errorList = array();
		
		// trim het objectid
		$perm['objectid'] = trim($perm['objectid']);
		
		// controleer dat deze specifieke permissie niet al in de security groep zit
		$groupPerms = $this->_db->getGroupPerms($groupId);
		foreach($groupPerms as $groupPerm) {
			if (($groupPerm['permissionid'] == $perm['permissionid']) && 
				($groupPerm['objectid'] == $perm['objectid'])) {
				
				# Dubbele permissie
				$errorList[] = array('validatesecgroup_duplicatepermission', array('name'));
			} # if
		} # foreach
	
		// voeg de permissie aan de groep
		if (empty($errorList)) {
			$this->_db->addPermToSecGroup($groupId, $perm);
		} # if
		
		return $errorList;
	} # addPermToSecGroup
	
	/*
	 * Update een group record
	 */
	function setSecGroup($group) {
		$this->_db->setSecurityGroup($group);
	} # setSecGroup

	/*
	 * Voegt een group record toe
	 */
	function addSecGroup($group) {
		$this->_db->addSecurityGroup($group);
	} # addSecGroup
	
	/*
	 * Geeft een group record terug
	 */
	function getSecGroup($groupId) {
		$tmpGroup = $this->_db->getSecurityGroup($groupId);
		if (!empty($tmpGroup)) {
			return $tmpGroup[0];
		} else {
			return false;
		} # else
	} # getSecGroup

	/*
	 * Verwijdert een group record
	 */
	function removeSecGroup($group) {
		$this->_db->removeSecurityGroup($group);
	} # removeSecGroup

	/*
	 * Geeft een user record terug
	 */
	function getUser($userid) {
		$tmpUser = $this->_db->getUser($userid);
		
		return $tmpUser;
	} # getUser()

	/*
	 * Vraagt een ongeformatteerde filterlist op
	 */
	function getPlainFilterList($userId, $filterType) {
		return $this->_db->getPlainFilterList($userId, $filterType);
	} # get PlainFilterList
	
	/*
	 * Vraagt een filter list op
	 */
	function getFilterList($userId, $filterType) {
		return $this->_db->getFilterList($userId, $filterType);
	} # getFilterList
	
	/*
	 * Vraag een specifieke filter op
	 */
	function getFilter($userId, $filterId) {
		return $this->_db->getFilter($userId, $filterId);
	} # getFilter

	/*
	 * Wijzigt de filter waardes.
	 *
	 * Op dit moment ondersteunen we enkel om de volgende waardes
	 * te wijzigen
	 *
	 *   * Title
	 *   * Order
	 *   * Parent
	 */
	function changeFilter($userId, $filterForm) {
		return $this->_db->updateFilter($userId, $filterForm);
	} # getFilter


	/*
	 * Checkt of een filter geldig is
	 */
	function validateFilter($filter) {
		$errorList = array();

		# Verwijder overbodige spaties e.d.
		$filter['title'] = trim(utf8_decode($filter['title']), " \t\n\r\0\x0B'\"");
		$filter['title'] = trim(utf8_decode($filter['title']), " \t\n\r\0\x0B'\"");
		
		// controleer dat deze specifieke permissie niet al in de security groep zit
		if (strlen($filter['title']) < 3) {
			$errorList[] = array('validatefilter_invalidtitle', array('name'));
		} # if
		
		return array($filter, $errorList);
	} # validateFilter
	
	/*
	 * Voegt een userfilter toe
	 */
	function addFilter($userId, $filter) {
		$errorList = array();
		list($filter, $errorList) = $this->validateFilter($filter);
		
		/* Geen fouten gevonden? voeg de filter dan toe */
		if (empty($errorList)) {
			$this->_db->addFilter($userId, $filter);
		} # if
		
		return $errorList;
	} # addFilter
	
	/*
	 * Get the users' index filter
	 */
	function getIndexFilter($userId) {
		$tmpFilter = $this->_db->getUserIndexFilter($userId);
		if ($tmpFilter === false) {
			return array('tree' => '');
		} else {
			return $tmpFilter;
		} # else
	} # getIndexFilter
	
	/*
	 * Add user's index filter
	 */
	function setIndexFilter($userId, $filter) {
		/* There can only be one */
		$this->removeIndexFilter($userId);
		
		/* en voeg de index filter toe */
		$filter['filtertype'] = 'index_filter';
		$this->_db->addFilter($userId, $filter);
	} # addIndexFilter
	
	/*
	 * Remove an index filter
	 */
	function removeIndexFilter($userId) {
		$tmpFilter = $this->_db->getUserIndexFilter($userId);
		
		if (!empty($tmpFilter)) {
			$this->_db->deleteFilter($userId, $tmpFilter['id'], 'index_filter');
		} # if
	} # removeIndexFilter

	/*
	 * Voegt een userfilter toe
	 */
	function removeFilter($userId, $filterId) {
		$this->_db->deleteFilter($userId, $filterId, 'filter');
	} # removeFilter
	
	/*
	 * Wist alle bestaande filters, en reset ze naar de opgegeven id
	 */
	function resetFilterList($userId) {
		# Wis de filters
		$this->_db->removeAllFilters($userId);
		
		# copieer de nodige filters
		$this->_db->copyFilterList(SPOTWEB_ANONYMOUS_USERID, $userId);
	} # resetFilterList

	/*
	 * Wist alle bestaande filters, en reset ze naar de opgegeven filterlist
	 */
	function setFilterList($userId, $filterList) {
		# Wis de filters
		$this->_db->removeAllFilters($userId);
		
		# copieer de nodige filters
		foreach($filterList as $filter) {
			$this->_db->addFilter($userId, $filter);
		} # foreach
	} # setFilterList
	
	/*
	 * Wist alle bestaande filters, en reset ze naar de opgegeven id
	 */
	function setFiltersAsDefault($userId) {
		# Wis de filters
		$this->_db->removeAllFilters(SPOTWEB_ANONYMOUS_USERID);
		
		# copieer de nodige filters
		$this->_db->copyFilterList($userId, SPOTWEB_ANONYMOUS_USERID);
	} # setFiltersAsDefault

	/*
	 * Update een user record
	 */
	function setUser($user) {
		# We gaan er altijd van uit dat een password nooit gezet wordt
		# via deze functie dus dat stuk negeren we
		$this->_db->setUser($user);
	} # setUser()
	
	/*
	 * Verwijdert een user record
	 */
	function removeUser($userid) {
		$this->_db->deleteUser($userid);
	} # removeUser()

	/*
	 * Converteert een lijst met filters naar een XML record
	 * welke uitwisselbaar is
	 */
	public function filtersToXml($filterList) {
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

		# Opbouwen XML
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$mainElm = $doc->createElement('spotwebfilter');
		$mainElm->appendChild($doc->createElement('version', '1.0'));
		$mainElm->appendChild($doc->createElement('generator', 'SpotWeb v' . SPOTWEB_VERSION));
		$doc->appendChild($mainElm);

		$filterListElm = $doc->createElement('filters');

		foreach($filterList as $filter) {
			$filterElm = $doc->createElement('filter');
			
			$filterElm->appendChild($doc->createElement('id', $filter['id']));
			$filterElm->appendChild($doc->createElement('title', $filter['title']));
			$filterElm->appendChild($doc->createElement('icon', $filter['icon']));
			$filterElm->appendChild($doc->createElement('parent', $filter['tparent']));
			$filterElm->appendChild($doc->createElement('order', $filter['torder']));

			/* 
			 * Voeg nu de boom toe - we krijgen dat als tree aangeleverd maar
			 * we willen die boom graag een beetje klein houden. We comprimeren
			 * dus de boom
			 *
			 * Maar eerst moeten we de tree parseren naar een aparte lijst
			 * categorieen en strongnots
			 */
			$dynaList = explode(',', $filter['tree']);
			list($categoryList, $strongNotList) = $spotsOverview->prepareCategorySelection($dynaList);
			$treeList = explode(',', $spotsOverview->compressCategorySelection($categoryList, $strongNotList));
			$tree = $doc->createElement('tree');
			foreach($treeList as $treeItem) { 
				if (!empty($treeItem)) {
					# Bepaal wat voor type tree element dit is
					$treeType = 'include';
					if ($treeItem[0] == '~') {
						$treeType = 'strongnot';
						$treeItem = substr($treeItem, 1);
					} elseif ($treeItem[1] == '!') {
						$treeType = 'exclude';
						$treeItem = substr($treeItem, 1);
					} # else
					
					# Creer nu een tree item
					$treeElm = $doc->createElement('item', $treeItem);
					$treeElm->setAttribute('type', $treeType);

					if (!empty($treeItem)) {
						$tree->appendChild($treeElm);
					} # if
				} # if
			} # treeItems
			$filterElm->appendChild($tree);

			/* 
			 * Prepareer de filtervalue list zodat hij bruikbaar is 
			 * in de XML hieronder
			 */
			$tmpFilterValues = explode('&', $filter['valuelist']);
			$filterValueList = array();
			foreach($tmpFilterValues as $filterValue) {
				$tmpFilter = explode(':', urldecode($filterValue));
				
				# maak de daadwerkelijke filter
				if (count($tmpFilter) >= 3) {
					$filterValueList[] = Array('fieldname' => $tmpFilter[0],
											 'operator' => $tmpFilter[1],
											 'value' => join(":", array_slice($tmpFilter, 2)));
				} # if
			} # foreach

			/* 
			 * Voeg nu de filter items (text searches e.d. toe)
			 */
			 if (!empty($filterValueList)) {
				 $valuesElm = $doc->createElement('values');
				 foreach($filterValueList as $filterValue) {
					# Creer nu een tree item
					$itemElm = $doc->createElement('item');
					$itemElm->appendChild($doc->createElement('fieldname', $filterValue['fieldname']));
					$itemElm->appendChild($doc->createElement('operator', $filterValue['operator']));
					$itemElm->appendChild($doc->createElement('value', $filterValue['value']));

					$valuesElm->appendChild($itemElm);
				 } # foreach
				$filterElm->appendChild($valuesElm);
			} # if
			 
			/* 
			 * Voeg nu de sort items
			 */
			if (!empty($filter['sorton'])) {
				$sortElm = $doc->createElement('sort');
				# Creer nu een tree item
				$itemElm = $doc->createElement('item');
				$itemElm->appendChild($doc->createElement('fieldname', $filter['sorton']));
				$itemElm->appendChild($doc->createElement('direction', $filter['sortorder']));

				$sortElm->appendChild($itemElm);
				$filterElm->appendChild($sortElm);
			} # if

			$filterListElm->appendChild($filterElm);
		} # foreach
		
		$mainElm->appendChild($filterListElm);

		return $doc->saveXML();
	} # filtersToXml 

	/*
	 * Converteert XML string naar een lijst met filters 
	 */
	public function xmlToFilters($xmlStr) {
		$filterList = array();
		$idMapping = array();
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

		/*
		 * Parse de XML file
		 */		
		$xml = @(new SimpleXMLElement($xmlStr));
		
		# Op dit moment kunnen we maar 1 versie van filters parsen
		if ( (string) $xml->version != '1.0') {
			return $filterList;
		} # if

		# en loop door alle filters heen
		foreach($xml->xpath('/spotwebfilter/filters/filter') as $filterItem) {
			$filter['id'] = (string) $filterItem->id;
			$filter['title'] = (string) $filterItem->title;
			$filter['icon'] = (string) $filterItem->icon;
			$filter['tparent'] = (string) $filterItem->parent;
			$filter['torder'] = (string) $filterItem->order;
			$filter['filtertype'] = 'filter';
			$filter['sorton'] = '';
			$filter['sortorder'] = '';
			$filter['tree'] = '';
			$filter['children'] = array();

			/*
			 * Parseer de items waarin de tree filters staan
			 */
			$treeStr = "";
			foreach($filterItem->xpath('tree/item') as $treeItem) {
				$treeType = (string) $treeItem->attributes()->type;
				if ($treeType == 'exclude') {
					$treeStr .= ',!' . $treeItem[0];
				} elseif ($treeType == 'strongnot') {
					$treeStr .= ',~' . $treeItem[0];
				} elseif ($treeType == 'include') {
					$treeStr .= ',' . $treeItem[0];
				} # if
			} # foreach
			
			if (strlen($treeStr) > 1) {
				$treeStr = substr($treeStr, 1);
			} # if
			
			$filter['tree'] = $treeStr;

			/*
			 * Parseer de items waarin de tree filters staan
			 */
			$filterValues = array();
			foreach($filterItem->xpath('values/item') as $valueItem) {
				$value = array();

				$filterValues[] = urlencode(
								   (string) $valueItem->fieldname . 
									':' . 
								   (string) $valueItem->operator . 
									':' . 
								   (string) $valueItem->value
								  );
			} # foreach
			$filter['valuelist'] = $filterValues;

			/* 
			 * Sorteer elementen zijn optioneel, kijk of ze bestaan
			 */
			if ($filterItem->sort) {
				$filter['sorton'] = (string) $filterItem->sort->item->fieldname;
				$filter['sortorder'] = (string) $filterItem->sort->item->direction;
			} # if
			
			$filterList[$filter['id']] = $filter;
		} # foreach
		
		/*
		 * Nu gaan we er en boom van maken, we kunnen dit niet op dezelfde
		 * manier doen als in SpotDb omdat de xpath() functie geen reference
		 * toestaat 
		 */
		 foreach($filterList as $idx => &$filter) {
			if ($filter['tparent'] != 0) {
				$filterList[$filter['tparent']]['children'][] =& $filter;
				unset($filterList[$filter['id']]);
			} # if
		} # for
		
		return $filterList;
	} # xmlToFilters
	
} # class SpotUserSystem
