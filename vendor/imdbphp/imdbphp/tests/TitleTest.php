<?php

require_once __DIR__ . "/helpers.php";

class TitleTest extends PHPUnit\Framework\TestCase
{

    /**
     * IMDb IDs for testing:
     * 0133093 = The Matrix (has everything)
     * 0087544 = Nausicaa (foreign, nonascii)
     * 0078788 = Apocalypse Now (Two cuts, multiple languages)
     * 0108052 = Schindler's List (multiple colours)
     * 0338187 = The Last New Yorker (see full synopsis...)
     * 2768262 = redirect to 2386868
     * 0416449 = 300 (some multi bracket credits)
     * 0103074 = Thelma & Louise (&amp; in title)
     * 0450385 = 1408 - recommends "Sinister" I (2012)
     * 3110958 = Now You See Me 2 -- Testing german language
     * 107290 = Jurassic Park (location with interesting characters)
     * 0120737 = The Lord of the Rings: The Fellowship of the Ring (historical mpaa)
     *
     * 0306414 = The Wire (TV / has everything)
     * 1286039 = Stargate Universe (multiple creators)
     * 1027544 = Roary the Racing Car (TV show, almost everything missing)
     * 303461  = Firefly (TV show, one season)
     * 988824  = Naruto (TV show, one massive season)
     *
     * 0579539 = A TV episode (train job, firefly)
     *
     * 0284717 = Crociati (tv movie, see full summary...)
     *
     * 1799527 = DOOM (2016) Video Game
     *
     * 0314979 = Battlestar Galactica (Tv Miniseries / no end date)
     *
     * 149937 = Bottom Live (Video)
     *
     * 7618100 = Untitled Star Wars Trilogy: Episode III ... has almost no information
     *
     * 2832384 = Jochem Myjer: Adéhadé (Only one actor)
     *
     */
    public function testConstruct_from_ini_constructed_config()
    {
        $config = new \Imdb\Config(dirname(__FILE__) . '/resources/test.ini');
        $imdb = new \Imdb\Title('0133093', $config);
        $this->assertEquals('test.local', $imdb->imdbsite);
        $this->assertEquals('/somefolder', $imdb->cachedir);
        $this->assertEquals(false, $imdb->storecache);
        $this->assertEquals(false, $imdb->usecache);
    }

    public function test_constructor_with_integer_imdbid_is_coerced_to_7_digit_string()
    {
        $imdb = new \Imdb\Title(133093);
        $this->assertEquals('0133093', $imdb->imdbid());
    }

    public function test_constructor_with_ttxxxxxxx_is_coerced_to_7_digit_string()
    {
        $imdb = new \Imdb\Title('tt0133093');
        $this->assertEquals('0133093', $imdb->imdbid());
    }

    public function test_constructor_with_url_is_coerced_to_7_digit_string()
    {
        $imdb = new \Imdb\Title('https://www.imdb.com/title/tt0133093/');
        $this->assertEquals('0133093', $imdb->imdbid());
    }

    public function test_constructor_with_8_digit_integer_imdbid_is_coerced_to_8_digit_string()
    {
        $imdb = new \Imdb\Title(10027990);
        $this->assertEquals('10027990', $imdb->imdbid());
    }

    public function test_constructor_with_ttxxxxxxxx_retains_the_8_digit_string()
    {
        $imdb = new \Imdb\Title('tt10027990');
        $this->assertEquals('10027990', $imdb->imdbid());
    }

    public function test_constructor_with_url_retains_the_8_digit_string()
    {
        $imdb = new \Imdb\Title('https://www.imdb.com/title/tt10027990/');
        $this->assertEquals('10027990', $imdb->imdbid());
    }

    public function test_constructor_with_custom_logger()
    {
        $logger = \Mockery::mock('\Psr\Log\LoggerInterface', function($mock) {
                $mock->shouldReceive('debug');
                $mock->shouldReceive('error');
            });
        $imdb = new \Imdb\Title('some rubbish', null, $logger);
        \Mockery::close(); // Assert that the mocked object was called as expected
        $this->assertTrue(true);
    }

    public function test_constructor_with_custom_cache()
    {
        $cache = \Mockery::mock('\Psr\SimpleCache\CacheInterface', function($mock) {
                $mock->shouldReceive('get')->andReturn('test');
                $mock->shouldReceive('purge');
            });
        $imdb = new \Imdb\Title('', null, null, $cache);
        $imdb->title();
        \Mockery::close();
        $this->assertTrue(true);
    }

    // @TODO tests for other types
    public function testMovietype_on_movie()
    {
        $imdb = $this->getImdb();
        $this->assertEquals('Movie', $imdb->movietype());
    }

    public function testMovietype_on_tv()
    {
        $imdb = $this->getImdb("0306414");
        $this->assertEquals('TV Series', $imdb->movietype());
    }

    public function testMovieType_on_tv_no_year()
    {
        $imdb = $this->getImdb("9916210");
        $this->assertEquals('TV Series', $imdb->movietype());
        $this->assertEquals(0, $imdb->year());
    }

    public function testMovietype_on_tvMovie()
    {
        $imdb = $this->getImdb("284717");
        $this->assertEquals('TV Movie', $imdb->movietype());
    }

    public function testMovietype_on_tvSpecial()
    {
        $imdb = $this->getImdb("5258960");
        $this->assertEquals('TV Special', $imdb->movietype());
    }

    public function testMovietype_on_tvEpisode()
    {
        $imdb = $this->getImdb("0579539");
        $this->assertEquals('TV Episode', $imdb->movietype());
    }

    public function testMovietype_on_TVMiniseries()
    {
        $imdb = $this->getImdb("0314979");
        $this->assertEquals($imdb->movietype(), \Imdb\Title::TV_MINI_SERIES);
    }

    public function testMovietype_on_videoGame()
    {
        $imdb = $this->getImdb("1799527");
        $this->assertEquals('Video Game', $imdb->movietype());
    }

    public function testMovieType_on_video()
    {
        $imdb = $this->getImdb(149937);
        $this->assertEquals('Video', $imdb->movietype());
    }

    public function testTitle()
    {
        $imdb = $this->getImdb();
        $this->assertEquals('The Matrix', $imdb->title());
    }

    public function testTitle_non_english_title_uses_english_title()
    {
        $imdb = $this->getImdb('0087544');
        $this->assertEquals('Nausicaä of the Valley of the Wind', $imdb->title());
    }

    public function testTitle_removes_html_entities()
    {
        $imdb = $this->getImdb('0103074');
        $this->assertEquals('Thelma & Louise', $imdb->title());
    }

    public function testTitle_removes_escaped_quotes()
    {
        $imdb = $this->getImdb('0108052');
        $this->assertEquals('Schindler\'s List', $imdb->title());
    }

    public function testTitle_different_language()
    {
        $config = new \Imdb\Config();
        $config->language = 'de-de';
        $config->cachedir = realpath(dirname(__FILE__) . '/cache') . '/';
        $title = new \Imdb\Title(3110958, $config);
        $this->assertEquals('Die Unfassbaren 2', $title->title());
    }

    public function testTitleEpisodeTitle()
    {
        $imdb = $this->getImdb('0579539');
        $this->assertEquals('"Firefly" The Train Job', $imdb->title());
    }

    //@TODO tests for titles with non ascii characters. Currently they're
    // html entities, would be nice to decode them

    public function testOrig_title_with_no_original()
    {
        $imdb = $this->getImdb();
        $this->assertEquals(null, $imdb->orig_title());
    }

    public function testOrig_title_with_original()
    {
        $imdb = $this->getImdb('0087544');
        $this->assertEquals('Kaze no tani no Naushika', $imdb->orig_title());
    }

    public function testYear_for_a_film()
    {
        $imdb = $this->getImdb();
        $this->assertEquals(1999, $imdb->year());
    }

    public function testYear_for_a_tv_show()
    {
        $imdb = $this->getImdb("0306414");
        $this->assertEquals(2002, $imdb->year());
    }

    public function testEndyear_for_a_film()
    {
        // Film has no range, so endyear is the same as year
        $imdb = $this->getImdb();
        $this->assertEquals(1999, $imdb->endyear());
    }

    public function testEndyear_for_a_tv_show()
    {
        $imdb = $this->getImdb("0306414");
        $this->assertEquals(2008, $imdb->endyear());
    }

    public function testYearspan_for_a_tv_show_that_hasnt_ended()
    {
        $imdb = $this->getImdb("4903514");
        $this->assertEquals(array('start' => 2015, 'end' => 0), $imdb->yearspan());
    }

    public function testYearspan()
    {
        $imdb = $this->getImdb("0306414");
        $this->assertEquals(array('start' => 2002, 'end' => 2008), $imdb->yearspan());
    }

    public function testMovieTypes()
    {
        $imdb = $this->getImdb("0306414");
        $movieTypes = $imdb->movieTypes();
        $this->assertEquals('TV Series 2002–2008', $movieTypes[0]);
    }

    public function testRuntime()
    {
        $imdb = $this->getImdb();
        $this->assertEquals(136, $imdb->runtime());
    }

    public function testRuntime_episode()
    {
        $imdb = $this->getImdb('0579539');
        $this->assertEquals(42, $imdb->runtime());
    }

    public function testRuntime_primary_where_multiple_exist()
    {
        $imdb = $this->getImdb('0087544');
        $this->assertEquals(117, $imdb->runtime());
    }

    // one plain unannotated runtime "136 min"
    public function testRuntimes_one_runtime()
    {
        $imdb = $this->getImdb();
        $runtimes = $imdb->runtimes();
        $this->assertEquals(136, $runtimes[0]['time']);
    }

    public function testRuntimes_tv_show()
    {
        $imdb = $this->getImdb('0306414');
        $runtimes = $imdb->runtimes();
        $this->assertEquals(59, $runtimes[0]['time']);
    }

    // Nausicaa's runtimes are "117 min | 95 min (1985) (edited)"
    public function testRuntimes_two_runtimes_multiple_annotations()
    {
        $imdb = $this->getImdb('0087544');
        $runtimes = $imdb->runtimes();
        $this->assertEquals(117, $runtimes[0]['time']);
        $this->assertEquals(95, $runtimes[1]['time']);
        $this->assertEquals('edited', $runtimes[1]['annotations'][0]);
        $this->assertEquals(1985, $runtimes[1]['annotations'][1]);
        $this->assertEquals('USA', $runtimes[1]['annotations'][2]);
    }

    // Apocalypse now "147 min | 196 min (Redux)"
    public function testRuntimes_two_runtimes_one_annotation()
    {
        $imdb = $this->getImdb('0078788');
        $runtimes = $imdb->runtimes();
        $this->assertEquals(147, $runtimes[0]['time']);
        $this->assertEquals(196, $runtimes[1]['time']);
        $this->assertEquals('Redux', $runtimes[1]['annotations'][0]);
    }

    public function testAspect_ratio()
    {
        $imdb = $this->getImdb();
        $this->assertEquals('2.39 : 1', $imdb->aspect_ratio());
    }

    public function testAspect_ratio_missing()
    {
        $imdb = $this->getImdb(1027544);
        $this->assertEquals('', $imdb->aspect_ratio());
    }

    public function testRating()
    {
        $imdb = $this->getImdb();
        $this->assertEquals('8.7', $imdb->rating());
    }

    public function testRating_no_rating()
    {
        $imdb = $this->getImdb('tt7618100');
        $this->assertEquals('', $imdb->rating());
    }

    public function testVotes()
    {
        $imdb = $this->getImdb();
        $votes = $imdb->votes();
        $this->assertGreaterThan(1500000, $votes);
        $this->assertLessThan(2000000, $votes);
    }

//    public function testVotes_no_votes()
//    {
//        //@TODO
//    }

    public function testMetacriticRating()
    {
        $imdb = $this->getImdb();
        $this->assertEquals(73, $imdb->metacriticRating());
    }

    public function testMetacriticRating_returns_null_when_no_rating()
    {
        $imdb = $this->getImdb('7618100');
        $this->assertEquals(null, $imdb->metacriticRating());
    }

    public function testMetacriticNumReviews()
    {
        $imdb = $this->getImdb();
        $this->assertEquals(null, $imdb->metacriticNumReviews());
    }

    public function testComment()
    {
        $imdb = $this->getImdb();
        $this->assertNotEmpty($imdb->comment());
    }

    // Taking different comments every time. Need to validate what it should look like.
//    public function testComment_split()
//    {
//        //@TODO
//    }

    public function testMovie_recommendations()
    {
        $imdb = $this->getImdb();
        $recommendations = $imdb->movie_recommendations();
        $this->assertIsArray($recommendations);
        $this->assertCount(12, $recommendations);

        $matches = 0;
        foreach ($recommendations as $recommendation) {
            if ($recommendation['title'] == 'The Matrix Reloaded') {
                $this->assertTrue($recommendation['title'] == 'The Matrix Reloaded');
                $this->assertTrue(floatval($recommendation['rating']) > 7.0);
                $this->assertTrue($recommendation['img'] != "");
                ++$matches;
            } else {
                $this->assertIsArray($recommendation);
                $this->assertTrue(strlen($recommendation['title']) > 0); // title
                $this->assertTrue(strlen($recommendation['imdbid']) === 7 || strlen($recommendation['imdbid']) === 8); // imdb number
                $this->assertTrue($recommendation['rating'] != -1); // rating
                $this->assertTrue($recommendation['img'] != ""); // img url
            }
        }
        $this->assertEquals(1, $matches);
    }

    public function testMovie_recommendations_tv_show()
    {
        $imdb = $this->getImdb(306414);
        $recommendations = $imdb->movie_recommendations();
        $this->assertIsArray($recommendations);
        $this->assertCount(12, $recommendations);

        $titlesWithEndYear = 0;
        foreach ($recommendations as $recommendation) {
            $this->assertIsArray($recommendation);
            $this->assertTrue(strlen($recommendation['title']) > 0); // title
            $this->assertTrue(strlen($recommendation['imdbid']) === 7 || strlen($recommendation['imdbid']) === 8); // imdb number
            $this->assertTrue($recommendation['rating'] != -1); // rating
            $this->assertTrue($recommendation['img'] != ""); // img url
        }
    }

    public function testKeywords()
    {
        $imdb = $this->getImdb("0306414");
        $keywords = $imdb->keywords();
        $this->assertTrue(in_array('corruption', $keywords));
        $this->assertTrue(in_array('drug trafficking', $keywords));
        $this->assertTrue(in_array('urban decay', $keywords));
    }

    public function testLanguage()
    {
        $imdb = $this->getImdb("0306414");
        $this->assertEquals('English', $imdb->language());
    }

    public function testLanguages_onelanguage()
    {
        $imdb = $this->getImdb();
        $this->assertEquals(array('English'), $imdb->languages());
    }

    public function testLanguages_multiplelanguage()
    {
        $imdb = $this->getImdb('0078788');
        $languages = $imdb->languages();
        $this->assertTrue(in_array('English', $languages));
        $this->assertTrue(in_array('French', $languages));
        $this->assertTrue(in_array('Vietnamese', $languages));
    }

//    public function testLanguages_nolanguage()
//    {
//        //@TODO
//    }

    public function testLanguages_detailed()
    {
        $imdb = $this->getImdb('0306414');
        $this->assertEquals(array(
            array(
                'name' => 'English',
                'code' => 'en',
                'comment' => ''
            ),
            array(
                'name' => 'Greek',
                'code' => 'el',
                'comment' => ''
            ),
            array(
                'name' => 'Mandarin',
                'code' => 'cmn',
                'comment' => ''
            ),
            array(
                'name' => 'Spanish',
                'code' => 'es',
                'comment' => ''
            )
            ), $imdb->languages_detailed());
    }

//    public function testLanguages_detailed_comment()
//    {
//        //@TODO
//    }

//    public function testGenre()
//    {
//        //@TODO .. this is a pretty terrible function that doesn't return anything useful
//        // Writing a test would be meaningless
//    }

    // @TODO this function seems to have a fallback, although I'm not sure what to
    // Primary match is to the genre listing just under the title, which this tests
    public function testGenres_multiple()
    {
        $imdb = $this->getImdb('0087544');
        $genres = $imdb->genres();
        $this->assertTrue(in_array('Animation', $genres));
        $this->assertTrue(in_array('Sci-Fi', $genres));
        $this->assertTrue(count($genres) == 3);
    }

//    public function testGenres_none()
//    {
//        //@TODO
//    }

    public function testColors_one_color()
    {
        $imdb = $this->getImdb();
        $colors = $imdb->colors();

        $this->assertIsArray($colors);
        $this->assertCount(1, $colors);
        $this->assertEquals('Color', $colors[0]);
    }

    public function testColors_two_colors()
    {
        $imdb = $this->getImdb('0108052');
        $colors = $imdb->colors();

        $this->assertIsArray($colors);
        $this->assertCount(2, $colors);
        $this->assertEquals('Black and White', $colors[0]);
        $this->assertEquals('Color', $colors[1]);
    }

    public function testCreator_no_creators_because_its_a_film()
    {
        $imdb = $this->getImdb('0133093');
        $creators = $imdb->creator();

        $this->assertIsArray($creators);
        $this->assertEquals(0, count($creators));
    }

    public function testCreator_one_creator()
    {
        $imdb = $this->getImdb('0306414');
        $creators = $imdb->creator();

        $this->assertIsArray($creators);
        $this->assertEquals('David Simon', $creators[0]['name']);
        $this->assertEquals('0800108', $creators[0]['imdb']);
    }

    public function testCreator_two_creators()
    {
        $imdb = $this->getImdb('1286039');
        $creators = $imdb->creator();

        $this->assertIsArray($creators);
        $this->assertEquals('Robert C. Cooper', $creators[0]['name']);
        $this->assertEquals('0178338', $creators[0]['imdb']);
        $this->assertEquals('Brad Wright', $creators[1]['name']);
        $this->assertEquals('0942249', $creators[1]['imdb']);
    }

    public function testTagline()
    {
        $imdb = $this->getImdb("0306414");
        $this->assertTrue(in_array($imdb->tagline(), $imdb->taglines()));
    }

    public function testSeasons()
    {
        $imdb = $this->getImdb("0306414");
        $this->assertEquals(5, $imdb->seasons());
    }

    public function testSeasons_single_season_show()
    {
        $imdb = $this->getImdb(303461);
        $this->assertEquals(1, $imdb->seasons());
    }

    public function testIs_serial()
    {
        $imdb = $this->getImdb("0306414");
        $this->assertTrue($imdb->is_serial());
    }

    public function test_if_not_Is_serial()
    {
        $imdb = $this->getImdb();
        $this->assertFalse($imdb->is_serial());
    }

    public function testEpisodeTitle()
    {
        $imdb = $this->getImdb('0579539');
        $this->assertEquals('The Train Job', $imdb->episodeTitle());
    }

    public function testEpisodeTitle_film()
    {
        $imdb = $this->getImdb();
        $this->assertEquals('', $imdb->episodeTitle());
    }

    public function testEpisodeSeason()
    {
        $imdb = $this->getImdb('0579539');
        $this->assertEquals(1, $imdb->episodeSeason());
    }

    public function testEpisodeEpisode()
    {
        $imdb = $this->getImdb('0579539');
        $this->assertEquals(1, $imdb->episodeEpisode());
    }

    public function testEpisodeAirDate()
    {
        $imdb = $this->getImdb('0579539');
        $this->assertEquals('2002-09-20', $imdb->episodeAirDate());
    }

    public function testGet_episode_details_does_nothing_for_a_film()
    {
        $imdb = $this->getImdb();
        $episodeDetails = $imdb->get_episode_details();
        $this->assertIsArray($episodeDetails);
        $this->assertCount(0, $episodeDetails);
    }

    public function testGet_episode_details()
    {
        $imdb = $this->getImdb('0579539');
        $episodeDetails = $imdb->get_episode_details();
        $this->assertEquals(array(
            'imdbid' => '0303461',
            'seriestitle' => 'Firefly',
            'episodetitle' => 'The Train Job',
            'season' => 1,
            'episode' => 1,
            'airdate' => '2002-09-20',
            ), $episodeDetails);
    }

    // Finds outline in the itemprop="description" section nexto the poster
    public function testPlotoutline()
    {
        $imdb = $this->getImdb();
        $this->assertEquals('When a beautiful stranger leads computer hacker Neo to a forbidding underworld, he discovers the shocking truth--the life he knows is the elaborate deception of an evil cyber-intelligence.', $imdb->plotoutline());
    }

    public function testPlotoutline_strip_see_full_summary()
    {
        $imdb = $this->getImdb('0284717');
        $outline = $imdb->plotoutline();
        $this->assertSame(0, strpos($outline, 'Towards the end of the'));
        $this->assertFalse(stripos($outline, 'full summary'));
    }

    public function testPlotoutline_strip_see_full_synopsis()
    {
        $imdb = $this->getImdb('0338187');
        $outline = $imdb->plotoutline();
        $this->assertSame(0, strpos($outline, "Lenny (Chianese) is a small-time investor who's always managed by rolling the dice on Wall Street, but he just can't keep up with the times."));
        $this->assertFalse(stripos($outline, 'See full synopsis'));
    }

    public function testPlotoutline_nooutline()
    {
        $imdb = $this->getImdb('0133096');
        $outline = $imdb->plotoutline();
        $this->assertEquals('', $outline);
    }

    public function testStoryline()
    {
        $imdb = $this->getImdb("0306414");
        $this->assertSame(0, strpos($imdb->storyline(), "The streets of Baltimore as a microcosm of the US's war on drugs"));
    }

    public function testPhoto_returns_false_if_no_poster()
    {
        $imdb = $this->getImdb('7618100');
        $this->assertFalse($imdb->photo(false));
    }

    public function testPhoto_thumb_returns_false_if_no_poster()
    {
        $imdb = $this->getImdb('7618100');
        $this->assertFalse($imdb->photo(true));
    }

    public function testPhoto()
    {
        $imdb = $this->getImdb();
        // This is a little brittle. What if the image changes? ...
        $this->assertEquals('https://m.media-amazon.com/images/M/MV5BNzQzOTk3OTAtNDQ0Zi00ZTVkLWI0MTEtMDllZjNkYzNjNTc4L2ltYWdlXkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_.jpg', $imdb->photo(false));
    }

    public function testPhoto_thumb()
    {
        $imdb = $this->getImdb();
        // This is a little brittle. What if the image changes? what if the size of the poster changes? ...
        $this->assertEquals('https://m.media-amazon.com/images/M/MV5BNzQzOTk3OTAtNDQ0Zi00ZTVkLWI0MTEtMDllZjNkYzNjNTc4L2ltYWdlXkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_QL75_UX190_CR0,2,190,281_.jpg', $imdb->photo(true));
    }

    public function testSavephoto()
    {
        $imdb = $this->getImdb();
        @unlink(dirname(__FILE__) . '/cache/poster.jpg');
        $result = $imdb->savephoto(dirname(__FILE__) . '/cache/poster.jpg');
        $this->assertTrue($result);
        $this->assertFileExists(dirname(__FILE__) . '/cache/poster.jpg');
        @unlink(dirname(__FILE__) . '/cache/poster.jpg');
    }

//    public function testPhoto_localurl()
//    {
//        //@TODO
//    }
//
//    public function testMainPictures()
//    {
//        //@TODO
//    }

    public function testCountry()
    {
        $imdb = $this->getImdb();
        $this->assertEquals(array('United States', 'Australia'), $imdb->country());
    }

//    public function testCountry_nocountries()
//    {
//        //@TODO
//    }

    public function testAlsoknow()
    {
        $imdb = $this->getImdb("0087544");
        $akas = $imdb->alsoknow();

        $matches = 0;
        foreach ($akas as $aka) {
            if ($aka['title'] == 'Kaze no tani no Naushika') {
                // No country
                $this->assertEquals('Kaze no tani no Naushika', $aka['title']);
                $this->assertThat($aka['comments'][0],
                    $this->logicalOr(
                        $this->equalTo('original title'),
                        $this->equalTo('French title')
                    ));
                ++$matches;
            } elseif ($aka['title'] == 'Naushika iz Doline vjetrova') {
                // Country, no comment
                $this->assertEquals('Naushika iz Doline vjetrova', $aka['title']);
                $this->assertEquals('Croatia', $aka['country']);
                $this->assertEmpty($aka['comments']);
                ++$matches;
            } elseif ($aka['title'] == 'Наусика от Долината на вятъра') {
                // Country with comment
                $this->assertEquals('Наусика от Долината на вятъра', $aka['title']);
                $this->assertEquals('Bulgaria', $aka['country']);
                $this->assertEquals('Bulgarian title', $aka['comments'][0]);
                ++$matches;
            } elseif ($aka['title'] == 'Nausicaä - Aus dem Tal der Winde' && count($aka['comments']) >= 2) {
                // Country with two comments
                $this->assertEquals('Nausicaä - Aus dem Tal der Winde', $aka['title']);
                $this->assertEquals('Switzerland', $aka['country']);
                $this->assertEquals('German title', $aka['comments'][0]);
                $this->assertEquals('DVD title', $aka['comments'][1]);
                ++$matches;
            }
        }
        $this->assertEquals(5, $matches);
    }

//    public function testAlsoknow_returns_no_results_when_film_has_no_akas()
//    {
//        //@TODO
//    }

    public function testSound_multiple_types()
    {
        $imdb = $this->getImdb();
        $sound = $imdb->sound();
        $this->assertIsArray($sound);
        $this->assertCount(3, $sound);
        $this->assertEquals('Dolby Digital', $sound[0]);
        $this->assertEquals('SDDS', $sound[1]);
        $this->assertEquals('Dolby Atmos', $sound[2]);
    }

    public function testSound_one_type()
    {
        $imdb = $this->getImdb('0087544');
        $sound = $imdb->sound();
        $this->assertIsArray($sound);
        $this->assertCount(1, $sound);
        $this->assertEquals('Mono', $sound[0]);
    }

    public function testSound_none()
    {
        $imdb = $this->getImdb('1027544');
        $sound = $imdb->sound();
        $this->assertIsArray($sound);
        $this->assertCount(0, $sound);
    }

    public function testMpaa()
    {
        $imdb = $this->getImdb('0120737');
        $mpaa = $imdb->mpaa();
        $this->assertArrayHasKey('Denmark', $mpaa);
        $this->assertEquals('15', $mpaa['Denmark']);
    }

    public function testMpaa_ratings()
    {
        $imdb = $this->getImdb('0120737');
        $mpaa = $imdb->mpaa(true);
        $this->assertArrayHasKey('Denmark', $mpaa);
        $this->assertEquals(['11', '15'], $mpaa['Denmark']);
    }

    public function testMpaa_hist()
    {
        $imdb = $this->getImdb('0120737');
        $mpaa = $imdb->mpaa_hist();
        $this->assertArrayHasKey('United States', $mpaa);
        $this->assertContains('PG-13', $mpaa['United States']);
    }

    public function testMpaa_reason()
    {
        $imdb = $this->getImdb('0120737');
        $this->assertEquals('Rated PG-13 for epic battle sequences and some scary images', $imdb->mpaa_reason());
    }

//    public function testProdNotes()
//    {
//        //@TODO
//    }

    public function testTop250()
    {
        $imdb = $this->getImdb();
        $top250 = $imdb->top250();
        $this->assertIsInt($top250);
        $this->assertGreaterThan(10, $top250);
        $this->assertLessThan(25, $top250);
    }

    public function testTop250_tv()
    {
        $imdb = $this->getImdb(306414);
        $top250 = $imdb->top250();
        $this->assertIsInt($top250);
        $this->assertGreaterThan(1, $top250);
        $this->assertLessThan(20, $top250);
    }

    public function testTop250_returns_0_when_not_in_top_250()
    {
        $imdb = $this->getImdb('0103074');
        $top250 = $imdb->top250();
        $this->assertIsInt($top250);
        $this->assertEquals(0, $top250);
    }

    public function testPlot()
    {
        $imdb = $this->getImdb('2039393');
        $plot = $imdb->plot();
        $this->assertCount(3, $plot);
        $this->assertStringStartsWith("Literature professor and gambler Jim Bennett's debt causes him to borrow money from his mother and a loan shark.", $plot[0]);
        $this->assertStringStartsWith("Jim Bennett is a risk taker. Both an English professor and a high-stakes gambler, Bennett bets it all when he", $plot[1]);
    }

    public function testPlot_split()
    {
        $imdb = $this->getImdb('2039393');
        $plot = $imdb->plot_split();
        $this->assertEquals(
            array(
            array(
                'plot' => 0,
                'author' => array(
                    'name' => '',
                    'url' => ''
                )
            ),
            array(
                'plot' => 0,
                'author' => array(
                    'name' => 'Paramount Pictures',
                    'url' => 'https://www.imdb.com/search/title?plot_author=Paramount Pictures&view=simple&sort=alpha&ref_=ttpl_pl_1'
                )
            )
            ), array(
            array(
                'plot' => strpos($plot[0]['plot'], "Literature professor and gambler Jim Bennett's debt causes him to borrow money from his mother and a loan shark."),
                'author' => array(
                    'name' => $plot[0]['author']['name'],
                    'url' => $plot[0]['author']['url']
                )
            ),
            array(
                'plot' => strpos($plot[1]['plot'], "Jim Bennett is a risk taker. Both an English professor and a high-stakes gambler, Bennett bets it all when he"),
                'author' => array(
                    'name' => $plot[1]['author']['name'],
                    'url' => $plot[1]['author']['url']
                )
            )
        ));
    }

    public function testSynopsis()
    {
        $imdb = $this->getImdb('2039393');
        $synopsis = $imdb->synopsis();
        $this->assertSame(0, strpos($synopsis, "After his grandpa dies, Jim Bennett goes straight to a Mr. Lees illegal casino. He plays a few hands of blackjack,"));
    }

    public function testTaglines()
    {
        $imdb = $this->getImdb("0306414");
        $taglines = $imdb->taglines();
        $this->assertTrue(in_array('A new case begins... (second season)', $taglines));
        $this->assertTrue(in_array('Rules change. The game remains the same. (third season)', $taglines));
        $this->assertTrue(in_array('No corner left behind. (fourth season)', $taglines));
        $this->assertTrue(in_array('Listen carefully (first season)', $taglines));
        $this->assertTrue(in_array('All in the game. (fifth season)', $taglines));
        $this->assertTrue(in_array('Read between the lines (season five)', $taglines));
    }

    public function testDirector_single()
    {
        $imdb = $this->getImdb('0087544');
        $this->assertEquals(array(
            array('imdb' => '0594503',
                'name' => 'Hayao Miyazaki',
                'role' => null),
            ), $imdb->director());
    }

    public function testDirector_multiple()
    {
        $imdb = $this->getImdb();
        // Is the 'role' part correct?
        $this->assertEquals(array(
            array(
                'imdb' => '0905154',
                'name' => 'Lana Wachowski',
                'role' => '(as The Wachowski Brothers)'
            ),
            array(
                'imdb' => '0905152',
                'name' => 'Lilly Wachowski',
                'role' => '(as The Wachowski Brothers)'
            )
            ), $imdb->director());
    }

//    public function testDirector()
//    {
//        //@TODO this needs more tests for different scenarios
//    }

    public function testCast_film_with_role_link()
    {
        $imdb = $this->getImdb();
        $cast = $imdb->cast();
        $firstCast = $cast[0];
        $this->assertEquals('0000206', $firstCast['imdb']);
        $this->assertEquals('Keanu Reeves', $firstCast['name']);
        $this->assertEquals('Neo', $firstCast['role']);
        $this->assertTrue($firstCast['credited']);
        $this->assertCount(0, $firstCast['role_other']);
    }

    public function testCast_short_nonascii()
    {
        $imdb = $this->getImdb('0087544');
        $cast = $imdb->cast(true);

        $sumi = $cast[0];
        $this->assertEquals('0793585', $sumi['imdb']);
        $this->assertEquals('Sumi Shimamoto', $sumi['name']);
        $this->assertEquals('Nausicaä', $sumi['role']);
        $this->assertTrue($sumi['credited']);
        $this->assertCount(0, $sumi['role_other']);

        $hisako = $cast[2];
        $this->assertEquals('0477449', $hisako['imdb']);
        $this->assertEquals('Hisako Kyôda', $hisako['name']);
        $this->assertEquals('Oh-Baba', $hisako['role']);
        $this->assertTrue($hisako['credited']);
        $this->assertCount(0, $hisako['role_other']);
    }

    public function testCast_short_cast_list_film_with_role_link()
    {
        $imdb = $this->getImdb();
        $cast = $imdb->cast(true);
        $firstCast = $cast[0];
        $this->assertEquals('0000206', $firstCast['imdb']);
        $this->assertEquals('Keanu Reeves', $firstCast['name']);
        $this->assertEquals('Neo', $firstCast['role']);
        $this->assertTrue($firstCast['credited']);
        $this->assertCount(0, $firstCast['role_other']);
    }

    public function testCast_film_with_role_link_and_as_name()
    {
        $imdb = $this->getImdb();
        $cast = $imdb->cast();
        $castMember = $cast[14];
        $this->assertEquals('0336802', $castMember['imdb']);
        $this->assertEquals('Marc Aden Gray', $castMember['name']);
        $this->assertEquals('Marc Gray', $castMember['name_alias']);
        $this->assertEquals('Choi', $castMember['role']);
        $this->assertTrue($castMember['credited']);
        $this->assertCount(0, $castMember['role_other']);
    }

    public function testCast_film_no_role_link()
    {
        $imdb = $this->getImdb();
        $cast = $imdb->cast();
        $castMember = $cast[16];
        $this->assertEquals('0330139', $castMember['imdb']);
        $this->assertEquals('Deni Gordon', $castMember['name']);
        $this->assertEquals('Priestess', $castMember['role']);
        $this->assertTrue($castMember['credited']);
        $this->assertCount(0, $castMember['role_other']);
    }

    public function testCast_film_no_role_link_and_as_name()
    {
        $imdb = $this->getImdb();
        $cast = $imdb->cast();
        $castMember = $cast[18];
        $this->assertEquals('0936860', $castMember['imdb']);
        $this->assertEquals('Eleanor Witt', $castMember['name']);
        $this->assertEquals('Elenor Witt', $castMember['name_alias']);
        $this->assertEquals('Potential', $castMember['role']);
        $this->assertTrue($castMember['credited']);
        $this->assertCount(0, $castMember['role_other']);
    }

    public function testCast_film_uncredited()
    {
        $imdb = $this->getImdb();
        $cast = $imdb->cast();
        $castMember = $cast[36];
        $this->assertEquals('1248119', $castMember['imdb']);
        $this->assertEquals('Mike Duncan', $castMember['name']);
        $this->assertEquals(null, $castMember['name_alias']);
        $this->assertEquals('Twin', $castMember['role']);
        $this->assertFalse($castMember['credited']);
    }

    public function testCast_film_as_name_and_brackets_in_role_name()
    {
        $imdb = $this->getImdb('0416449');
        $cast = $imdb->cast();
        $castMember = $cast[19];
        $this->assertEquals('2542697', $castMember['imdb']);
        $this->assertEquals('Sebastian St. Germain', $castMember['name']);
        $this->assertEquals('Sébastian St Germain', $castMember['name_alias']);
        $this->assertEquals('Fighting Boy (12 years old)', $castMember['role']);
        $this->assertTrue($castMember['credited']);
        $this->assertIsArray($castMember['role_other']);
        $this->assertCount(0, $castMember['role_other']);
    }

    public function testCast_film_multiple_roles()
    {
        $imdb = $this->getImdb('2015381');
        $cast = $imdb->cast();
        $castMember = $cast[13];
        $this->assertEquals('0348231', $castMember['imdb']);
        $this->assertEquals('Sean Gunn', $castMember['name']);
        $this->assertEquals(null, $castMember['name_alias']);
        $this->assertEquals('Kraglin / On Set Rocket', $castMember['role']);
        $this->assertTrue($castMember['credited']);
        $this->assertIsArray($castMember['role_other']);
        $this->assertCount(0, $castMember['role_other']);
    }

    public function testCast_film_uncredited_and_other()
    {
        $imdb = $this->getImdb('2015381');
        $cast = $imdb->cast();
        $castMember = array_find_item($cast, 'imdb', '0001293');
        $this->assertEquals('0001293', $castMember['imdb']);
        $this->assertEquals('Seth Green', $castMember['name']);
        $this->assertEquals(null, $castMember['name_alias']);
        $this->assertEquals('Howard the Duck', $castMember['role']);
        $this->assertFalse($castMember['credited']);
        $this->assertIsArray($castMember['role_other']);
        $this->assertCount(1, $castMember['role_other']);
        $this->assertEquals('voice', $castMember['role_other'][0]);
    }

    public function testCast_tv_multi_episode_multi_year()
    {
        $imdb = $this->getImdb('0306414');
        $cast = $imdb->cast();
        $firstCast = array_find_item($cast, 'imdb', '0922035');

        $this->assertEquals('0922035', $firstCast['imdb']);
        $this->assertEquals('Dominic West', $firstCast['name']);
        $this->assertEquals("Detective James 'Jimmy' McNulty", $firstCast['role']);
        $this->assertEquals(60, $firstCast['role_episodes']);
        $this->assertEquals(2002, $firstCast['role_start_year']);
        $this->assertEquals(2008, $firstCast['role_end_year']);
        $this->assertIsArray($firstCast['role_other']);
        $this->assertCount(0, $firstCast['role_other']);
        $this->assertEquals('https://m.media-amazon.com/images/M/MV5BMjM1MDU1Mzg3N15BMl5BanBnXkFtZTgwNTcwNzcyMzI@._V1_UY44_CR19,0,32,44_AL_.jpg', $firstCast['thumb']);
        $this->assertEquals('https://m.media-amazon.com/images/M/MV5BMjM1MDU1Mzg3N15BMl5BanBnXkFtZTgwNTcwNzcyMzI@.jpg', $firstCast['photo']);
    }

    public function testCast_tv_multi_episode_one_year()
    {
        $imdb = $this->getImdb('0306414');
        $cast = $imdb->cast();
        $castMember = array_find_item($cast, 'imdb', '1370480');

        $this->assertEquals('1370480', $castMember['imdb']);
        $this->assertEquals('Dan De Luca', $castMember['name']);
        $this->assertEquals("David Parenti", $castMember['role']);
        $this->assertEquals(10, $castMember['role_episodes']);
        $this->assertEquals(2006, $castMember['role_start_year']);
        $this->assertEquals(2006, $castMember['role_end_year']);
        $this->assertIsArray($castMember['role_other']);
        $this->assertCount(0, $castMember['role_other']);
    }

    public function testCast_tv_one_episode_one_year()
    {
        $imdb = $this->getImdb('0306414');
        $cast = $imdb->cast();
        $castMember = array_find_item($cast, 'imdb', '0661449');

        $this->assertEquals('0661449', $castMember['imdb']);
        $this->assertEquals('Neko Parham', $castMember['name']);
        $this->assertEquals("State Police Undercover Troy Wiggins", $castMember['role']);
        $this->assertEquals(1, $castMember['role_episodes']);
        $this->assertEquals(2002, $castMember['role_start_year']);
        $this->assertEquals(2002, $castMember['role_end_year']);
        $this->assertIsArray($castMember['role_other']);
        $this->assertCount(0, $castMember['role_other']);
    }

    public function testStars_Cast()
    {
        $imdb = $this->getImdb();
        $stars = $imdb->actor_stars();
        $castMember = array_find_item($stars, 'imdb', '0000206');
        $this->assertEquals('0000206', $castMember['imdb']);
        $this->assertEquals('Keanu Reeves', $castMember['name']);
        $this->assertCount(3, $stars);
    }

    public function testStars_Cast_one_cast()
    {
        $imdb = $this->getImdb('2832384');
        $stars = $imdb->actor_stars();
        $this->assertCount(1, $stars);
        $castMember = $stars[0];
        $this->assertEquals('2926122', $castMember['imdb']);
        $this->assertEquals('Jochem Myjer', $castMember['name']);
    }

    // @TODO Why keep the brackets?
    public function testWriting_multiple_withrole()
    {
        $imdb = $this->getImdb('0087544');
        $this->assertEquals(array(
            array('imdb' => '0594503',
                'name' => 'Hayao Miyazaki',
                'role' => '(based on the manga by)'),
            array('imdb' => '0594503',
                'name' => 'Hayao Miyazaki',
                'role' => '(screenplay by)'),
            array(
                'imdb' => '1248357',
                'name' => 'Cindy Davis',
                'role' => '(english language adaptation) (as Cindy Davis Hewitt) &'
            ),
            array('imdb' => '1248358',
                'name' => 'Donald H. Hewitt',
                'role' => '(english language adaptation)'),
            array('imdb' => '0411872',
                'name' => 'Kazunori Itô',
                'role' => '(earlier screenplay) (uncredited)')
            ), $imdb->writing());
    }

    public function testWriting_tv()
    {
        $imdb = $this->getImdb('0306414');
        $credits = $imdb->writing();
        $this->assertEquals(array('imdb' => '0800108', 'name' => 'David Simon', 'role' => '(created by) (60 episodes, 2002-2008)'), $credits[0]);
    }

//    public function testWriting()
//    {
//        //@TODO more
//    }

    public function testProducer_no_producers()
    {
        $imdb = $this->getImdb(149937);
        $producers = $imdb->producer();
        $this->assertIsArray($producers);
        $this->assertCount(0, $producers);
    }

    public function testProducer()
    {
        $imdb = $this->getImdb();
        $producers = $imdb->producer();
        $this->assertCount(10, $producers);

        $this->assertEquals(array(
            'imdb' => '0075732',
            'name' => 'Bruce Berman',
            'role' => 'executive producer'
            ), $producers[0]);

        // Trims (As Larry Wachowski) from the end of the role
        $this->assertEquals(array(
            'imdb' => '0905152',
            'name' => 'Lilly Wachowski',
            'role' => 'executive producer'
            ), $producers[9]);
    }

    public function testProducer_series()
    {
        $imdb = $this->getImdb(306414);
        $producers = $imdb->producer();
        $this->assertCount(11, $producers);

        $this->assertEquals(array(
            'imdb' => '0861769',
            'name' => 'Karen L. Thorson',
            'role' => 'producer / co-producer (60 episodes, 2002-2008)'
            ), $producers[0]);
    }

    public function testCinematographer()
    {
        $imdb = $this->getImdb();
        $cinematographers = $imdb->cinematographer();
        $this->assertCount(1, $cinematographers);

        $this->assertEquals(array(
            'imdb' => '0691084',
            'name' => 'Bill Pope',
            'role' => 'director of photography'
        ), $cinematographers[0]);
    }

    public function testComposer_movie()
    {
        $imdb = $this->getImdb();
        $composers = $imdb->composer();
        $this->assertCount(1, $composers);
        $this->assertEquals(array('imdb' => '0204485', 'name' => 'Don Davis', 'role' => null), $composers[0]);
    }

    public function testComposer_series()
    {
        $imdb = $this->getImdb('1286039');
        $composers = $imdb->composer();
        $this->assertCount(1, $composers);
        $this->assertEquals(array('imdb' => '0006107', 'name' => 'Joel Goldsmith', 'role' => '(40 episodes, 2009-2011)'), $composers[0]);
    }

    public function testComposer_none()
    {
        // The wire has 'Series Music Department' but no 'Series Music by' section so returns no results
        $imdb = $this->getImdb('0306414');
        $composers = $imdb->composer();
        $this->assertCount(0, $composers);
    }

    public function testCrazy_credits()
    {
        $imdb = $this->getImdb();
        $credits = $imdb->crazy_credits();
        $this->assertCount(3, $credits);
        $this->assertEquals('At the end of all the credits, the URL for the (now defunct) website of the film is given, www.whatisthematrix.com, along with a password, \'steak\'. There\'s a \'secret\' link on the page that requests a password.', $credits[0]);
    }

    // @TODO Stopped writing out tests for all functions here .. there are plenty more

    public function testEpisodes_returns_nothing_for_a_film()
    {
        $imdb = $this->getImdb();
        $episodes = $imdb->episodes();
        $this->assertIsArray($episodes);
        $this->assertEmpty($episodes);
    }

    public function testEpisodes_returns_episodes_for_a_single_season_show()
    {
        $imdb = $this->getImdb(303461);
        $seasons = $imdb->episodes();
        $this->assertIsArray($seasons);
        $this->assertCount(1, $seasons);
        $this->assertCount(14, $seasons[1]);
    }

    public function testEpisodes_returns_episodes_for_a_episode_of_a_single_season_show()
    {
        $imdb = $this->getImdb('0579539'); // This is an episode of firefly, not the show
        $seasons = $imdb->episodes();
        $this->assertIsArray($seasons);
        $this->assertCount(1, $seasons);
        $this->assertCount(14, $seasons[1]);
    }

    public function testEpisodes_returns_episodes_for_a_multiseason_show()
    {
        $imdb = $this->getImdb('0306414');
        $seasons = $imdb->episodes();
        $this->assertIsArray($seasons);
        $this->assertCount(5, $seasons);
        $episode1 = $seasons[1][1];
        $lastEpisode = $seasons[5][10];

        $this->assertEquals('0749451', $episode1['imdbid']);
        $this->assertEquals('The Target', $episode1['title']);
        $this->assertEquals('2 Jun. 2002', $episode1['airdate']);
        $this->assertEquals("Baltimore Det. Jimmy McNulty finds himself in hot water with his superior Major William Rawls after a drug dealer, D'Angelo Barksdale who is charged with three murders, is acquitted. McNulty knows the judge in question and although it's not his case, he's called into chambers to explain what happened. Obviously key witnesses recanted their police statements on the stand but McNulty doesn't underplay Barksdale's role in at least 7 other murders. When the judge's raises his concerns at the senior levels of the police department, they have a new investigation on their ...", $episode1['plot']);
        $this->assertEquals(1, $episode1['season']);
        $this->assertEquals(1, $episode1['episode']);

        $this->assertEquals('0977179', $lastEpisode['imdbid']);
        $this->assertEquals('-30-', $lastEpisode['title']);
        $this->assertEquals('9 Mar. 2008', $lastEpisode['airdate']);
        $this->assertEquals("Carcetti maps out a damage-control scenario with the police brass in the wake of a startling revelation from Pearlman and Daniels. Their choice: clean up the mess...or hide the dirt.", $lastEpisode['plot']);
        $this->assertEquals(5, $lastEpisode['season']);
        $this->assertEquals(10, $lastEpisode['episode']);
    }

    public function testEpisodes_returns_episodes_for_a_multiseason_show_with_missing_airdates()
    {
        $imdb = $this->getImdb('1027544');
        $seasons = $imdb->episodes();
        $this->assertIsArray($seasons);
        $this->assertCount(4, $seasons);
        $episode = $seasons[1][20];

        $this->assertEquals('1956132', $episode['imdbid']);
        $this->assertEquals("Mama Mia", $episode['title']);
        $this->assertEquals('', $episode['airdate']);
        $this->assertEquals("Mr Carburettor is in a panic as his mother is coming to visit and he needs everything to be perfect.", $episode['plot']);
        $this->assertEquals(1, $episode['season']);
        $this->assertEquals(20, $episode['episode']);
    }

    public function testEpisodes_returns_episodes_for_a_multiseason_show_with_empty_plots()
    {
        $imdb = $this->getImdb('1027544');
        $seasons = $imdb->episodes();
        $this->assertIsArray($seasons);
        $this->assertCount(4, $seasons);
        $episode = $seasons[1][14];

        $this->assertEquals('1827207', $episode['imdbid']);
        $this->assertEquals("Make Up Your Mind Roary", $episode['title']);
        $this->assertEquals('2007', $episode['airdate']);
        $this->assertEquals("", $episode['plot']);
        $this->assertEquals(1, $episode['season']);
        $this->assertEquals(14, $episode['episode']);
    }

    public function testEpisodes_returns_unknown_season_episodes()
    {
        $imdb = $this->getImdb('1027544');
        $seasons = $imdb->episodes();

        $this->assertIsArray($seasons);
        $this->assertCount(4, $seasons);

        $episode = $seasons[-1][0];

        $this->assertEquals('1981928', $episode['imdbid']);
        $this->assertEquals("Rules Are Rules", $episode['title']);
        $this->assertEquals('9 Sep. 2010', $episode['airdate']);
        $this->assertEquals("", $episode['plot']);
        $this->assertEquals(-1, $episode['season']);
        $this->assertEquals(-1, $episode['episode']);
    }

    // Some shows don't work on seasons and so have many episodes assigned to season 1. Imdb used to just timeout rendering the page but they now cut the request off and don't render the page
    // Instead the episodes need to be fetched by year - which have far fewer per page and do load
    public function testEpisodes_many_episodes_season_1()
    {
        $imdb = $this->getImdb(988824);
        $years = $imdb->episodes();

        $this->assertIsArray($years);
        $this->assertCount(10, $years);

        $episode = $years[2009][1];

        $episodeCount = array_reduce($years, function ($count, $episodes) {
            return $count + count($episodes);
        }, 0);

        $this->assertEquals(502, $episodeCount);

        $this->assertEquals([
            "imdbid" => "0990165",
            "title" => "Kikyô",
            "airdate" => "28 Oct. 2009",
            "plot" => "Naruto returns to Konoha after a two-and-a-half-year training journey with Jiraiya and is reunited with Sakura.",
            "season" => 2009,
            "episode" => 1,
            "image_url" => 'https://m.media-amazon.com/images/M/MV5BYjE4YWE3ODYtNTE3MC00NzlhLWExZmEtZjU0MDk1ZThiOTA5XkEyXkFqcGdeQXVyNDk3NDEzMzk@._V1_UX224_CR0,0,224,126_AL_.jpg',
        ], $episode);
    }

    public function testGoofs()
    {
        $imdb = $this->getImdb();

        $goofs = $imdb->goofs();
        $this->assertIsArray($goofs);
        $this->assertGreaterThan(140, count($goofs));
        $this->assertLessThan(170, count($goofs));

        $this->assertEquals('Audio/visual unsynchronised', $goofs[0]['type']);
        $this->assertEquals('When Neo meets Trinity for the first time in the nightclub she is close to him talking in his ear. Even though she pauses between sentences the shot from the back of Trinity shows that her jaw is still moving during the pauses.', $goofs[0]['content']);
    }

    public function testQuotes()
    {
        $imdb = $this->getImdb();
        $quotes = $imdb->quotes();

        $this->assertGreaterThan(100, count($quotes));
    }

    public function testQuotes_split()
    {
        $imdb = $this->getImdb("0306414");
        $quotes_split = $imdb->quotes_split();

        $this->assertGreaterThan(10, count($quotes_split));

        $allInTheGame = null;
        foreach ($quotes_split as $quote_split) {
            if (2 == count($quote_split) && $quote_split[1]['quote'] === 'All in the game yo, all in the game.') {
                $allInTheGame = $quote_split;
            }
        }

        $this->assertEquals(array(
            array(
                'quote' => '[repeated line]',
                'character' => array(
                    'url' => '',
                    'name' => ''
                )
            ),
            array(
                'quote' => 'All in the game yo, all in the game.',
                'character' => array(
                    'url' => 'https://www.imdb.com/name/nm0931324/?ref_=tt_trv_qu',
                    'name' => 'Omar'
                )
            )
            ), $allInTheGame);
    }

    public function testTrailers_all()
    {
        $imdb = $this->getImdb(2395427);
        $trailers = $imdb->trailers(true);

        $this->assertCount(6, $trailers);

        $this->assertEquals(array(
            "title" => "Watch New Scenes",
            "url" => "https://www.imdb.com/videoplayer/vi2821566745",
            "resolution" => "HD",
            "lang" => "",
            "restful_url" => ""
            ), $trailers[0]);

        $this->assertEquals(array(
            "title" => "Trailer #3",
            "url" => "https://www.imdb.com/videoplayer/vi2906697241",
            "resolution" => "HD",
            "lang" => "",
            "restful_url" => ""
            ), $trailers[1]);
    }

    public function testTrailers_urlonly()
    {
        $imdb = $this->getImdb(2395427);
        $trailers = $imdb->trailers(false);

        $this->assertCount(6, $trailers);

        $this->assertEquals("https://www.imdb.com/videoplayer/vi2821566745", $trailers[0]);
        $this->assertEquals("https://www.imdb.com/videoplayer/vi2906697241", $trailers[1]);
    }

    public function testTrailers_no_trailers()
    {
        $imdb = $this->getImdb(149937);
        $trailers = $imdb->trailers();

        $this->assertCount(0, $trailers);
    }

    public function testTrivia()
    {
        $imdb = $this->getImdb();
        $trivia = $imdb->trivia();

        $this->assertGreaterThan(100, count($trivia));
        $this->assertTrue(in_array('The lobby shootout took ten days to film.', $trivia));
    }

    public function testTrivia_spoilers()
    {
        $imdb = $this->getImdb();
        $spoil = $imdb->trivia(true);
        // There aren't spoilers anymore, so this is just empty
        $this->assertCount(0, $spoil);
    }

    public function testMovieconnection_followed_by()
    {
        $imdb = $this->getImdb();
        $conn = $imdb->movieconnection();

        $this->assertGreaterThan(5, count($conn["followedBy"]));
        $this->assertEquals(array(
            'mid' => '0366179',
            'name' => 'The Second Renaissance Part I',
            'year' => '2003',
            'comment' => ''
            ), $conn["followedBy"][0]);
    }

    public function testSoundtrack_nosoundtracks() {
        $imdb = $this->getImdb('1899250');
        $result = $imdb->soundtrack();
        $this->assertEmpty($result);
    }

    public function testSoundtrack_matrix() {
        $imdb = $this->getImdb();
        $result = $imdb->soundtrack();
        $this->assertnotEmpty($result);
        $this->assertEquals(12, count($result));

        $rid = $result[11];
        $this->assertEquals('Rock is Dead', $rid['soundtrack']);
        $this->assertEquals("Written by Marilyn Manson, Jeordie White, and Madonna Wayne Gacy
Performed by Marilyn Manson
Courtesy of Nothing/Interscope Records
Under License from Universal Music Special Markets", $rid['credits']);
        $this->assertEquals("Written by <a href=\"/name/nm0001504/\">Marilyn Manson</a>, <a href=\"/name/nm0708390/\">Jeordie White</a>, and <a href=\"/name/nm0300476/\">Madonna Wayne Gacy</a> <br />
Performed by <a href=\"/name/nm0001504/\">Marilyn Manson</a> <br />
Courtesy of Nothing/Interscope Records <br />
Under License from Universal Music Special Markets <br />", $rid['credits_raw']);
    }

    public function testExtReviews()
    {
        $imdb = $this->getImdb();
        $extReviews = $imdb->extReviews();

        $this->assertEquals('http://www.rogerebert.com/reviews/the-matrix-1999', $extReviews[0]['url']);
        $this->assertEquals('rogerebert.com [Roger Ebert]', $extReviews[0]['desc']);
    }

    public function test_releaseInfo()
    {
        $imdb = $this->getImdb(107290);
        $releaseInfo = $imdb->releaseInfo();

        $this->assertGreaterThanOrEqual(165, count($releaseInfo));
        $this->assertLessThanOrEqual(175, count($releaseInfo));

        $this->assertEquals(array(
            'country' => 'USA',
            'day' => '9',
            'month' => 'June',
            'mon' => '06',
            'year' => '1993',
            'comment' => '(Washington, D.C.) (premiere)'
            ), $releaseInfo[0]);

        $this->assertEquals(array(
            'country' => 'USA',
            'day' => '11',
            'month' => 'June',
            'mon' => '06',
            'year' => '1993',
            'comment' => ''
            ), $releaseInfo[2]);
    }

    public function test_locations()
    {
        $imdb = $this->getImdb(107290);
        $locations = $imdb->locations();
        $this->assertGreaterThan(17, $locations);

        $matches = 0;
        foreach ($locations as $location) {
            if (strpos($location, 'Kualoa Ranch') === 0) {
                ++$matches;
            }
        }
        $this->assertEquals(1, $matches);
    }

    public function testProdCompany_empty_notes()
    {
        $imdb = $this->getImdb("0306414");
        $prodCompany = $imdb->prodCompany();
        $this->assertEquals('Blown Deadline Productions', $prodCompany[0]['name']);
        $this->assertEquals('https://www.imdb.com/company/co0019588?ref_=ttco_co_1', $prodCompany[0]['url']);
        $this->assertEquals('', $prodCompany[0]['notes']);
    }

    public function testProdCompany()
    {
        $imdb = $this->getImdb();
        $prodCompany = $imdb->prodCompany();
        $this->assertEquals('Warner Bros.', $prodCompany[0]['name']);
        $this->assertEquals('https://www.imdb.com/company/co0002663?ref_=ttco_co_1', $prodCompany[0]['url']);
        $this->assertEquals('(presents)', $prodCompany[0]['notes']);
    }

    public function testDistCompany()
    {
        $imdb = $this->getImdb();
        $distCompany = $imdb->distCompany();
        $this->assertEquals('Mauris Film', $distCompany[0]['name']);
        $this->assertEquals('https://www.imdb.com/company/co0613366?ref_=ttco_co_1', $distCompany[0]['url']);
        $this->assertEquals('(2019) (Russia) (theatrical)', $distCompany[0]['notes']);
    }

    public function testSpecialCompany()
    {
        $imdb = $this->getImdb();
        $specialCompany = $imdb->specialCompany();
        $amalgamated = array_find_item($specialCompany, 'name', 'Amalgamated Pixels');
        $this->assertEquals('Amalgamated Pixels', $amalgamated['name']);
        $this->stringStartsWith('https://www.imdb.com/company/co0012497')->evaluate($amalgamated['url']);
        $this->assertEquals('(additional visual effects)', $amalgamated['notes']);
    }

    public function testOtherCompany()
    {
        $imdb = $this->getImdb();
        $otherCompany = $imdb->otherCompany();
        $absoluteRentals = array_find_item($otherCompany, 'name', 'Absolute Rentals');
        $this->assertEquals('Absolute Rentals', $absoluteRentals['name']);
        $this->stringStartsWith('https://www.imdb.com/company/co0235245')->evaluate($absoluteRentals['url']);
        $this->assertEquals('(post-production rentals)', $absoluteRentals['notes']);
    }

    public function testParentalGuide()
    {
        $imdb = $this->getImdb();
        $parentalGuide = $imdb->parentalGuide();
        $profanity = $parentalGuide['Profanity'];
        $drugs = $parentalGuide['Drugs'];
        $this->assertGreaterThanOrEqual(2, count($profanity));
        $this->assertGreaterThan(5, $drugs);
        $this->assertContains('The songs in the end credits have some uses of the f bomb.', $profanity);
        $this->assertContains('The Oracle smokes a cigarette.', $drugs);
    }

    public function testParentalGuide_spoilers()
    {
        $imdb = $this->getImdb(120737);
        $parentalGuide = $imdb->parentalGuide(TRUE);
        $violence = $parentalGuide['Frightening'][1];
        $this->assertSame(0, strpos($violence, 'Gandalf&#39;s "death" scene'));
    }

    public function testOfficialsites()
    {
        $imdb = $this->getImdb();
        $officialSites = $imdb->officialSites();
        $this->assertContains(
            [
                'url' => 'https://www.facebook.com/TheMatrixMovie/',
                'name' => 'Official Facebook'
            ],
            $officialSites);
    }

    public function testKeywords_all()
    {
        $imdb = $this->getImdb();
        $keywords_all = $imdb->keywords_all();
        $this->assertGreaterThan(250, count($keywords_all));
        $this->assertTrue(in_array('truth', $keywords_all));
        $this->assertTrue(in_array('human machine relationship', $keywords_all));
    }

    public function test_title_redirects_are_followed()
    {
        $imdb = $this->getImdb('2768262');
        $this->assertEquals('The Battle of the Sexes', $imdb->title());
    }

    public function testAwards_correctly_parses_an_entry_with_expandable_note()
    {
        $imdb = $this->getImdb('0306414');
        $awards = $imdb->awards();

        $award = $awards['AFI Awards, USA'];
        $firstEntry = $award['entries'][0];

        $this->assertEquals(2009, $firstEntry['year']);
        $this->assertEquals(true, $firstEntry['won']);
        $this->assertEquals('TV Program of the Year', $firstEntry['category']);
        $this->assertEquals('AFI Award', $firstEntry['award']);
        $this->assertCount(0, $firstEntry['people']);
        $this->assertEquals('Won', $firstEntry['outcome']);
    }

    public function testAwards_correctly_parses_an_entry_with_no_category_with_a_following_entry()
    {
        $imdb = $this->getImdb('0306414');
        $awards = $imdb->awards();

        $award = $awards['Television Critics Association Awards'];
        $firstEntry = $award['entries'][0];

        $this->assertEquals(2008, $firstEntry['year']);
        $this->assertEquals(true, $firstEntry['won']);
        $this->assertEquals('', $firstEntry['category']);
        $this->assertEquals('Heritage Award', $firstEntry['award']);
        $this->assertCount(0, $firstEntry['people']);
        $this->assertEquals('Won', $firstEntry['outcome']);
    }

    public function testAwards_correctly_parses_a_single_entry_award_with_one_person()
    {
        $imdb = $this->getImdb();
        $awards = $imdb->awards();

        $ifmca = $awards['International Film Music Critics Award (IFMCA)'];
        $firstEntry = $ifmca['entries'][1];

        $this->assertEquals(1999, $firstEntry['year']);
        $this->assertEquals(false, $firstEntry['won']);
        $this->assertEquals('Film Score of the Year', $firstEntry['category']);
        $this->assertEquals('FMCJ Award', $firstEntry['award']);
        $this->assertCount(1, $firstEntry['people']);
        $this->assertEquals('Don Davis', $firstEntry['people']['0204485']);
        $this->assertEquals('Nominated', $firstEntry['outcome']);
    }

    public function testAwards_correctly_parses_a_single_entry_award_with_two_people()
    {
        $imdb = $this->getImdb();
        $awards = $imdb->awards();

        $this->assertCount(39, $awards);

        $scifiWritersAward = $awards['Science Fiction and Fantasy Writers of America'];
        $firstEntry = $scifiWritersAward['entries'][0];

        $this->assertEquals(2000, $firstEntry['year']);
        $this->assertEquals(false, $firstEntry['won']);
        $this->assertEquals('Best Script', $firstEntry['category']);
        $this->assertEquals('Nebula Award', $firstEntry['award']);
        $this->assertCount(2, $firstEntry['people']);
        $this->assertEquals('Lana Wachowski', $firstEntry['people']['0905154']);
        $this->assertEquals('Lilly Wachowski', $firstEntry['people']['0905152']);
        $this->assertEquals('Nominated', $firstEntry['outcome']);
    }

    public function testAwards_correctly_parses_a_multi_entry_award()
    {
        $imdb = $this->getImdb();
        $awards = $imdb->awards();

        $award = $awards['Online Film & Television Association'];

        $this->assertCount(6, $award['entries']);

        // Merges in award from 2021 with the ones from 2000
        $award2021 = $award['entries'][0];

        $this->assertEquals(2021, $award2021['year']);

        $firstEntry2000 = $award['entries'][1];

        $this->assertEquals(2000, $firstEntry2000['year']);
        $this->assertEquals(true, $firstEntry2000['won']);
        $this->assertEquals('Best Sound Mixing', $firstEntry2000['category']);
        $this->assertEquals('OFTA Film Award', $firstEntry2000['award']);
        $this->assertCount(4, $firstEntry2000['people']);
        $this->assertEquals('John T. Reitz', $firstEntry2000['people']['0718676']);
        $this->assertEquals('Gregg Rudloff', $firstEntry2000['people']['0748832']);
        $this->assertEquals('David E. Campbell', $firstEntry2000['people']['0132372']);
        $this->assertEquals('David Lee Fein', $firstEntry2000['people']['0270646']);
        $this->assertEquals('Won', $firstEntry2000['outcome']);

        $secondEntry2000 = $award['entries'][2];

        $this->assertEquals(2000, $secondEntry2000['year']);
        $this->assertEquals(true, $secondEntry2000['won']);
        $this->assertEquals('Best Visual Effects', $secondEntry2000['category']);
        $this->assertEquals('OFTA Film Award', $secondEntry2000['award']);
        $this->assertCount(4, $secondEntry2000['people']);
        $this->assertEquals('John Gaeta', $secondEntry2000['people']['0300665']);
        $this->assertEquals('Janek Sirrs', $secondEntry2000['people']['0802938']);
        $this->assertEquals('Steve Courtley', $secondEntry2000['people']['0183871']);
        $this->assertEquals('Jon Thum', $secondEntry2000['people']['0862039']);
        $this->assertEquals('Won', $secondEntry2000['outcome']);
    }

    public function testAwards_correctly_parses_an_entry_with_no_people()
    {
        $imdb = $this->getImdb();
        $awards = $imdb->awards();

        $award = $awards['Online Film & Television Association'];

        $this->assertCount(6, $award['entries']);

        $fifthEntry = $award['entries'][5];

        $this->assertEquals(2000, $fifthEntry['year']);
        $this->assertEquals(false, $fifthEntry['won']);
        $this->assertEquals('Best Official Film Website', $fifthEntry['category']);
        $this->assertEquals('OFTA Film Award', $fifthEntry['award']);
        $this->assertCount(0, $fifthEntry['people']);
        $this->assertEquals('Nominated', $fifthEntry['outcome']);
    }

    public function testAwards_correctly_parses_an_entry_with_no_category_or_people()
    {
        $imdb = $this->getImdb();
        $awards = $imdb->awards();

        $award = $awards['National Film Preservation Board, USA'];

        $this->assertCount(1, $award['entries']);

        $firstEntry = $award['entries'][0];

        $this->assertEquals(2012, $firstEntry['year']);
        $this->assertEquals(true, $firstEntry['won']);
        $this->assertEquals('', $firstEntry['category']);
        $this->assertEquals('National Film Registry', $firstEntry['award']);
        $this->assertCount(0, $firstEntry['people']);
        $this->assertEquals('Won', $firstEntry['outcome']);
    }

    public function testAwards_correctly_parses_an_entry_where_people_have_role_descriptions()
    {
        $imdb = $this->getImdb();
        $awards = $imdb->awards();

        $award = $awards['Motion Picture Sound Editors, USA'];

        $this->assertCount(3, $award['entries']);

        $thirdEntry = $award['entries'][2];

        $this->assertEquals(2000, $thirdEntry['year']);
        $this->assertEquals(false, $thirdEntry['won']);
        $this->assertEquals('Best Sound Editing - Music (Foreign & Domestic)', $thirdEntry['category']);
        $this->assertEquals('Golden Reel Award', $thirdEntry['award']);
        $this->assertCount(3, $thirdEntry['people']);
        $this->assertEquals('Lori L. Eschler', $thirdEntry['people']['0002669']);
        $this->assertEquals('Zigmund Gron', $thirdEntry['people']['0343065']);
        $this->assertEquals('Jordan Corngold', $thirdEntry['people']['0180383']);
        $this->assertEquals('Nominated', $thirdEntry['outcome']);
    }

    public function testAwards_correctly_parses_an_entry_with_no_category_name()
    {
        $imdb = $this->getImdb();
        $awards = $imdb->awards();

        $award = $awards['BMI Film & TV Awards'];

        $this->assertCount(1, $award['entries']);

        $firstEntry = $award['entries'][0];

        $this->assertEquals(1999, $firstEntry['year']);
        $this->assertEquals(true, $firstEntry['won']);
        $this->assertEquals('', $firstEntry['category']);
        $this->assertEquals('BMI Film Music Award', $firstEntry['award']);
        $this->assertCount(1, $firstEntry['people']);
        $this->assertEquals('Don Davis', $firstEntry['people']['0204485']);
        $this->assertEquals('Won', $firstEntry['outcome']);
    }

    public function test_budget()
    {
        $budget = $this->getImdb();
        $this->assertEquals(63000000, $budget->budget());
    }

    public function test_filmingDates()
    {
        $imdb = $this->getImdb();
        $filmingDates = $imdb->filmingDates();
        $this->assertIsArray($filmingDates);
        $this->assertEquals('1998-03-14', $filmingDates['beginning']);
        $this->assertEquals('1998-09-01', $filmingDates['end']);
    }

    public function test_videosites()
    {
        $imdb = $this->getImdb();
        $videoSites = $imdb->videosites();

        $this->assertIsArray($videoSites);
        $this->assertGreaterThan(2, $videoSites);
    }

    public function test_alternateversions()
    {
        $imdb = $this->getImdb();
        $alternateVersions = $imdb->alternateVersions();

        $this->assertGreaterThan(7, count($alternateVersions));
        $this->assertLessThan(12, count($alternateVersions));

        $this->assertEquals($alternateVersions[0], "Because 'The Matrix' was filmed in Australia the Region 4 (Australia) DVD release includes a more comprehensive Australian based list of credits.");

        foreach ($alternateVersions as $alternateVersion) {
            $this->assertNotEmpty($alternateVersion);
        }
    }

    public function test_alternateversions_no_alternate_versions()
    {
        $imdb = $this->getImdb('0056592');
        $alternateVersions = $imdb->alternateVersions();

        $this->assertCount(0, $alternateVersions);
    }

    public function test_alternateversions_list()
    {
        $imdb = $this->getImdb('0120737');
        $alternateVersions = $imdb->alternateVersions();

        $this->assertGreaterThan(7, count($alternateVersions));
        $this->assertLessThan(12, count($alternateVersions));

        $this->assertSame(0, strpos($alternateVersions[1], "The Extended Edition DVD includes the following changes to the film.\n- During the prologue"));

        foreach ($alternateVersions as $alternateVersion) {
            $this->assertNotEmpty($alternateVersion);
        }
    }

    public function test_real_id()
    {
        $imdb = $this->getImdb();
        $this->assertEquals('0133093', $imdb->real_id());
    }

    public function test_real_id_after_redirect()
    {
        $imdb = $this->getImdb('2768262');
        $this->assertEquals('2386868', $imdb->real_id());
    }

    /**
     * Create an imdb object that uses cached pages
     * The matrix by default
     * @return \Imdb\Title
     */
    protected function getImdb($imdbId = '0133093')
    {
        $config = new \Imdb\Config();
        $config->language = 'en-US';
        $config->cachedir = realpath(dirname(__FILE__) . '/cache') . '/';
        $config->usezip = false;
        $config->cache_expire = 3600;
        $config->debug = false;
        $imdb = new \Imdb\Title($imdbId, $config);
        return $imdb;
    }
}
