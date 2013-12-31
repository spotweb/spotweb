<?php
    class ScraperDto {
        public $functionName;          /* Name of the tag, eg: CreateSearchUrl */
        public $dest;                  /* destination slot */
        public $clearBuffers;

        /**
         * @var ScraperRegExp
         */
        public $regExpList;            /* List of RegExp tags below the funtcion */
    } # ScraperDto

    class ScraperRegExp {
        public $conditional;           /* condition has to be true to be exeuted */
        public $input;                 /* input string with placeholders */
        public $output;                /* Output string with placeholders */
        public $dest;                  /* Destination slot */
        public $cache;                 /* ???? */

        /*
         * Fields which are listed in the <Expression> tag of a RegExp
         */
        public $expr_regex;             /* Actual regex pattern below the regex */
        public $expr_clear;             /* ? */
        public $expr_noclean;           /* ? */
        public $expr_fixchars;          /* ? */
        public $expr_repeat;            /* Repeat this statement until no mathes */

        /**
         * When 'output' contains the <url></url> tag, we replace it with
         * a placeholder, this way we prevent from having to re-parse the URL
         * tag a lot of times
         */
        public $out_url;
        public $out_cache;
        public $out_func;
        public $out_headers;

        /**
         * @var ScraperRegExp
         */
        public $childRegExes;
    } # ScraperRegExp


    function parseScraperRegExp($regExpElm) {
        /*
         * There can be 1..n RegExp elements within a function,
         * and they can have another list of child Regular expressions
         */
        $regExp = new ScraperRegExp();
        $regExp->input = $regExpElm->getAttribute('input');
        $regExp->dest = $regExpElm->getAttribute('dest');
        $regExp->cache = $regExpElm->getAttribute('cache');
        $regExp->conditional = $regExpElm->getAttribute('conditional');
        $regExp->output = $regExpElm->getAttribute('output');
        $regExp->childRegExes = array();

        /*
         * Let's see if the output contains an <URL></URL> tag
         */
        if (stripos($regExp->output, '<url') !== false) {
            preg_match_all('/<url(.*)<\/url>/i', $regExp->output, $matches, PREG_SET_ORDER);

            if (isset($matches[0][0])) {
                $outputDom = new DOMDocument();
                $outputDom->preserveWhiteSpace = false;
                $outputDom->loadXML('<xml>' . $matches[0][0] . '</xml>');

                $urlElm = $outputDom->getElementsByTagName('url')->item(0);

                /*
                 * The URL can ontain an pipe, with after that headers.
                 */
                $urlList = explode('|', $urlElm->nodeValue);

                $regExp->out_func = $urlElm->getAttribute('function');
                $regExp->out_cache = $urlElm->getAttribute('cache');
                $regExp->out_url = $urlList[0];
                if (count($urlList) > 1) {
                    $regExp->out_headers = array_slice($urlList, 1);
                } # if

                $regExp->output = str_replace($matches[0][0], chr(3), $regExp->output);
            } # if

        } # if

        /*
         * A RegExp element can have child items, if it does, lets parse
         * those as well
         */
        $childRegExpElmList = $regExpElm->getElementsByTagName('RegExp');
        foreach($childRegExpElmList as $childRegExpElm) {
            $regExp->childRegExes[] = parseScraperRegExp($childRegExpElm);
        } # foreach


        /*
         * Get the actual expression element for this RegExp element, the
         */
        foreach($regExpElm->childNodes as $exprElm) {
            if ($exprElm->nodeName == 'expression') {
                $regExp->expr_clear = $exprElm->getAttribute('clear');
                $regExp->expr_fixchars = $exprElm->getAttribute('fixchars');
                $regExp->expr_noclean = $exprElm->getAttribute('noclean');
                $regExp->expr_repeat = (bool) $exprElm->getAttribute('repeat');
                $regExp->expr_regex = (string) $exprElm->nodeValue;
                if (empty($regExp->expr_regex)) {
                    $regExp->expr_regex = '(.*)';
                } # if
            } # if
        }

        return $regExp;
    } # parseScraperRegExp

    function parseScraperXml($xml, $headElmName) {
        $funcList = array();
        $dom = new DOMDocument();
        /*
         * We need to ignore white space, else they will
         * end up as DomText elements during parsing
         */
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);

        /*
         * Basic XML looks like:
         *      <xml>
         *         <$heading>
         *                              -
         *              <$function>     |           We will parse these funtions and construct
         *              </$function>    |           ScraperDto's from it
         *                              -
         *         </$heading>
         *      </xml>
         */
        $funcNodes = $dom->getElementsByTagName($headElmName)->item(0)->childNodes;
        foreach($funcNodes as $funcElm) {

            $scraperFunc = new ScraperDto();
            $scraperFunc->clearBuffers = (bool) $funcElm->getAttribute('clearbuffers');
            $scraperFunc->dest = $funcElm->getAttribute('dest');
            $scraperFunc->functionName = $funcElm->tagName;
            $scraperFunc->regExpList = array();

            /*
             * Now lets see if this function has RegExp's elements
             */
            $regExpElms = $funcElm->childNodes;
            foreach($regExpElms as $regExpElm) {
                $scraperFunc->regExpList[] = parseScraperRegExp($regExpElm);
            } # foreach

            $funcList[$scraperFunc->functionName] = $scraperFunc;
        } # foreach

        return $funcList;
    } # parseSraperXml

    $input = array(
            1   =>   'Fast and the Furious',
            2   =>   '2001'
    );

    $conditions = array(
            'tmdbsearch' => false,
            'imdbsearch' => true
    );

    function replace_input($x, $input) {
        foreach($input as $key => $val) {
            $x = str_replace('$$' . $key, $val, $x);
        } # foreach

        $x = str_replace('$INFO[imdbakatitles]', 'Keep Original', $x);

        return $x;
    } # replace_input


    function saveDest($input, $tmpStr, $dest) {
        // do we need to append the output or overwrite?
        if (substr($dest, -1) == '+') {
            $dest = substr($dest, 0, -1);

            if (!isset($input[$dest])) {
                $input[$dest] = '';
            } # if

            $input[$dest] .= $tmpStr;
        } else {
            $input[$dest] = $tmpStr;
        } # else

        return $input;
    } # saveDest

    function run($func, array $structure, array $input) {
        global $conditions;

        /*
         * Run any child regular expressions
         */
        if (!empty($func->childRegExes)) {
            foreach($func->childRegExes as $regExp) {
                $input = run($regExp, $structure, $input);
            } # foreach
        } # if

        if ($func instanceof ScraperDto) {
            $tmpInput = array(1 => $input[1]);

            foreach($func->regExpList as $regExp) {
                echo 'Running nested RegExp ...: ' . PHP_EOL;

                $tmpInput = run($regExp, $structure, $tmpInput);
            } # foreach

            $input = saveDest($input, $tmpInput[$func->dest], $func->dest);
        } else {
            /*
             * We are still not actually scraping, but
             * we are asked to run a function ..
             */
            if (!empty($func->out_func)) {
                /*
                 * We want a new buffer array, because buffers for
                 * functions are local
                 */
                $newInput = array(1 => $input[1]);
                $input = run($structure[$func->out_func], $structure, $input);

                $func->output = str_replace(chr(3), $input[$func->dest], $func->output);
            } # if

            /*
             * Actually run the regular expression
             */
            $pattern = '%' .  (string) $func->expr_regex . '%s';
            $tmpInput = replace_input((string) $func->input, $input);
            $tmpOutput = $func->output;
            $output = '';

            if (preg_match_all($pattern, $tmpInput, $matches, PREG_SET_ORDER) > 0) {
                if ($func->expr_repeat) {


                    foreach($matches as $match) {
                        $blahTmp = $tmpOutput;

                        foreach($match as $matchKey => $matchVal) {
                            $blahTmp = str_replace('\\' . $matchKey, $matchVal, $blahTmp);
                        } # foreach

                        $output .= $blahTmp;
                    } # foreach

                } else {
                    $blahTmp = $tmpOutput;

                    foreach($matches[0] as $matchKey => $matchVal) {
                        echo '\\' . $matchKey . PHP_EOL;

                        $blahTmp = str_replace('\\' . $matchKey, $matchVal, $blahTmp);
                    } # foreach

                    $output = $blahTmp;
                } # else

                $input = saveDest($input, $output, $func->dest);
            } else {
                /*
                 * No match was found
                 */
                if ($func->expr_clear) {
                    $input = saveDest($input, '', $func->dest);
                } # if
            } # else
        } # else

        return $input;
    } # run

    // $imdb = file_get_contents("http://www.imdb.com/title/tt0368891/");
    $imdb = file_get_contents("imdb-combined.htm");

    $input[2] = $imdb;

// GetIMDBFullCastById ook niet
// GetIMDBFullDirectorsById
// GetIMDBFullWritersById
// GetIMDBThumbsById
// GetIMDBUSACert (INFO ??)
// GetIMDBCountryCert (INFO ??)
// GetIMDBAKATitlesById

$s = microtime(true);
$input[1] = $imdb;
// parseScraperXml(file_get_contents('universal.xml'), 'scraper');
$scraperFunc = parseScraperXml(file_get_contents('imdb.xml'), 'scraperfunctions');
/*
 * ToDo: GetIMDBCountryCert
 *       GetIMDBAKATitlesById
 */
$input = run($scraperFunc['GetIMDBAKATitlesById'], $scraperFunc, $input);
echo PHP_EOL . 'Wheeee.. ' . PHP_EOL;
var_dump($input[5]);
echo microtime(true) - $s;
die();

    $scraper = simplexml_load_file('imdb.xml');
    $plotFunction = $scraper->GetIMDBGenresById;
    $input[1] = 'tt0368891';
//     run($scraper, $plotFunction);
    echo '--------------------' . PHP_EOL;
//    var_dump($input);
// die();


    $scraper = simplexml_load_file('universal.xml');
    $searchFunction = $scraper->CreateSearchUrl;
    $input[1] = 'Fast and the Furious';
    $input[2] = '2001';
var_dump($searchFunction);
die();
    run($scraper, $searchFunction);

echo '--------------------' . PHP_EOL;
var_dump($input);
