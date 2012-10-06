<?php
/*
 * Vormt basically de koppeling tussen DB en NNTP, waarbij de db als een soort
 * cache dient
 */
class SpotsOverview {
	private $_db;
	private $_cacheDao;
	private $_settings;

	function __construct(SpotDb $db, SpotSettings $settings) {
		$this->_db = $db;
		$this->_settings = $settings;
		$this->_cacheDao = $db->_cacheDao;
	} # ctor

	/*
	 * Geeft een Spotnet avatar image terug
	 */
	function getAvatarImage($md5, $size, $default, $rating) {
		SpotTiming::start(__FUNCTION__);
		$url = 'http://www.gravatar.com/avatar/' . $md5 . "?s=" . $size . "&d=" . $default . "&r=" . $rating;

		list($return_code, $data) = $this->getFromWeb($url, true, 60*60);

		$svc_ImageUtil = new Services_Image_Util();
		$dimensions = $svc_ImageUtil->getImageDimensions($data);

		$data = array('content' => $data);
		$data['metadata'] = $dimensions;
		$data['expire'] = true;
		SpotTiming::stop(__FUNCTION__, array($md5, $size, $default, $rating));
		return $data;
	} # getAvatarImage

	/* 
	 * Haalt een url op en cached deze
	 */
	function getFromWeb($url, $storeWhenRedirected, $ttl=900) {
		$x = new Services_Providers_Http($this->_db->_cacheDao);
		return $x->getFromWeb($url, $storeWhenRedirected, $ttl);
	} # getFromWeb


} # class SpotsOverview
