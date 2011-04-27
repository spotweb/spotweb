<?php
class SpotPage_render extends SpotPage_Abs {
	private $_tplname;
	private $_params;
	
	function __construct(SpotDb $db, SpotSettings $settings, $currentSession, $tplName, $params) {
		parent::__construct($db, $settings, $currentSession);
		$this->_tplname = $tplName;
		$this->_params = $params;
	} # ctor

	function sanitizeTplName($tpl) {
		$validChars = 'abcdefghijklmnopqrstuvwxyz';
		
		$newName = '';
		for($i = 0; $i < strlen($tpl); $i++) {
			if (strpos($validChars, $tpl[$i]) !== false) {
				$newName .= $tpl[$i];
			} # if
		} # for
		
		return $newName;
	} # sanitizeTplName

	function render() {
		# Haal de volledige spotinhoud op
		$spotsOverview = new SpotsOverview($this->_db, $this->_settings);

		# sanitize the template name
		$tplFile = $this->sanitizeTplName($this->_tplname);
		
		#- display stuff -#
		if (strlen($tplFile) > 0) {
			$this->template($tplFile, $this->_params);
		} # if
	} # render
	
} # class SpotPage_render
