<?php
class SpotPage_twitteroauth extends SpotPage_Abs {
	private $_notificationService = array();
	private $_params;

	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $params) {
		parent::__construct($db, $settings, $currentSession);

		$this->_params = $params;
	}

	function render() {
		# Controleer de users' rechten
		$this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_send_notifications_services, 'twitter');

		# Instantieer het Spot user system & notificatiesysteem
		$spotUserSystem = new SpotUserSystem($this->_db, $this->_settings);
		$spotsNotifications = new SpotNotifications($this->_db, $this->_settings, $this->_currentSession);

		$requestArray = array_merge_recursive($this->_currentSession['user']['prefs']['notifications']['twitter'],
											  array('consumer_key' => $this->_settings->get('twitter_consumer_key'),
											        'consumer_secret' => $this->_settings->get('twitter_consumer_secret'))
											 );

		if ($this->_params['action'] == 'verify') {
			$this->_notificationService = Notifications_Factory::build('Spotweb', 'twitter', $requestArray);
			# een foute PIN invoeren geeft een notice, terwijl we zonder notice al een prima foutafhandeling hebben
			list ($http_code, $access_token) = @$this->_notificationService->verifyPIN($this->_params['pin']);

			if ($http_code == 200) {
				# request_token hebben we niet meer nodig
				$this->_currentSession['user']['prefs']['notifications']['twitter']['request_token'] = '';
				$this->_currentSession['user']['prefs']['notifications']['twitter']['request_token_secret'] = '';
				# access_token is wat we wel willen opslaan
				$this->_currentSession['user']['prefs']['notifications']['twitter']['screen_name'] = $access_token['screen_name'];
				$this->_currentSession['user']['prefs']['notifications']['twitter']['access_token'] = $access_token['oauth_token'];
				$this->_currentSession['user']['prefs']['notifications']['twitter']['access_token_secret'] = $access_token['oauth_token_secret'];
				$spotUserSystem->setUser($this->_currentSession['user']);
				echo "Account " . $access_token['screen_name'] . " geverifi&euml;erd.";
			} else {
				echo "Code " . $http_code . ": " . $this->getError($http_code);
			} # if
		} elseif ($this->_params['action'] == 'remove') {
			$screen_name = $this->_currentSession['user']['prefs']['notifications']['twitter']['screen_name'];
			$this->_currentSession['user']['prefs']['notifications']['twitter']['screen_name'] = '';
			$this->_currentSession['user']['prefs']['notifications']['twitter']['access_token'] = '';
			$this->_currentSession['user']['prefs']['notifications']['twitter']['access_token_secret'] = '';
			$spotUserSystem->setUser($this->_currentSession['user']);
			echo "Account " . $screen_name . " verwijderd.";
		} else {
			$this->_notificationService = Notifications_Factory::build('Spotweb', 'twitter', $requestArray);
			list ($http_code, $request_token, $registerURL) = @$this->_notificationService->requestAuthorizeURL();
			
			if ($http_code == 200) {
				# request_token slaan we op in de preferences, deze hebben we
				# weer nodig wanneer de PIN wordt ingevoerd
				$this->_currentSession['user']['prefs']['notifications']['twitter']['request_token'] = $request_token['oauth_token'];
				$this->_currentSession['user']['prefs']['notifications']['twitter']['request_token_secret'] = $request_token['oauth_token_secret'];
				$spotUserSystem->setUser($this->_currentSession['user']);
				echo $registerURL;
			} else {
				echo "Code " . $http_code . ": " . $this->getError($http_code);
			} # if

		} # if
	} # render

	function getError($errcode) {
		# http://dev.twitter.com/pages/responses_errors
		switch ($errcode) {
			case 200: $errtext = "OK"; break;
			case 304: $errtext = "Not Modified"; break;
			case 400: $errtext = "Bad Request"; break;
			case 401: $errtext = "Unauthorized"; break;
			case 403: $errtext = "Forbidden"; break;
			case 404: $errtext = "Not Found"; break;
			case 406: $errtext = "Not Acceptable"; break;
			case 420: $errtext = "Enhance Your Calm"; break;
			case 500: $errtext = "Internal Server Error"; break;
			case 502: $errtext = "Bad Gateway"; break;
			case 503: $errtext = "Service Unavailable"; break;
			default: $errtext = "Unknown error"; break;
		} # switch

		return ($errtext);
	} # getError

} # class SpotPage_twitteroauth