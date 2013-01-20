<?php
class Services_Spot_Record {
	private $_daoFactory;
	private $_currentSession;

	function __construct(Dao_Factory $daoFactory, $currentSession) {
		$this->_daoFactory = $daoFactory;
		$this->_currentSession = $currentSession;
	} # ctor

	/*
	 * Delete a spot from the database
	 */
	function deleteSpot($messageId)
	{
		# remove the spot from the database
		$daoSpot = $this->_daoFactory->getSpotDao();
		$spotMsgIdList = array($messageId => true);
		$daoSpot->removeSpots($spotMsgIdList);
	} # deleteSpot

	function updateSpot($messageId, $fullSpotXml) {
		# parse the fullspot xml
		$svcFmtParsing = new Services_Format_Parsing();
		$updatedFullSpot = $svcFmtParsing->parseFull($fullSpotXml);
		# add the message id and updated fullspot xml because they are not added
		# to the spot when parsing the updated fullspot xml
		$updatedFullSpot['messageid'] = $messageId;
		$updatedFullSpot['fullxml'] = $fullSpotXml;
		# finally store the updated spot in the database
		$daoSpot = $this->_daoFactory->getSpotDao();
		$daoSpot->editSpot($updatedFullSpot, $this->_currentSession['user']['username']);
	} # updateSpot

	/*
	 * Validate the data entered by the user, merge the original
	 * fullspot with the data entered by the user and create the
	 * updated fullspot xml that will be stored in the database.
	 * 
	 * The following fields can be merged into the fullspot:
	 * 'title', 'body', 'tag', 'website', 'category', 'subcata',
	 * 'subcatb', 'subcatc', 'subcatd' and 'subcatz'
	 */
	function updateSpotXml($fullSpot, $updatesToApply) {
		$result = new Dto_FormResult();
		/*
		 * before we merge we first want to clean the form from the stuff
		 * we don't want to merge with the original spot
		 */
		$spot = $this->cleanseUpdates($updatesToApply);

		# Title cannot be empty or very short
		$this->verifyTitle($spot, $result);

		# Body cannot be empty, very short or too long
		$this->verifyBody($spot, $result);

		# Verify the categories
		$this->verifyCategories($spot, $result);

		if ($result->isSuccess()) {
			# We now merge the cleaned edit form into the original spot
			$spot = array_merge($fullSpot, $spot);

			$imageInfo = array('height' => $spot['image']['height'],
							   'width' => $spot['image']['width'],
							   'segments' => $spot['image']['segment']);

			$nzbSegmentList = $spot['nzb'];

			# Parse the updated spot to an XML structure
			$spotCreator = new Services_Format_Creation();
			$spotXml = $spotCreator->convertSpotToXml($spot, $imageInfo, $nzbSegmentList);

			$result->addData('spotxml', $spotXml);
		} # if

		return $result;
	} # updateSpotXml

	private function verifyTitle(&$spot, &$result) {

		# Title cannot be empty or very short
		$spot['title'] = trim($spot['title']);
		if (strlen($spot['title']) < 5) {
			$result->addError(_('Enter a title'));
		} # if

		/*
		 * If the post's character does not fit into ISO-8859-1, we HTML
		 * encode the UTF-8 characters so we can properly post the spots
		 */
		if (mb_detect_encoding($spot['title'], 'UTF-8, ISO-8859-1', true) == 'UTF-8') {
			$spot['title'] = mb_convert_encoding($spot['title'], 'HTML-ENTITIES', 'UTF-8');
		} # if
	} # verifyTitle

	private function verifyBody(&$spot, &$result) {

		# Body cannot be empty, very short or too long
		$spot['body'] = trim($spot['body']);
		if (strlen($spot['body']) < 30) {
			$result->addError(_('Please enter a description'));
		} # if
		if (strlen($spot['body']) > 9000) {
			$result->addError(_('Entered description is too long'));
		} # if

	} # verifyBody

	private function verifyCategories(&$spot, &$result) {

		/* Make sure the category is valid
		 * We use array_key_exists() to allow for gaps in the category numbering. This is an intentional
		 * deviation from similar code used in Services_Posting_Spot.php
		 */
		if (!array_key_exists($spot['category'], SpotCategories::$_head_categories)) {
			$result->addError(sprintf(_('Incorrect headcategory (%s)'), $spot['category']));
		} # if

		# Make sure the subcategories are in the proper format
		if (is_array($spot['subcata']) || is_array($spot['subcatz'])) {
			$result->addError(_('Invalid subcategories given'));
		} # if

		# if subcat is not an array, then make it an array so that merging the
		# arrays won't cause us any trouble
		if (!is_array($spot['subcatb'])) { $spot['subcatb'] = array(); }
		if (!is_array($spot['subcatc'])) { $spot['subcatc'] = array(); }
		if (!is_array($spot['subcatd'])) { $spot['subcatd'] = array(); }

		# create a list of the chosen subcategories
		$spot['subcatlist'] = array_merge(
		array($spot['subcata']),
			  $spot['subcatb'],
			  $spot['subcatc'],
			  $spot['subcatd']
		 );

		/*
		 * Loop through all subcategories and check if they are valid in
		 * our list of subcategories
		 */
		$subCatSplitted = array('a' => array(), 'b' => array(), 'c' => array(), 'd' => array(), 'z' => array());

		foreach($spot['subcatlist'] as $subCat) {
			$subcats = explode('_', $subCat);
			# If not in our format
			if (count($subcats) != 3) {
				$result->addError(sprintf(_('Incorrect subcategories (%s)'), $subCat));
			} else {
				$subCatLetter = substr($subcats[2], 0, 1);

				$subCatSplitted[$subCatLetter][] = $subCat;

				if (!isset(SpotCategories::$_categories[$spot['category']][$subCatLetter][substr($subcats[2], 1)])) {
					$result->addError(sprintf(_('Incorrect subcategories (%s)'), $subCat . ' !! ' . $subCatLetter . ' !! ' . substr($subcats[2], 1)));
				} # if
			} # else
		} # foreach

		/*
		 * Make sure all subcategories are in the format we expect, for
		 * example we strip the 'cat' part and strip the z-subcat
		 */
		$subcatCount = count($spot['subcatlist']);
		for($i = 0; $i < $subcatCount; $i++) {
			$subcats = explode('_', $spot['subcatlist'][$i]);

			# If not in our format
			if (count($subcats) != 3) {
				$result->addError(sprintf(_('Incorrect subcategories (%s)'), $spot['subcatlist'][$i]));
			} else {
				$spot['subcatlist'][$i] = substr($subcats[2], 0, 1) . str_pad(substr($subcats[2], 1), 2, '0', STR_PAD_LEFT);

				# Explicitly add the 'z'-category - we derive it from the full categorynames we already have
				$zcatStr = substr($subcats[1], 0, 1) . str_pad(substr($subcats[1], 1), 2, '0', STR_PAD_LEFT);
				if ((is_numeric(substr($subcats[1], 1))) && (array_search($zcatStr, $spot['subcatlist']) === false)) {
					$spot['subcatlist'][] = $zcatStr;
				} # if
			} # else
		} # for

		# Make sure the spot isn't being posted in many categories
		if (count($subCatSplitted['a']) > 1) {
			$result->addError(_('You can only specify one format for a spot'));
		} # if

		# Make sure the spot has at least a format
		if (count($subCatSplitted['a']) < 1) {
			$result->addError(_('You need to specify a format for a spot'));
		} # if

		# Make sure the spot isn't being posted for too many categories
		if (count($spot['subcatlist']) > 10) {
			$result->addError(_('Too many categories'));
		} # if

		# Make sure the spot isn't being posted for too many categories
		if (count($spot['subcatlist']) < 2) {
			$result->addError(_('At least one category need to be selected'));
		} # if

	} # verifyCategories
	/*
	 * remove all fields from the array that we do not want to merge
	 * with the original fullspot
	 */
	private function cleanseUpdates($updatesToApply) {
		# Only keep the fields we want to merge
		$validFields = array('title', 'body', 'tag', 'website', 'category', 'subcata', 'subcatb', 'subcatc', 'subcatd', 'subcatz');
		foreach($updatesToApply as $key => $value) {
			if (in_array($key, $validFields) === false) {
				unset($updatesToApply[$key]);
			} # if
		} # foreach

		return $updatesToApply;
	} # cleanseEditForm

} # Services_Spot_Record