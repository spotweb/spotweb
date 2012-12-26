<?php

class Services_Nntp_EnginePool {
	private static $_instances = array();
	
	/*
	 * Returns an Sevice_Nntp_Engine but tries to minimize
	 * the amount of different objects and hence connections 
	 * which are created by issueing existing NNTP engines
	 * when possible
	 */
	static public function pool(Services_Settings_Base $settings, $type) {
		if (isset(self::$_instances[$type])) {
			return self::$_instances[$type];
		} # if

		/*
		 * Make sure we have a valid NNTP configuration
		 */
		$settings_nntp_hdr = $settings->get('nntp_hdr');
		if (empty($settings_nntp_hdr)) {
			throw new MissingNntpConfigurationException();
		} # if

		/*
		 * Retrieve the NNTP header settings we can validate those
		 */
		switch ($type) {
			case 'hdr'			: self::$_instances[$type] = new Services_Nntp_Engine($settings_nntp_hdr); break;
			case 'bin'			: {

				$settings_nntp_bin = $settings->get('nntp_nzb');
				if (empty($settings_nntp_nzb['host'])) {
					self::$_instances[$type] = self::pool($settings, 'hdr');
				} else {
					self::$_instances[$type] = new Services_Nntp_Engine($settings_nntp_nzb);
				} # else

				break;
			} # nzb

			case 'post'			: {
				$settings_nntp_post = $settings->get('nntp_post');
				if (empty($settings_nntp_post['host'])) {
					self::$_instances[$type] = self::pool($settings, 'hdr');
				} else {
					self::$_instances[$type] = new Services_Nntp_Engine($settings_nntp_post);
				} # else

				break;
			} # post

			default 			: throw new Exception("Unknown NNTP type engine (" . $type . ") for pool creation");
		} # switch

		return self::$_instances[$type];
	} # pool

} # Services_Nntp_Engine_Pool
