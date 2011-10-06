<?php

class SpotCategories {
	public static $_head_categories =
	  Array(0 => "Beeld",
			1 => "Geluid",
			2 => "Spellen",
			3 => "Applicaties");

	public static $_headcat_subcat_mapping =
	  Array(0 => 'd',
			1 => 'd',
			2 => 'c',
			3 => 'b');

	public static $_subcat_descriptions =
		Array(0 =>
			Array('a' => 'Formaat',
				  'b' => 'Bron',
				  'c' => 'Taal',
				  'd' => 'Genre',
				  'z' => 'Type'),
			  1 =>
			Array('a' => 'Formaat',
				  'b' => 'Bron',
				  'c' => 'Bitrate',
				  'd' => 'Genre',
				  'z' => 'Type'),
			  2 =>
			Array('a' => 'Platform',
				  'b' => 'Formaat',
				  'c' => 'Genre'),
			  3 =>
			Array('a' => 'Platform',
				  'b' => 'Genre')
			);

	public static $_shortcat =
		Array(0 =>
				Array(0 => "DivX",
					  1 => "WMV",
					  2 => "MPG",
					  3 => "DVD5",
					  4 => "HD Ovg",
					  5 => "ePub",
					  6 => "Blu-ray",
					  7 => "HD-DVD",
					  8 => "WMVHD",
					  9 => "x264HD",
					  10 => "DVD9"),
			  1 =>
				Array(0	=> "MP3",
					  1 => "WMA",
					  2 => "WAV",
					  3 => "OGG",
					  4 => "EAC",
					  5 => "DTS",
					  6 => "AAC",
					  7 => "APE",
					  8 => "FLAC"),
			  2 =>
				Array(0 => "WIN",
					  1 => "MAC",
					  2 => "TUX",
					  3 => "PS",
					  4 => "PS2",
					  5 => "PSP",
					  6 => "XBX",
					  7 => "360",
					  8 => "GBA",
					  9 => "GC",
					  10 => "NDS",
					  11 => "Wii",
					  12 => "PS3",
					  13 => "WinPh",
					  14 => "iOS",
					  15 => "Android",
					  16 => "3DS"),
			  3 =>
				Array(0 => "WIN",
					  1 => "MAC",
					  2 => "TUX",
					  3 => "OS/2",
					  4 => "WinPh",
					  5 => "NAV",
					  6 => "iOS",
					  7 => "Android")
			);

	public static $_categories =
		Array(0 =>
			Array('a' =>
				Array(0 => array("DivX", array("z0", "z1", "z3")),
					  1 => array("WMV", array("z0", "z1", "z3")),
					  2 => array("MPG", array("z0", "z1", "z3")),
					  3 => array("DVD5", array("z0", "z1", "z3")),
					  4 => array("HD Overig", array()),
					  5 => array("ePub", array("z2")),
					  6 => array("Blu-ray", array("z0", "z1", "z3")),
					  7 => array("HD-DVD", array()),
					  8 => array("WMVHD", array()),
					  9 => array("x264", array("z0", "z1", "z3")),
					  10 => array("DVD9", array("z0", "z1", "z3"))),
				  'b' =>
				Array(0	=> array("CAM", array("z0", "z1", "z3")),
					  1 => array("(S)VCD", array()),
					  2 => array("Promo", array()),
					  3 => array("Retail", array("z0", "z1", "z2", "z3")),
					  4 => array("TV", array()),
					  5 => array("", array()),
					  6 => array("Satelliet", array()),
					  7 => array("R5", array("z0", "z1", "z3")),
					  8 => array("Telecine", array()),
					  9 => array("Telesync", array("z0", "z1", "z3")),
					  10 => array("Scan", array("z2"))),
				  'c' =>
				Array(0 => array("Geen ondertitels", array("z0", "z1", "z3")),
					  1 => array("Nederlands ondertiteld (extern)", array("z0", "z1", "z3")),
					  2 => array("Nederlands ondertiteld (ingebakken)", array("z0", "z1", "z3")),
					  3 => array("Engels ondertiteld (extern)", array("z0", "z1", "z3")),
					  4 => array("Engels ondertiteld (ingebakken)", array("z0", "z1", "z3")),
					  5 => array("", array()), 
					  6 => array("Nederlands ondertiteld (instelbaar)", array("z0", "z1", "z3")),
					  7 => array("Engels ondertiteld (instelbaar)", array("z0", "z1", "z3")),
					  8 => array("", array()),
					  9 => array("", array()),
					  10 => array("Engels gesproken/geschreven", array("z0", "z1", "z2", "z3")),
					  11 => array("Nederlands gesproken/geschreven", array("z0", "z1", "z2", "z3")),
					  12 => array("Duits gesproken/geschreven", array("z0", "z1", "z2", "z3")),
					  13 => array("Frans gesproken/geschreven", array("z0", "z1", "z2", "z3")),
					  14 => array("Spaans gesproken/geschreven", array("z0", "z1", "z2", "z3"))),
				  'd' =>
				Array(0  => array("Actie", array("z0", "z1")),
					  1  => array("Avontuur", array("z0", "z1", "z2")),
					  2  => array("Animatie", array("z0", "z1")),
					  3  => array("Cabaret", array("z0", "z1")),
					  4  => array("Komedie", array("z0", "z1")),
					  5  => array("Misdaad", array("z0", "z1", "z2")),
					  6  => array("Documentaire", array("z0", "z1")),
					  7  => array("Drama", array("z0", "z1", "z2")),
					  8  => array("Familie", array("z0", "z1")),
					  9  => array("Fantasie", array("z0", "z1", "z2")),
					  10  => array("Filmhuis", array("z0", "z1")),
					  11  => array("Televisie", array("z0", "z1")),
					  12  => array("Horror", array("z0", "z1")),
					  13  => array("Muziek", array("z0", "z1")),
					  14  => array("Musical", array("z0", "z1")),
					  15  => array("Mysterie", array("z0", "z1", "z2")),
					  16  => array("Romantiek", array("z0", "z1", "z2")),
					  17  => array("Science Fiction", array("z0", "z1", "z2")),
					  18  => array("Sport", array("z0", "z1")),
					  19  => array("Korte film", array("z0", "z1")),
					  20  => array("Thriller", array("z0", "z1", "z2")),
					  21  => array("Oorlog", array("z0", "z1", "z2")),
					  22  => array("Western", array("z0", "z1")),
					  23  => array("Erotiek (hetero)", array()),
					  24  => array("Erotiek (gay mannen)", array()),
					  25  => array("Erotiek (gay vrouwen)", array()),
					  26  => array("Erotiek (bi)", array()), 
					  27  => array("", array()),
					  28  => array("Asian", array("z0", "z1")),
					  29  => array("Anime", array("z0", "z1")),
					  30  => array("Cover", array("z2")),
					  31  => array("Stripboek", array("z2")),
					  32  => array("Cartoons", array("z2")),
					  33  => array("Jeugd", array("z2")),
					  34  => array("Zakelijk", array("z2")),
					  35  => array("Computer", array("z2")),
					  36  => array("Hobby", array("z2")),
					  37  => array("Koken", array("z2")),
					  38  => array("Knutselen", array("z2")),
					  39  => array("Handwerk", array("z2")),
					  40  => array("Gezondheid", array("z2")),
					  41  => array("Historie", array("z0", "z1", "z2")),
					  42  => array("Psychologie", array("z2")),
					  43  => array("Dagblad", array("z2")),
					  44  => array("Tijdschrift", array("z2")),
					  45  => array("Wetenschap", array("z2")),
					  46  => array("Vrouw", array("z2")),
					  47  => array("Religie", array("z2")),
					  48  => array("Roman", array("z2")),
					  49  => array("Biografie", array("z2")),
					  50  => array("Detective", array("z0", "z1", "z2")),
					  51  => array("Dieren", array("z0", "z1", "z2")),
					  52  => array("Humor", array("z0", "z1", "z2")),
					  53  => array("Reizen", array("z2")),
					  54  => array("Waargebeurd", array()),
					  55  => array("Non-fictie", array("z2")),
					  56  => array("Politiek", array()),
					  57  => array("Poezie", array("z2")),
					  58  => array("Sprookje", array("z2")),
					  59  => array("Techniek", array("z2")),
					  60  => array("Kunst", array("z2")),
					  72  => array("Bi", array("z3")),
					  73  => array("Lesbo", array("z3")),
					  74  => array("Homo", array("z3")),
					  75  => array("Hetero", array("z3")),
					  76  => array("Amateur", array("z3")),
					  77  => array("Groep", array("z3")),
					  78  => array("POV", array("z3")),
					  79  => array("Solo", array("z3")),
					  80  => array("Jong", array("z3")),
					  81  => array("Soft", array("z3")),
					  82  => array("Fetisj", array("z3")),
					  83  => array("Oud", array("z3")),
					  84  => array("Dik", array("z3")),
					  85  => array("SM", array("z3")),
					  86  => array("Ruig", array("z3")),
					  87  => array("Donker", array("z3")),
					  88  => array("Hentai", array("z3")),
					  89  => array("Buiten", array("z3"))),
				  'z' =>
				Array(0	=> "Film",
					  1 => "Serie",
					  2 => "Boek",
					  3 => "Erotiek")
			),
			  1 => Array(
				  'a' =>
				Array(0	=> array("MP3", array("z0", "z1", "z2", "z3")),
					  1 => array("WMA", array("z0", "z1", "z2", "z3")),
					  2 => array("WAV", array("z0", "z1", "z2", "z3")),
					  3 => array("OGG", array("z0", "z1", "z2", "z3")),
					  4 => array("EAC", array("z0", "z1", "z2", "z3")),
					  5 => array("DTS", array("z0", "z1", "z2", "z3")),
					  6 => array("AAC", array("z0", "z1", "z2", "z3")),
					  7 => array("APE", array("z0", "z1", "z2", "z3")),
					  8 => array("FLAC", array("z0", "z1", "z2", "z3"))),
				  'b' =>
				Array(0 => array("CD", array("z0", "z1", "z2", "z3")),
					  1 => array("Radio", array("z0", "z1", "z2", "z3")),
					  2 => array("Compilatie", array()),
					  3 => array("DVD", array("z0", "z1", "z2", "z3")),
					  4 => array("Overig", array()),
					  5 => array("Vinyl", array("z0", "z1", "z2", "z3")),
					  6 => array("Stream", array("z0", "z1", "z2", "z3"))),
				  'c' =>
				Array(0 => array("Variabel", array("z0", "z1", "z2", "z3")),
					  1 => array("< 96kbit", array("z0", "z1", "z2", "z3")),
					  2 => array("96kbit", array("z0", "z1", "z2", "z3")),
					  3 => array("128kbit", array("z0", "z1", "z2", "z3")),
					  4 => array("160kbit", array("z0", "z1", "z2", "z3")),
					  5 => array("192kbit", array("z0", "z1", "z2", "z3")),
					  6 => array("256kbit", array("z0", "z1", "z2", "z3")),
					  7 => array("320kbit", array("z0", "z1", "z2", "z3")),
					  8 => array("Lossless", array("z0", "z1", "z2", "z3")),
					  9 => array("Overig", array("z0", "z1", "z2", "z3"))),
				  'd' =>
				Array(0 => array("Blues", array("z0", "z1", "z2", "z3")),
					  1 => array("Compilatie", array("z0", "z1", "z2", "z3")),
					  2 => array("Cabaret", array("z0", "z1", "z2", "z3")),
					  3 => array("Dance", array("z0", "z1", "z2", "z3")),
					  4 => array("Diversen", array("z0", "z1", "z2", "z3")),
					  5 => array("Hardcore", array("z0", "z1", "z2", "z3")),
					  6 => array("Wereld", array()),
					  7 => array("Jazz", array("z0", "z1", "z2", "z3")),
					  8 => array("Jeugd", array("z0", "z1", "z2", "z3")),
					  9 => array("Klassiek", array("z0", "z1", "z2", "z3")),
					  10 => array("Kleinkunst", array()),
					  11 => array("Hollands", array("z0", "z1", "z2", "z3")),
					  12 => array("New Age", array()),
					  13 => array("Pop", array("z0", "z1", "z2", "z3")),
					  14 => array("RnB", array("z0", "z1", "z2", "z3")),
					  15 => array("Hiphop", array("z0", "z1", "z2", "z3")),
					  16 => array("Reggae", array("z0", "z1", "z2", "z3")),
					  17 => array("Religieus", array()),
					  18 => array("Rock", array("z0", "z1", "z2", "z3")),
					  19 => array("Soundtracks", array("z0", "z1", "z2", "z3")),
					  20 => array("Overig", array()),
					  21 => array("Hardstyle", array()),
					  22 => array("Asian", array()),
					  23 => array("Disco", array("z0", "z1", "z2", "z3")),
					  24 => array("Classics", array("z0", "z1", "z2", "z3")),
					  25 => array("Metal", array("z0", "z1", "z2", "z3")),
					  26 => array("Country", array("z0", "z1", "z2", "z3")),
					  27 => array("Dubstep", array("z0", "z1", "z2", "z3")),
					  28 => array("Nederhop", array("z0", "z1", "z2", "z3")),
					  29 => array("DnB", array("z0", "z1", "z2", "z3")),
					  30 => array("Electro", array("z0", "z1", "z2", "z3")),
					  31 => array("Folk", array("z0", "z1", "z2", "z3")),
					  32 => array("Soul", array("z0", "z1", "z2", "z3")),
					  33 => array("Trance", array("z0", "z1", "z2", "z3")),
					  34 => array("Balkan", array("z0", "z1", "z2", "z3")),
					  35 => array("Techno", array("z0", "z1", "z2", "z3")),
					  36 => array("Ambient", array("z0", "z1", "z2", "z3")),
					  37 => array("Latin", array("z0", "z1", "z2", "z3")),
					  38 => array("Live", array("z0", "z1", "z2", "z3"))),
				  'z' =>
				Array(0	=> "Album",
					  1 => "Liveset",
					  2 => "Podcast",
					  3 => "Luisterboek")
			),
			  2 => Array(
				  'a' =>
				Array(0 => array("Windows", array("zz")),
					  1 => array("Macintosh", array("zz")),
					  2 => array("Linux", array("zz")),
					  3 => array("Playstation", array("zz")),
					  4 => array("Playstation 2", array("zz")),
					  5 => array("PSP", array("zz")),
					  6 => array("Xbox", array("zz")),
					  7 => array("Xbox 360", array("zz")),
					  8 => array("Gameboy Advance", array("zz")),
					  9 => array("Gamecube", array("zz")),
					  10 => array("Nintendo DS", array("zz")),
					  11 => array("Nintento Wii", array("zz")),
					  12 => array("Playstation 3", array("zz")),
					  13 => array("Windows Phone", array("zz")),
					  14 => array("iOS", array("zz")),
					  15 => array("Android", array("zz")),
					  16 => array("Nintendo 3DS", array("zz"))),
				  'b' =>
				Array(0 => array("ISO", array()),
					  1 => array("Rip", array("zz")),
					  2 => array("Retail", array("zz")),
					  3 => array("DLC", array("zz")),
					  4 => array("", array()),
					  5 => array("Patch", array("zz")),
					  6 => array("Crack", array("zz"))),
				  'c' =>
				Array(0 => array("Actie", array("zz")),
					  1 => array("Avontuur", array("zz")),
					  2 => array("Strategie", array("zz")),
					  3 => array("Rollenspel", array("zz")),
					  4 => array("Simulatie", array("zz")),
					  5 => array("Race", array("zz")),
					  6 => array("Vliegen", array("zz")),
					  7 => array("Shooter", array("zz")),
					  8 => array("Platform", array("zz")),
					  9 => array("Sport", array("zz")),
					  10 => array("Kinder/jeugd", array("zz")),
					  11 => array("Puzzel", array("zz")),
					  12 => array("Overig", array()),
					  13 => array("Bordspel", array("zz")),
					  14 => array("Kaarten", array("zz")),
					  15 => array("Educatie", array("zz")),
					  16 => array("Muziek", array("zz")),
					  17 => array("Familie", array("zz"))),
				  'z' =>
				Array('z' => "alles")
			),
			  3 => Array(
				   'a' =>
				Array(0 => array("Windows", array("zz")),
					  1 => array("Macintosh", array("zz")),
					  2 => array("Linux", array("zz")),
					  3 => array("OS/2", array("zz")),
					  4 => array("Windows Phone", array("zz")),
					  5 => array("Navigatiesystemen", array("zz")),
					  6 => array("iOS", array("zz")),
					  7 => array("Android", array("zz"))),
				  'b' =>
				Array(0 => array("Audio", array("zz")),
					  1 => array("Video", array("zz")),
					  2 => array("Grafisch", array("zz")),
					  3 => array("CD/DVD Tools", array()),
					  4 => array("Media spelers",  array()),
					  5 => array("Rippers &amp; Encoders", array()),
					  6 => array("Plugins", array()),
					  7 => array("Database tools", array()),
					  8 => array("Email software", array()),
					  9 => array("Foto", array()),
					  10 => array("Screensavers", array()),
					  11 => array("Skin software", array()),
					  12 => array("Drivers", array()),
					  13 => array("Browsers", array()),
					  14 => array("Download managers", array()),
					  15 => array("Download", array("zz")),
					  16 => array("Usenet software", array()),
					  17 => array("RSS Readers", array()),
					  18 => array("FTP software", array()),
					  19 => array("Firewalls", array()),
					  20 => array("Antivirus software", array()),
					  21 => array("Antispyware software", array()),
					  22 => array("Optimalisatiesoftware", array()),
					  23 => array("Beveiligingssoftware", array("zz")),
					  24 => array("Systeemsoftware", array("zz")),
					  25 => array("Other", array()),
					  26 => array("Educatief", array("zz")),
					  27 => array("Kantoor", array("zz")),
					  28 => array("Internet", array("zz")),
					  29 => array("Communicatie", array("zz")),
					  30 => array("Ontwikkel", array("zz")),
					  31 => array("Spotnet", array("zz"))),
				  'z' =>
				Array('z' => "alles")
			)
		);

	static function Cat2Desc($hcat, $cat) {
		$catList = explode("|", $cat);
		$cat = $catList[0];

		if (empty($cat[0])) {
			return '';
		} # if

		$type = $cat[0];
		$nr = substr($cat, 1);

		if (!isset(self::$_categories[$hcat][$type][$nr][0])) {
			return "-";
		} else {
			if ($type !== 'z') {
				return self::$_categories[$hcat][$type][$nr][0];
			} else {
				return self::$_categories[$hcat][$type][$nr];
			} # else
		} # if
	}

	static function Cat2ShortDesc($hcat, $cat) {
		$catList = explode("|", $cat);
		$cat = $catList[0];

		if (empty($cat[0])) {
			return '';
		} # if

		$nr = substr($cat, 1);

		if (!isset(self::$_shortcat[$hcat][$nr])) {
			return "-";
		} else {
			return self::$_shortcat[$hcat][$nr];
		} # if
	}

	static function SubcatDescription($hcat, $ch) {
		if ((isset(self::$_subcat_descriptions[$hcat])) && (isset(self::$_subcat_descriptions[$hcat][$ch]))) {
			return self::$_subcat_descriptions[$hcat][$ch];
		} else {
			return '-';
		} # else
	} # func SubcatDescription

	static function SubcatNumberFromHeadcat($hcat) {
		if (isset(self::$_headcat_subcat_mapping[$hcat])) {
			return self::$_headcat_subcat_mapping[$hcat];
		} else {
			return '-';
		} # else
	} # SubcatNumberFromHeadcat

	static function HeadCat2Desc($cat) {
		if (isset(self::$_head_categories[$cat])) {
			return self::$_head_categories[$cat];
		} else {
			return '-';
		} # else
	} # func. Cat2Desc

	static function createSubcatZ($hcat, $subcats) {
		# z-categorieen gelden tot nu toe enkel voor films en muziek
		if (($hcat != 0) && ($hcat != 1)) {
			return '';
		} # if

		$genreSubcatList = explode('|', $subcats);
		$subcatz = '';

		foreach($genreSubcatList as $subCatVal) {
			if ($subCatVal == '') {
				continue;
			} # if

			if ($hcat == 0) {
				# 'Erotiek'
				if (stripos('d23|d24|d25|d26|d72|d73|d74|d75|d76|d77|d78|d79|d80|d81|d82|d83|d84|d85|d86|d87|d88|d89|', ($subCatVal . '|')) !== false) {
					$subcatz = 'z3|';
				} elseif (stripos('b4|d11|', ($subCatVal . '|')) !== false) {
					# Series
					$subcatz = 'z1|';
				} elseif (stripos('a5|', ($subCatVal . '|')) !== false) {
					# Boeken
					$subcatz = 'z2|';
				} elseif (empty($subcatz)) {
					# default, film
					$subcatz = 'z0|';
				} # else
			} elseif ($hcat == 1) {
				$subcatz = 'z0|';
				break;
			} # if muziek
		} # foreach

		return $subcatz;
	} # createSubcatZ
}