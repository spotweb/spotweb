<?php

class Spot_SpotMapping {
	public static $fieldMapping = array(
				'messageid'		=> 0,
				'spotid'		=> 1,
				'category'		=> 2,
				'subcat'		=> 3,
				'poster'		=> 4,
				'groupname'		=> 5,
				'subcata'		=> 6,
				'subcatb'		=> 7,
				'subcatc'		=> 8,
				'subcatd'		=> 9,
				'title'			=> 10,
				'tag'			=> 11,
				'stamp'			=> 12,
				'filesize'		=> 13,
				'moderated'		=> 14,
				'userid'		=> 15,
				'verified'		=> 16,
				'usersignature'	=> 17,
				'userkey'		=> 18,
				'xmlsignature'	=> 19,
				'fullxml'		=> 20,
				'filesize'		=> 21);

	public static $valueMapping = array();
	
	public static function myCtor() {
		self::$valueMapping = array_flip(self::$fieldMapping);
	} # myCtor
} # class Spot_SpotMapping

# Roep myCtor() aan om te zorgen dat $valueMapping geinitialiseerd is
Spot_SpotMapping::myCtor();
