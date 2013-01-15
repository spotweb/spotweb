<?php

class Services_Posting_Spot {
	private $_daoFactory;
	private $_settings;
	private $_nntp_post;
	private $_nntp_hdr;

	function __construct(Dao_Factory $daoFactory, Services_Settings_Base $settings) {
		$this->_daoFactory = $daoFactory;
		$this->_settings = $settings;
		$this->_nntp_post = new Services_Nntp_SpotPosting(Services_Nntp_EnginePool::instance('post'));
		$this->_nntp_hdr = new Services_Nntp_SpotPosting(Services_Nntp_EnginePool::instance('hdr'));
	} # ctor

	/*
	 * Post a spot to the usenet server. 
	 */
	public function postSpot(Services_User_Record $svcUserRecord, array $user, array $spot, $imageFilename, $nzbFilename) {
		$result = new Dto_FormResult();
		$spotDao = $this->_daoFactory->getSpotDao();

		# Make sure the anonymous user and reserved usernames cannot post content
		if (!$svcUserRecord->allowedToPost($user)) {
			$result->addError(_("You need to login to be able to post comments"));
		} # if

		# Retrieve the users' private key
		$user['privatekey'] = $svcUserRecord->getUserPrivateRsaKey($user['userid']);

		$hdr_newsgroup = $this->_settings->get('hdr_group');
		$bin_newsgroup = $this->_settings->get('nzb_group');

		# Make sure the subcategories are in the proper format
		if ((is_array($spot['subcata'])) || (is_array($spot['subcatz'])) || (!is_array($spot['subcatb'])) || (!is_array($spot['subcatc'])) || (!is_array($spot['subcatd']))) { 
			$result->addError(_('Invalid subcategories given'));
		} # if				

		/*
		 * We'll get the messageid's with <>'s but we always strip
		 * them in Spotweb, so remove them
		 */			
		$spot['newmessageid'] = substr($spot['newmessageid'], 1, -1);
/*
		$hdr_newsgroup = 'alt.test';
		$bin_newsgroup = 'alt.test';
*/

		# create a list of the chosen subcategories
		$spot['subcatlist'] = array_merge(
									array($spot['subcata']), 
									$spot['subcatb'], 
									$spot['subcatc'], 
									$spot['subcatd']
								);


		# If the hashcash doesn't match, we will never post it
		if (substr(sha1('<' . $spot['newmessageid'] . '>'), 0, 4) != '0000') {
			$result->addError(_('Hash was not calculated properly'));
		} # if

		# Read the contents of image so we can check it
		$imageContents = file_get_contents($imageFilename);

		# the image should be below 1MB
		if (strlen($imageContents) > 1024*1024) {
			$result->addError(_('Uploaded image is too large (maximum 1MB)'));
		} # if

		/*
		 * Get some image information, if it fails, this is an
		 * error as well
		 */
		$tmpGdImageSize = getimagesize($imageFilename);
		if ($tmpGdImageSize === false) {
			$result->addError(_('Uploaded image was not recognized as an image'));
		} else {
			$imageInfo = array('width' => $tmpGdImageSize[0],
					  	       'height' => $tmpGdImageSize[1]);
		} # if

		# Body cannot be empty, very short or too long
		$spot['body'] = trim($spot['body']);
		if (strlen($spot['body']) < 30) {
			$result->addError(_('Please enter an description'));
		} # if
		if (strlen($spot['body']) > 9000) {
			$result->addError(_('Entered description is too long'));
		} # if

		# Title cannot be empty or very short
		$spot['title'] = trim($spot['title']);
		if (strlen($spot['title']) < 5) {
			$result->addError(_('Enter a title'));
		} # if
		
		# Subcategory should be valid
		if (($spot['category'] < 0) || ($spot['category'] > count(SpotCategories::$_head_categories))) {
			$result->addError(sprintf(_('Incorrect headcategory (%s)'), $spot['category']));
		} # if
		
		/*
		 * Load the NZB file as an XML file so we can make sure 
		 * it's a valid XML and NZB file and we can determine the
		 * filesize
		 */
		$nzbFileContents = file_get_contents($nzbFilename);
		$nzbXml = simplexml_load_string($nzbFileContents);

		# Do some basic sanity checking for some required NZB elements
		if (empty($nzbXml->file)) {
			$result->addError(_('Incorrect NZB file'));
		} # if
		
		# and determine the total filesize
		$spot['filesize'] = 0;
		foreach($nzbXml->file as $file) {
			foreach($file->segments->segment as $seg) {
				$spot['filesize'] += (int) $seg['bytes'];
			} # foreach
		} # foreach
		
		/*
		 * Make sure we didn't use this messageid recently or at all, this
		 * prevents people from not recalculating the hashcash in order to spam
		 * the system
		 */
		if (!$spotDao->isNewSpotMessageIdUnique($spot['newmessageid'])) {
			$result->addError(_('Replay attack!?'));
		} # if

		# Make sure a newmessageid contains a certain length
		if (strlen($spot['newmessageid']) < 10) {
			$result->addError(_('MessageID too short!?'));
		} # if

		# We require the keyid 7 because it is selfsigned
		$spot['key'] = 7;
		
		# Poster's  username
		$spot['poster'] = $user['username'];
		
		# Fix up some overly long spot properties and other minor issues
		$spot['tag'] = substr(trim($spot['tag'], " |;\r\n\t"), 0, 99);
		$spot['website'] = substr(trim($spot['website']), 0, 449);
		
		/**
		 * If the post's character do not fit into ISO-8859-1, we HTML
		 * encode the UTF-8 characters so we can properly post the spots
		 */
		if (mb_detect_encoding($spot['title'], 'UTF-8, ISO-8859-1', true) == 'UTF-8') {
			$spot['title'] = mb_convert_encoding($spot['title'], 'HTML-ENTITIES', 'UTF-8');
		} # if

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

		# actually post the spot
		if ($result->isSuccess()) {
			/*
			 * Retrieve the image information and post the image to 
			 * the appropriate newsgroup so we have the messageid list of 
			 * images
			 */
			$imgSegmentList = $this->_nntp_post->postBinaryMessage($user, $bin_newsgroup, $imageContents, '');
			$imageInfo['segments'] = $imgSegmentList;
				
			# Post the NZB file to the appropriate newsgroups
			$nzbSegmentList = $this->_nntp_post->postBinaryMessage($user, $bin_newsgroup, gzdeflate($nzbFileContents), '');
			
			# Convert the current Spotnet info, to an XML structure
			$spotCreator = new Services_Format_Creation();
			$spotXml = $spotCreator->convertSpotToXml($spot, $imageInfo, $nzbSegmentList);
			$spot['spotxml'] = $spotXml;
			
			# And actually post to the newsgroups
			$this->_nntp_post->postFullSpot($user,
										   $this->_settings->get('privatekey'),  # Server private key
										   $hdr_newsgroup,
										   $spot);
			$spotDao->addPostedSpot($user['userid'], $spot, $spotXml);
		} # if

		return $result;
	} # postSpot
	
	
} # Services_Posting_Spot
