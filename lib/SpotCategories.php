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
				Array(0 => "DivX",
					  1 => "WMV",
					  2 => "MPG",
					  3 => "DVD5",
					  4 => "HD Overig",
					  5 => "ePub",
					  6 => "Blu-ray",
					  7 => "HD-DVD",
					  8 => "WMVHD",
					  9 => "x264HD",
					  10 => "DVD9"),
				  'b' =>
				Array(0	=> "CAM",
					  1 => "(S)VCD",
					  2 => "Promo",
					  3 => "Retail",
					  4 => "TV",
					  5 => "",
					  6 => "Satelliet",
					  7 => "R5",
					  8 => "Telecine",
					  9 => "Telesync",
					  10 => "Scan"),
				  'c' =>
				Array(0 => "Geen ondertitels",
                      1 => "Nederlands ondertiteld (extern)",
                      2 => "Nederlands ondertiteld (ingebakken)",
                      3 => "Engels ondertiteld (extern)",
                      4 => "Engels ondertiteld (ingebakken)",
                      5 => "",
                      6 => "Nederlands ondertiteld (instelbaar)",
                      7 => "Engels ondertiteld (instelbaar)",
					  8 => "Fout!",
					  9 => "Fout",
                      10 => "Engels gesproken",
                      11 => "Nederlands gesproken",
                      12 => "Duits gesproken",
                      13 => "Frans gesproken",
                      14 => "Spaans gesproken"),
				  'd' =>
				Array(0  => "Actie",
					  1  => "Avontuur",
					  2  => "Animatie",
					  3  => "Cabaret",
					  4  => "Komedie",
					  5  => "Misdaad",
					  6  => "Documentaire",
					  7  => "Drama",
					  8  => "Familie",
					  9  => "Fantasie",
					  10  => "Filmhuis",
					  11  => "Televisie",
					  12  => "Horror",
					  13  => "Muziek",
					  14  => "Musical",
					  15  => "Mysterie",
					  16  => "Romantiek",
					  17  => "Science Fiction",
					  18  => "Sport",
					  19  => "Korte film",
					  20  => "Thriller",
					  21  => "Oorlog",
  					  22  => "Western",
					  23  => "Erotiek (hetero)",
					  24  => "Erotiek (gay mannen)",
					  25  => "Erotiek (gay vrouwen)",
					  26  => "Erotiek (bi)",
					  27  => "",
					  28  => "Asian",
					  29  => "Anime",
					  32  => "Cartoons",
					  33  => "Jeugd",
					  30  => "Cover",
					  43  => "Dagblad",
					  44  => "Tijdschrift",
					  31  => "Stripboek",
					  32  => "Studie",
					  33  => "Zakelijk",
					  34  => "Economie",
					  35  => "Computer",
					  36  => "Hobby",
					  37  => "Koken",
					  38  => "Knutselen",
					  39  => "Handwerk",
					  40  => "Gezondheid",
					  41  => "Historie",
					  42  => "Psychologie",
					  45  => "Wetenschap",
					  46  => "Vrouw",
					  47  => "Religie",
					  48  => "Roman",
					  49  => "Biografie",
					  50  => "Detective",
					  51  => "Dieren",
					  52  => "Humor",
					  53  => "Reizen",
					  54  => "Waargebeurd",
					  55  => "Non-fictie",
					  56  => "Politiek",
					  57  => "Poezie",
					  58  => "Sprookje",
					  59  => "Techniek",
					  60  => "Kunst",
					  75  => "Hetero",
					  74  => "Homo",
					  73  => "Lesbo",
					  72  => "Bi",
					  76  => "Amateur",
					  77  => "Groep",
					  78  => "POV",
					  79  => "Solo",
					  80  => "Jong",
					  81  => "Soft",
					  82  => "Fetisj",
					  83  => "Oud",
					  84  => "Dik",
					  85  => "SM",
					  86  => "Ruig",
					  87  => "Donker",
					  88  => "Hentai",
					  89  => "Buiten"),
				  'z' =>
				Array(0	=> "Film",
					  1 => "Serie",
					  2 => "Boek",
					  3 => "Erotiek")
			),
			  1 => Array(
			      'a' => 
				Array(0	=> "MP3",
					  1 => "WMA",
					  2 => "WAV",
					  3 => "OGG",
					  4 => "EAC",
					  5 => "DTS",
					  6 => "AAC",
					  7 => "APE",
					  8 => "FLAC"),
				  'b' => 
				Array(0 => "CD",
					  1 => "Radio",
					  2 => "Compilatie",
					  3 => "DVD",
					  4 => "",
					  5 => "Vinyl",
					  6 => "Stream"),
				  'c' =>
				Array(0 => "Variabel",
				      1 => "< 96kbit",
					  2 => "96kbit",
					  3 => "128kbit",
					  4 => "160kbit",
					  5 => "192kbit",
					  6 => "256kbit",
					  7 => "320kbit",
					  8 => "Lossless",
					  9 => ""),
				  'd' => 
				Array(0 => "Blues",
					  1 => "Compilatie",
					  2 => "Cabaret",
					  3 => "Dance",
					  4 => "Diversen",
					  5 => "Hardcore",
					  6 => "Wereld",
  					  7 => "Jazz",
					  8 => "Jeugd",
				  	  9 => "Klassiek",
					  10 => "Kleinkunst",
					  11 => "Hollands",
					  12 => "New Age",
					  13 => "Pop",
					  14 => "RnB",
					  15 => "Hiphop",
					  16 => "Reggae",
					  17 => "Religieus",
					  18 => "Rock",
				 	  19 => "Soundtracks",
					  20 => "",
					  21 => "Hardstyle",
					  22 => "Asian",
					  23 => "Disco",
					  24 => "Classics",
					  25 => "Metal",
					  26 => "Country",
					  27 => "Dubstep",
					  28 => "Nederhop",
					  29 => "DnB",
					  30 => "Electro",
					  31 => "Folk",
					  32 => "Soul",
					  33 => "Trance",
					  34 => "Balkan",
					  35 => "Techno",
					  36 => "Ambient",
					  37 => "Latin",
					  38 => "Live"),
				  'z' =>
				Array(0	=> "Album",
					  1 => "Liveset",
					  2 => "Podcast",
					  3 => "Luisterboek")
			),
			  2 => Array(
			  	  'a' => 
				Array(0 => "Windows",
					  1 => "Macintosh",
					  2 => "Linux",
					  3 => "Playstation",
					  4 => "Playstation 2",
					  5 => "PSP",
					  6 => "Xbox",
					  7 => "Xbox 360",
					  8 => "Gameboy Advance",
					  9 => "Gamecube",
					  10 => "Nintendo DS",
					  11 => "Nintento Wii",
					  12 => "Playstation 3",
					  13 => "Windows Phone",
					  14 => "iOS",
					  15 => "Android",
					  16 => "Nintendo 3DS"),
				  'b' => 
				Array(0 => "ISO",
					  1 => "Rip",
					  2 => "Retail",
					  3 => "DLC",
					  4 => "",
					  5 => "Patch",
					  6 => "Crack"),
				  'c' =>
				Array(0 => "Actie",
					  1 => "Avontuur",
					  2 => "Strategie",
					  3 => "Rollenspel",
					  4 => "Simulatie",
					  5 => "Race",
					  6 => "Vliegen",
					  7 => "Shooter",
					  8 => "Platform",
					  9 => "Sport",
					  10 => "Kinder/jeugd",
					  11 => "Puzzel",
					  12 => "",
					  13 => "Bordspel",
					  14 => "Kaarten",
					  15 => "Educatie",
					  16 => "Muziek",
					  17 => "Familie"),
			),
			  3 => Array(
			       'a' =>
				Array(0 => "Windows",
					  1 => "Macintosh",
					  2 => "Linux",
					  3 => "OS/2",
					  4 => "Windows Phone",
					  5 => "Navigatiesystemen",
					  6 => "iOS",
					  7 => "Android"),
				  'b' =>
				Array(0 => "Audio",
                      1 => "Video",
                      2 => "Grafisch",
                      3 => "CD/DVD Tools",
                      4 => "Media spelers",
                      5 => "Rippers &amp; Encoders",
                      6 => "Plugins",
                      7 => "Database tools",
                      8 => "Email software",
					  9 => "Foto",
					  10 => "Screensavers",
					  11 => "Skin software",
					  12 => "Drivers",
					  13 => "Browsers",
					  14 => "Download managers",
					  15 => "Download",
					  16 => "Usenet software",
					  17 => "RSS Readers",
					  18 => "FTP software",
					  19 => "Firewalls",
					  20 => "Antivirus software",
					  21 => "Antispyware software",
					  22 => "Optimalisatiesoftware",
					  23 => "Beveiligingssoftware",
					  24 => "Systeemsoftware",
					  25 => "",
					  26 => "Educatief",
					  27 => "Kantoor",
					  28 => "Internet",
					  29 => "Communicatie",
					  30 => "Ontwikkel",
					  31 => "Spotnet")
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
	
		if (!isset(self::$_categories[$hcat][$type][$nr])) {
			return "-";
		} else {
			return self::$_categories[$hcat][$type][$nr];
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
	
	static function SubcatToFilter($hcat, $scat) {
		$subcat = explode('|',$scat);
		return 'cat' . $hcat . '_' . $subcat[0];
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
} 
