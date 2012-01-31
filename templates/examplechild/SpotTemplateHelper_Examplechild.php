<?php
class SpotTemplateHelper_Examplechild extends SpotTemplateHelper_We1rdo {

	function getFilterIcons() {
		$filterIcons = parent::getFilterIcons();

		$filterIcons['extraicon'] = _('Extra icon from Example Child Theme');

		return $filterIcons;
	} # getFilterIcons


	/*
	 * Returns an array of parent template paths
	 */
	function getParentTemplates() {
		$tmpList = parent::getParentTemplates();
		$tmpList[] = 'we1rdo';
		
		return $tmpList;
	} // getParentTemplates


	function getStaticFiles($type) {
		$tmpList = parent::getStaticFiles($type);

		switch($type) {
			case 'css'	: {
				$tmpList[] = 'templates/examplechild/css/extraspoticons.css';
				
				break;
			} # case css
		} # switch

		return $tmpList;
	} # getStaticFiles
							 


} # SpotTemplateHelper_ExampleChild
