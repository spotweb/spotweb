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
				  'd' => 'Genre'),
			  1 =>
			Array('a' => 'Formaat',
			      'b' => 'Bron',
				  'c' => 'Bitrate',
				  'd' => 'Genre'),
			  2 =>
			Array('a' => 'Platform',
			      'b' => 'Formaat',
				  'c' => 'Genre'),
			  3 =>
			Array('a' => 'Platform',
				  'b' => 'Genre')
			);
			
	public static $_categories = 
		Array(0 => 
			Array('a' => 
				Array(0 => "DivX",
					  1 => "WMV",
					  2 => "MPG",
					  3 => "DVD5",
					  4 => "HD Overig",
					  5 => "eBook",
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
					  9 => "Telesync"),
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
					  8  => "Familiefilm",
					  9  => "Fantasiefilm",
					  10  => "Film Noir",
					  11  => "TV Series",
					  12  => "Horror",
					  13  => "Muziek",
					  14  => "Musical",
					  15  => "Mysterie",
					  0x10  => "Romantiek",
					  0x11  => "Science Fiction",
					  0x12  => "Sport",
					  0x13  => "Korte film",
					  20  => "Thriller",
					  0x15  => "Oorlog",
  					  0x16  => "Western",
					  0x17  => "Erotiek (hetero)",
					  0x18  => "Erotiek (gay mannen)",
					  0x19  => "Erotiek (gay vrouwen)",
					  0x1a  => "Erotiek (bi)",
					  0x1b  => "",
					  0x1c  => "Asian",
					  0x1d  => "Anime",
					  30  => "Cover",
					  0x1f  => "Comics",
					  0x20  => "Cartoons",
					  0x21  => "Kinderfilm")
			),
			  1 => Array(
			      'a' => 
				Array(0	=> "MP3",
					  1 => "WMA",
					  2 => "WAV",
					  3 => "OGG",
					  4 => "Lossless",
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
					  5 => "Vinyl"),
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
				Array(0 => "Blues/Folk",
					  1 => "Compilatie",
					  2 => "Cabaret",
					  3 => "Dance",
					  4 => "Diversen",
					  5 => "Hardcore",
					  6 => "Internationaal",
  					  7 => "Jazz",
					  8 => "Kinder/Jeugd",
				  	  9 => "Klassiek",
					  10 => "Kleinkunst",
					  11 => "Nederlands",
					  12 => "New Age",
					  13 => "Pop",
					  14 => "R&B/Soul",
					  15 => "Hip hop",
					  0x10 => "Reggae",
					  0x11 => "Religieus",
					  0x12 => "Rock",
				 	  0x13 => "Soundtracks",
					  20 => "",
					  0x15 => "Jumpstyle",
					  0x16 => "Asian",
					  0x17 => "Disco",
					  0x18 => "Oldskool",
					  0x19 => "Metal",
					  0x1a => "Country")
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
					  14 => "iOs",
					  15 => "Android"),
				  'b' => 
				Array(0 => "ISO",
					  1 => "Rip",
					  2 => "DVD",
					  3 => "Addon",
					  4 => ""),
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
					  12 => ""),
			),
			  3 => Array(
			       'a' =>
				Array(0 => "Windows",
					  1 => "Macintosh",
					  2 => "Linux",
					  3 => "OS/2",
					  4 => "Windows Phone",
					  5 => "Navigatiesystemen",
					  6 => "iOs",
					  7 => "Android"),
				  'b' =>
				Array(0 => "Audio bewerking",
                      1 => "Video bewerking",
                      2 => "Grafisch design",
                      3 => "CD/DVD Tools",
                      4 => "Media spelers",
                      5 => "Rippers & Encoders",
                      6 => "Plugins",
                      7 => "Database tools",
                      8 => "Email software",
					  9 => "Fotobewerking",
					  10 => "Screensavers",
					  11 => "Skin software",
					  12 => "Drivers",
					  13 => "Browsers",
					  14 => "Download managers",
					  15 => "File sharing",
					  0x10 => "Usenet software",
					  0x11 => "RSS Readers",
					  0x12 => "FTP software",
					  0x13 => "Firewalls",
					  20 => "Antivirus software",
					  0x15 => "Antispyware software",
					  0x16 => "Optimalisatiesoftware",
					  0x17 => "Beveiligingssoftware",
					  0x18 => "Systeemsoftware",
					  0x19 => "")
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

	static function SubcatDescription($hcat, $ch) {
		return self::$_subcat_descriptions[$hcat][$ch];
	} # func SubcatDescription
	
	static function SubcatNumberFromHeadcat($hcat) {
		return self::$_headcat_subcat_mapping[$hcat];
	} # SubcatNumberFromHeadcat
	
	static function HeadCat2Desc($cat) {
		return self::$_head_categories[$cat];
	} # func. Cat2Desc
} 
