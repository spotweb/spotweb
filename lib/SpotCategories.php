<?php

class SpotCategories {
	private static $_namesTranslated = false;
	
	public static $_head_categories =
	  Array(0 => "Image",
			1 => "Sound",
			2 => "Games",
			3 => "Applications");

	public static $_headcat_subcat_mapping =
	  Array(0 => 'd',
			1 => 'd',
			2 => 'c',
			3 => 'b');

	public static $_subcat_descriptions =
		Array(0 =>
			Array('a' => 'Format',
				  'b' => 'Source',
				  'c' => 'Language',
				  'd' => 'Genre',
				  'z' => 'Type'),
			  1 =>
			Array('a' => 'Format',
				  'b' => 'Source',
				  'c' => 'Bitrate',
				  'd' => 'Genre',
				  'z' => 'Type'),
			  2 =>
			Array('a' => 'Platform',
				  'b' => 'Format',
				  'c' => 'Genre',
				  'z' => 'Type'),
			  3 =>
			Array('a' => 'Platform',
				  'b' => 'Genre',
				  'z' => 'Type'),
			);

	public static $_shortcat =
		Array(0 =>
				Array(0 => "DivX",
					  1 => "WMV",
					  2 => "MPG",
					  3 => "DVD5",
					  4 => "HD Oth",
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

	/*
	 * The structure of the categorynames is als follows:
	 *
	 *  [0] == Name
	 *  [1] == To which 'type' (eg: Movie, Book, Erotica, etc) are they available for new selections
	 *  [2] == To which 'type' *were* they available in the past
	 *
	 * 	We cannot call the gettxt routines directly on this structure, so do this later
	 */
	public static $_categories =
		Array(0 =>
			Array('a' =>
				Array(0 => array("DivX", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  1 => array("WMV", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  2 => array("MPG", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  3 => array("DVD5", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  4 => array("HD other", array(), array("z0", "z1", "z3")),
					  5 => array("ePub", array("z2"), array("z2")),
					  6 => array("Blu-ray", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  7 => array("HD-DVD", array(), array("z0", "z1", "z3")),
					  8 => array("WMVHD", array(), array("z0", "z1", "z3")),
					  9 => array("x264", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  10 => array("DVD9", array("z0", "z1", "z3"), array("z0", "z1", "z3"))),
				  'b' =>
				Array(0	=> array("CAM", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  1 => array("(S)VCD", array(), array("z0", "z1", "z3")),
					  2 => array("Promo", array(), array("z0", "z1", "z3")),
					  3 => array("Retail", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  4 => array("TV", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  5 => array("", array(), array()),
					  6 => array("Satellite", array(), array("z0", "z1", "z3")),
					  7 => array("R5", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  8 => array("Telecine", array(), array("z0", "z1", "z3")),
					  9 => array("Telesync", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  10 => array("Scan", array("z2"), array("z2"))),
				  'c' =>
				Array(0 => array("No subtitles", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  1 => array("Dutch subtitles (external)", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  2 => array("Dutch subtitles (builtin)", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  3 => array("English subtitles (external)", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  4 => array("English subtitles (builtin)", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  5 => array("", array(), array()), 
					  6 => array("Dutch subtitles (available)", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  7 => array("English subtitles (available)", array("z0", "z1", "z3"), array("z0", "z1", "z3")),
					  8 => array("", array(), array()),
					  9 => array("", array(), array()),
					  10 => array("English audio/written", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  11 => array("Dutch audio/written", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  12 => array("German audio/written", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  13 => array("French audio/written", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  14 => array("Spanish audio/written", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  15 => array("Asian audio/written", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3"))),
				  'd' =>
				Array(0  => array("Action", array("z0", "z1"), array("z0", "z1")),
					  1  => array("Adventure", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  2  => array("Animation", array("z0", "z1"), array("z0", "z1")),
					  3  => array("Cabaret", array("z0", "z1"), array("z0", "z1")),
					  4  => array("Comedy", array("z0", "z1"), array("z0", "z1")),
					  5  => array("Crime", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  6  => array("Documentary", array("z0", "z1"), array("z0", "z1")),
					  7  => array("Drama", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  8  => array("Family", array("z0", "z1"), array("z0", "z1")),
					  9  => array("Fantasy", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  10  => array("Arthouse", array("z0", "z1"), array("z0", "z1")),
					  11  => array("Television", array("z0", "z1"), array("z0", "z1")),
					  12  => array("Horror", array("z0", "z1"), array("z0", "z1")),
					  13  => array("Music", array("z0", "z1"), array("z0", "z1")),
					  14  => array("Musical", array("z0", "z1"), array("z0", "z1")),
					  15  => array("Mystery", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  16  => array("Romance", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  17  => array("Science Fiction", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  18  => array("Sport", array("z0", "z1"), array("z0", "z1")),
					  19  => array("Short movie", array("z0", "z1"), array("z0", "z1")),
					  20  => array("Thriller", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  21  => array("War", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  22  => array("Western", array("z0", "z1"), array("z0", "z1")),
					  23  => array("Erotica (hetero)", array(), array("z3")),
					  24  => array("Erotica (gay male)", array(), array("z3")),
					  25  => array("Erotica (gay female)", array(), array("z3")),
					  26  => array("Erotica (bi)", array(), array("z3")), 
					  27  => array("", array(), array()),
					  28  => array("Asian", array("z0", "z1"), array("z0", "z1")),
					  29  => array("Anime", array("z0", "z1"), array("z0", "z1")),
					  30  => array("Cover", array("z2"), array("z2")),
					  31  => array("Comicbook", array("z2"), array("z2")),
					  32  => array("Cartoons", array("z2"), array("z2")),
					  33  => array("Youth", array("z2"), array("z2")),
					  34  => array("Business", array("z2"), array("z2")),
					  35  => array("Computer", array("z2"), array("z2")),
					  36  => array("Hobby", array("z2"), array("z2")),
					  37  => array("Cooking", array("z2"), array("z2")),
					  38  => array("Handwork", array("z2"), array("z2")),
					  39  => array("Craftwork", array("z2"), array("z2")),
					  40  => array("Health", array("z2"), array("z2")),
					  41  => array("History", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  42  => array("Psychology", array("z2"), array("z2")),
					  43  => array("Newspaper", array("z2"), array("z2")),
					  44  => array("Magazine", array("z2"), array("z2")),
					  45  => array("Science", array("z2"), array("z2")),
					  46  => array("Female", array("z2"), array("z2")),
					  47  => array("Religion", array("z2"), array("z2")),
					  48  => array("Roman", array("z2"), array("z2")),
					  49  => array("Biografy", array("z2"), array("z2")),
					  50  => array("Detective", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  51  => array("Animals", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  52  => array("Humor", array("z0", "z1", "z2"), array("z0", "z1", "z2")),
					  53  => array("Travel", array("z2"), array("z2")),
					  54  => array("True story", array("z0", "z1"), array("z0", "z1")),
					  55  => array("Non-fiction", array("z2"), array("z2")),
					  56  => array("Politics", array(), array()),
					  57  => array("Poetry", array("z2"), array("z2")),
					  58  => array("Fairy tale", array("z2"), array("z2")),
					  59  => array("Technical", array("z2"), array("z2")),
					  60  => array("Art", array("z2"), array("z2")),
					  72  => array("Bi", array("z3"), array("z3")),
					  73  => array("Lesbian", array("z3"), array("z3")),
					  74  => array("Homo", array("z3"), array("z3")),
					  75  => array("Hetero", array("z3"), array("z3")),
					  76  => array("Amature", array("z3"), array("z3")),
					  77  => array("Group", array("z3"), array("z3")),
					  78  => array("POV", array("z3"), array("z3")),
					  79  => array("Solo", array("z3"), array("z3")),
					  80  => array("Young", array("z3"), array("z3")),
					  81  => array("Soft", array("z3"), array("z3")),
					  82  => array("Fetish", array("z3"), array("z3")),
					  83  => array("Old", array("z3"), array("z3")),
					  84  => array("Fat", array("z3"), array("z3")),
					  85  => array("SM", array("z3"), array("z3")),
					  86  => array("Rough", array("z3"), array("z3")),
					  87  => array("Dark", array("z3"), array("z3")),
					  88  => array("Hentai", array("z3"), array("z3")),
					  89  => array("Outside", array("z3"), array("z3"))),
				  'z' =>
				Array(0	=> "Movie",
					  1 => "Series",
					  2 => "Book",
					  3 => "Erotica")
			),
			  1 => Array(
				  'a' =>
				Array(0	=> array("MP3", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  1 => array("WMA", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  2 => array("WAV", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  3 => array("OGG", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  4 => array("EAC", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  5 => array("DTS", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  6 => array("AAC", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  7 => array("APE", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  8 => array("FLAC", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3"))),
				  'b' =>
				Array(0 => array("CD", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  1 => array("Radio", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  2 => array("Compilation", array(), array("z0", "z1", "z2", "z3")),
					  3 => array("DVD", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  4 => array("Other", array(), array("z0", "z1", "z2", "z3")),
					  5 => array("Vinyl", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  6 => array("Stream", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3"))),
				  'c' =>
				Array(0 => array("Variable", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  1 => array("< 96kbit", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  2 => array("96kbit", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  3 => array("128kbit", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  4 => array("160kbit", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  5 => array("192kbit", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  6 => array("256kbit", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  7 => array("320kbit", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  8 => array("Lossless", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  9 => array("Other", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3"))),
				  'd' =>
				Array(0 => array("Blues", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  1 => array("Compilation", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  2 => array("Cabaret", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  3 => array("Dance", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  4 => array("Diverse", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  5 => array("Hardcore", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  6 => array("World", array(), array("z0", "z1", "z2", "z3")),
					  7 => array("Jazz", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  8 => array("Youth", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  9 => array("Classical", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  10 => array("Kleinkunst", array(), array("z0", "z1", "z2", "z3")),
					  11 => array("Dutch", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  12 => array("New Age", array(), array("z0", "z1", "z2", "z3")),
					  13 => array("Pop", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  14 => array("RnB", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  15 => array("Hiphop", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  16 => array("Reggae", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  17 => array("Religious", array(), array("z0", "z1", "z2", "z3")),
					  18 => array("Rock", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  19 => array("Soundtracks", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  20 => array("Other", array(), array("z0", "z1", "z2", "z3")),
					  21 => array("Hardstyle", array(), array("z0", "z1", "z2", "z3")),
					  22 => array("Asian", array(), array("z0", "z1", "z2", "z3")),
					  23 => array("Disco", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  24 => array("Classics", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  25 => array("Metal", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  26 => array("Country", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  27 => array("Dubstep", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  28 => array("Nederhop", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  29 => array("DnB", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  30 => array("Electro", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  31 => array("Folk", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  32 => array("Soul", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  33 => array("Trance", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  34 => array("Balkan", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  35 => array("Techno", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  36 => array("Ambient", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  37 => array("Latin", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3")),
					  38 => array("Live", array("z0", "z1", "z2", "z3"), array("z0", "z1", "z2", "z3"))),
				  'z' =>
				Array(0	=> "Album",
					  1 => "Liveset",
					  2 => "Podcast",
					  3 => "Audiobook")
			),
			  2 => Array(
				  'a' =>
				Array(0 => array("Windows", array("zz"), array("zz")),
					  1 => array("Macintosh", array("zz"), array("zz")),
					  2 => array("Linux", array("zz"), array("zz")),
					  3 => array("Playstation", array("zz"), array("zz")),
					  4 => array("Playstation 2", array("zz"), array("zz")),
					  5 => array("PSP", array("zz"), array("zz")),
					  6 => array("Xbox", array("zz"), array("zz")),
					  7 => array("Xbox 360", array("zz"), array("zz")),
					  8 => array("Gameboy Advance", array("zz"), array("zz")),
					  9 => array("Gamecube", array("zz"), array("zz")),
					  10 => array("Nintendo DS", array("zz"), array("zz")),
					  11 => array("Nintento Wii", array("zz"), array("zz")),
					  12 => array("Playstation 3", array("zz"), array("zz")),
					  13 => array("Windows Phone", array("zz"), array("zz")),
					  14 => array("iOS", array("zz"), array("zz")),
					  15 => array("Android", array("zz"), array("zz")),
					  16 => array("Nintendo 3DS", array("zz"), array("zz"))),
				  'b' =>
				Array(0 => array("ISO", array(), array("zz")),
					  1 => array("Rip", array("zz"), array("zz")),
					  2 => array("Retail", array("zz"), array("zz")),
					  3 => array("DLC", array("zz"), array("zz")),
					  4 => array("", array(), array()),
					  5 => array("Patch", array("zz"), array("zz")),
					  6 => array("Crack", array("zz"), array("zz"))),
				  'c' =>
				Array(0 => array("Action", array("zz"), array("zz")),
					  1 => array("Adventure", array("zz"), array("zz")),
					  2 => array("Strategy", array("zz"), array("zz")),
					  3 => array("Roleplaying", array("zz"), array("zz")),
					  4 => array("Simulation", array("zz"), array("zz")),
					  5 => array("Race", array("zz"), array("zz")),
					  6 => array("Flying", array("zz"), array("zz")),
					  7 => array("Shooter", array("zz"), array("zz")),
					  8 => array("Platform", array("zz"), array("zz")),
					  9 => array("Sport", array("zz"), array("zz")),
					  10 => array("Child/youth", array("zz"), array("zz")),
					  11 => array("Puzzle", array("zz"), array("zz")),
					  12 => array("Other", array(), array("zz")),
					  13 => array("Boardgame", array("zz"), array("zz")),
					  14 => array("Cards", array("zz"), array("zz")),
					  15 => array("Education", array("zz"), array("zz")),
					  16 => array("Music", array("zz"), array("zz")),
					  17 => array("Family", array("zz"), array("zz"))),
				  'z' =>
				Array('z' => "everything")
			),
			  3 => Array(
				   'a' =>
				Array(0 => array("Windows", array("zz"), array("zz")),
					  1 => array("Macintosh", array("zz"), array("zz")),
					  2 => array("Linux", array("zz"), array("zz")),
					  3 => array("OS/2", array("zz"), array("zz")),
					  4 => array("Windows Phone", array("zz"), array("zz")),
					  5 => array("Navigation systems", array("zz"), array("zz")),
					  6 => array("iOS", array("zz"), array("zz")),
					  7 => array("Android", array("zz"), array("zz"))),
				  'b' =>
				Array(0 => array("Audio", array("zz"), array("zz")),
					  1 => array("Video", array("zz"), array("zz")),
					  2 => array("Graphics", array("zz"), array("zz")),
					  3 => array("CD/DVD Tools", array(), array("zz")),
					  4 => array("Media players",  array(), array("zz")),
					  5 => array("Rippers &amp; Encoders", array(), array()),
					  6 => array("Plugins", array(), array("zz")),
					  7 => array("Database tools", array(), array("zz")),
					  8 => array("Email software", array(), array("zz")),
					  9 => array("Photo", array(), array("zz")),
					  10 => array("Screensavers", array(), array("zz")),
					  11 => array("Skin software", array(), array("zz")),
					  12 => array("Drivers", array(), array("zz")),
					  13 => array("Browsers", array(), array("zz")),
					  14 => array("Download managers", array(), array()),
					  15 => array("Download", array("zz"), array("zz")),
					  16 => array("Usenet software", array(), array("zz")),
					  17 => array("RSS Readers", array(), array("zz")),
					  18 => array("FTP software", array(), array("zz")),
					  19 => array("Firewalls", array(), array("zz")),
					  20 => array("Antivirus software", array(), array("zz")),
					  21 => array("Antispyware software", array(), array("zz")),
					  22 => array("Optimization software", array(), array("zz")),
					  23 => array("Security software", array("zz"), array("zz")),
					  24 => array("System software", array("zz"), array("zz")),
					  25 => array("Other", array(), array("zz")),
					  26 => array("Educational", array("zz"), array("zz")),
					  27 => array("Office", array("zz"), array("zz")),
					  28 => array("Internet", array("zz"), array("zz")),
					  29 => array("Communication", array("zz"), array("zz")),
					  30 => array("Development", array("zz"), array("zz")),
					  31 => array("Spotnet", array("zz"), array("zz"))),
				  'z' =>
				Array('z' => "everything")
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
	
	static function mapDepricatedGenreSubCategories($hcat, $subcatd, $subcatz)
	{
		# image
		if ($hcat == 0) {
			# erotica
			if ($subcatz == 'z3|') {
		        # hetero
 				$subcatd = self::replaceGenreSubCategory($subcatd, 'd23|', 'd75|');
				$subcatd = self::replaceGenreSubCategory($subcatd, 'd24|', 'd74|');
				$subcatd = self::replaceGenreSubCategory($subcatd, 'd25|', 'd73|');
				$subcatd = self::replaceGenreSubCategory($subcatd, 'd26|', 'd72|');
			}
		} # if
		
		return $subcatd;
	}

	# helper function for function mapDepricatedGenreSubCategories()
	private static function replaceGenreSubCategory($subcatd, $oldsubcat, $newsubcat)
	{
		if (stripos($subcatd, $oldsubcat) !== false) {
			# prevent new genre being listed twice
			# if the new genre already exists, we replace the old genre with nothing
			if (stripos($subcatd, $newsubcat) !== false) {
				$subcatd = str_replace($oldsubcat, '', $subcatd);
			}
			else {
				$subcatd = str_replace($oldsubcat, $newsubcat, $subcatd);
			}
		}
		
		return $subcatd;
	}
	
	public static function startTranslation() {
		/* 
		 * Make sure we only translate once
		 */
		if (self::$_namesTranslated) {
			return ;
		} # if
		self::$_namesTranslated = true;
		
		# Translate the head categories
		foreach(self::$_head_categories as $key => $value) {
			self::$_head_categories[$key] = _($value);
			
			# Translate the subcat descriptions
			foreach(self::$_subcat_descriptions[$key] as $subkey => $subvalue) {
				self::$_subcat_descriptions[$key][$subkey] = _($subvalue);
			} # foreach

			# Translate the shortcat descriptions
			foreach(self::$_shortcat[$key] as $subkey => $subvalue) {
				self::$_shortcat[$key][$subkey] = _($subvalue);
			} # foreach
			
			# and translate the actual categories
			foreach(self::$_categories[$key] as $subkey => $subvalue) {
				foreach(self::$_categories[$key][$subkey] as $subsubkey => $subsubvalue) {
					if (is_array($subsubvalue)) {
						self::$_categories[$key][$subkey][$subsubkey][0] = _($subsubvalue[0]);
					} else {
						self::$_categories[$key][$subkey][$subsubkey] = _($subsubvalue);
					} # else
				} # foreach
			} # foreach
		} # foreach
	} # startTranslation
} # SpotCategories
