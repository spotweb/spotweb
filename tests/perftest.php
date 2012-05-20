<?php

function one($y) {
	echo 'Length: ' . strlen($y) . PHP_EOL;
} # one()

function two(&$y) {
	echo 'Length: ' . strlen($y) . PHP_EOL;
} # two()

class Dto {
	var $title;
	var $author;
	var $date;
	var $messageid;
	var $idlist;

	function Dto($title, $author, $date, $messageid, $idlist) {
		$this->title = $title;
		$this->author = $author;
		$this->date = $date;
		$this->messageid = $messageid;
		$this->idlist = $idlist;
	} # ctor
} # class Spotinfo

$objList = array();
$arList = array();

$y = microtime(true);
for($i = 0; $i < 10000; $i++) {
	$x = new Dto('title', 'author', '01 april 2012',  '<asdlksjjkslda98213890jsdalkda@bliep.net>', array('<asdlksjjkslda98213890jsdalkda@bliep.net>'));
	$objList[] = $x;
} # for 

echo 'Object run time: ' . (microtime(true) - $y) . PHP_EOL;

$y = microtime(true);
for($i = 0; $i < 10000; $i++) {
	$x = array('title' => 'title',
		   'author' => 'author',
		   'date' => '01 april 2012',
		   'messageid' => '<asdlksjjkslda98213890jsdalkda@bliep.net>',
		   'idlist' => array('<sdkdsalksdaljkd@bliep.net>'));

	$arList[] = $x;
} # for

echo 'Array run time: ' . (microtime(true) - $y) . PHP_EOL;

$x = str_repeat('aaaaa', 12 * 1024);
$y = array($x, $x, $x);

$y = microtime(true);
one($x);
echo 'Passing not per reference: ' . ((microtime(true) - $y) * 1000) . PHP_EOL;
$y = microtime(true);
two($x);
echo 'Passing per reference: ' . ((microtime(true) - $y) * 1000) . PHP_EOL;
