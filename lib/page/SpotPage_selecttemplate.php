<?php
require_once "lib/page/SpotPage_Abs.php";

class SpotPage_selecttemplate extends SpotPage_Abs {
	private $_req;

	function __construct($db, $settings, $currentUser, $req) {
		parent::__construct($db, $settings, $currentUser);
		$this->_req = $req;
	} # ctor
	
	function render() {
		$chosenTemplate = $this->_req->getDef('template', '');
		
		if (array_search($chosenTemplate, $this->_settings['available_templates']) !== false) {
			setcookie('template', $chosenTemplate, time()+(86400*$this->_settings['cookie_expires']), '/', $this->_settings['cookie_host']);
			echo "<xml><return>ok</return></xml>";
		} else {
			echo "<xml><return>error</return></xml>";
		} # if
	} # render()

} # SpotPage_selecttemplate
