<?php
require_once "lib/page/SpotPage_Abs.php";

class SpotPage_selecttemplate extends SpotPage_Abs {
	
	function render() {
		if(!empty($_GET['template']) && isset($this->_settings['available_templates'][$_GET['template']])) {
			setcookie('template', $_GET['template'], time()+(86400*$this->_settings['cookie_expires']), '/', $this->_settings['cookie_host']);
			echo "<xml><return>ok</return></xml>";
		} else {
			echo "<xml><return>nok</return></xml>";
		} # if
	} # render()

} # SpotPage_markallasread
