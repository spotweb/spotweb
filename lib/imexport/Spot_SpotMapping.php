<?php

class Spot_SpotMapping {
	public static $fieldMapping = array(
				'id'			=> 0,
				'messageid'		=> 1,
				'spotid'		=> 2,
				'category'		=> 3,
				'subcat'		=> 4,
				'poster'		=> 5,
				'groupname'		=> 6,
				'subcata'		=> 7,
				'subcatb'		=> 8,
				'subcatc'		=> 9,
				'subcatd'		=> 10,
				'title'			=> 11,
				'tag'			=> 12,
				'stamp'			=> 13,
				'filesize'		=> 14,
				'moderated'		=> 15,
				'userid'		=> 16,
				'verified'		=> 17,
				'user-signature'=> 18,
				'user-key'		=> 19,
				'xml-signature'	=> 20,
				'fullxml'		=> 21);

	public static $valueMapping = array();
	
	public static function myCtor() {
		self::$valueMapping = array_flip(self::$fieldMapping);
	} # myCtor
} # class Spot_SpotMapping

# Roep myCtor() aan om te zorgen dat $valueMapping geinitialiseerd is
Spot_SpotMapping::myCtor();
