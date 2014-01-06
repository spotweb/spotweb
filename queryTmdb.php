<?php
define('TMDB_API_KEY', '9a3cc6d14b4765b19587e229556ae226');


function queryTmdb($mcId, $title, $year) {
    $baseUrl = 'http://api.themoviedb.org/3/search/movie?api_key=' . TMDB_API_KEY  .
                    '&append_to_response=trailers,credits,images&language=en';

    /*
     * Build the query
     */
    $url = $baseUrl .
                '&query=' . urlencode($title) .
                '&year=' . urlencode($year);

    echo "Performing a TMDB search for: " . $title . " (" . $year . ")";

    $json = file_get_contents($url);

    $parsed = json_decode($json);

    echo ", done." . PHP_EOL;

    if (empty($parsed->results)) {
        return null;
    } // if

    return array('title' => $parsed->results[0]->title,
                 'tmdbid' => $parsed->results[0]->id,
                 'mcid' => $mcId);
}

echo $argv[0] . " - query tmdb distribution script" . PHP_EOL . PHP_EOL;
if ($argc < 2) {
    echo "Je moet de filenaam welke je gekregen hebt als eerste parameter meegeven" . PHP_EOL;
    die();
}

if (!is_readable($argv[1])) {
    echo "Bestand '" . $argv[1] . "' kan niet worden gelezen" . PHP_EOL;
    die();
} // if

@file_put_contents('results.csv', '', FILE_APPEND);
if (!is_writeable('results.csv')) {
    echo "Bestand 'results.csv' kan niet worden geschreven" . PHP_EOL;
    die();
} // if

echo "Load the master collections table into memory, ";
$masters = unserialize(file_get_contents($argv[1]));
echo "done (" . count($masters) . " records)" . PHP_EOL;

$counter = 0;
$startTime = time();
foreach($masters as $mc) {
    $result = queryTmdb($mc['mcid'], $mc['title'], $mc['year']);
    $counter++;

    if ($result !== null) {
        file_put_contents('results.csv', $result['mcid'] . ';' . $result['tmdbid'] . ';'. $result['title'] . ';' . $mc['title'] . PHP_EOL, FILE_APPEND);
    } else {
        file_put_contents('results.csv', $mc['mcid'] . ';NULL;NULL;' . $mc['title'] . PHP_EOL, FILE_APPEND);
    } // if

    if ($counter > 29) {
        echo "  backing off for " . max(3, 12 - (time() - $startTime)) . " seconds" . PHP_EOL;
        // we try to limit ourselves to 30 records per 12 seconds
        sleep(max(0, 12 - (time() - $startTime)));

        $counter = 0;
        $startTime = time();
    } // if
} // foreach

