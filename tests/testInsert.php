<?php
error_reporting(E_ALL);	

 mysql_connect('localhost', 'spotweb', 'spotweb', 'spotweb');

mysql_select_db('spotweb');

  $data = array('5412321e9b3024b668854ef1ffff606a140@free.pt', 'testposter', 'Test Title', 'tagtagtagtagtag', 0, 'a10|', 'b3|', 'c6|', 'd11|', 'z1|', time(), -time(), 0, NULL, 0, 0, 0, '');


  $sql = '';


  $x = microtime(true);
  for($i = 0; $i < 50; $i++) {
  $sql = 'INSERT INTO spots(messageid, poster, title, tag, category, subcata, subcatb, subcatc, subcatd, subcatz, stamp, reversestamp, filesize, moderated, commentcount, spotrating, reportcount, spotterid) VALUES';
  for ($j = 0; $j < 10000; $j++) {
	$sql .= "('{$data[0]}{$i}-{$j}', '{$data[1]}', '{$data[2]}', '{$data[3]}', {$data[4]}, 	
				'{$data[5]}', '{$data[6]}', '{$data[7]}', '{$data[8]}', '{$data[9]}', {$data[10]}, {$data[11]}, {$data[12]}, NULL, {$data[14]}, {$data[15]}, {$data[16]}, '{$data[17]}'),";
  } # for
  $sql = substr($sql, 0, -1);

  	 mysql_query($sql);

  	 if (mysql_errno() != 0) {
  	 	echo "ffs!" . PHP_EOL;
  	 	break;
  	 } # if
  	} # for

   $y = microtime(true);

   echo "Total time for 500k inserts: " . ($y - $x) . PHP_EOL;

die(mysql_error().PHP_EOL);
