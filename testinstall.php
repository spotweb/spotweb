<html>
	<head>
		<title>Install ...</title>
		<style type='text/css'>
			body {margin:0;}
			body.fixed {padding:25px 0 0 0;}

			div.container {position:relative; font:11px Arial, Helvetica, sans-serif;}
			
		</style>
	</head>

	<body>

<?php
	$extList = get_loaded_extensions();
	$phpVersion = explode(".", phpversion());
	
	
	function return_bytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	} # return_bytes()
	
	function showResult($b, $hint = "") {
		if ($b) {
			echo "OK";
		} else {
			echo "NOT OK";
			
			if (!empty($hint)) {
				echo '(' . $hint . ')';
			} 
		} # else
	} # showResult
				
?>

	<table>
		<tr> <th> PHP settings </th> <th> OK ? </th> </tr>
		<tr> <td> PHP version </td> <td> <?php showResult(($phpVersion[0] >= '5' && $phpVersion[1] >= 3), "PHP 5.3 or later is recommended"); ?>  </td> </tr>
		<tr> <td> timezone settings </td> <td> <?php showResult(ini_get("date.timezone"), "Please specify date.timezone in your PHP.ini"); ?> </td> </tr>
		<tr> <td> Open base dir </td> <td> <?php showResult(!ini_get("open_basedir"), "Not empty, might be a problem"); ?>  </td> </tr>
		<tr> <td> Allow furl open </td> <td> <?php showResult(ini_get("allow_url_fopen") == 1, "allow_url_fopen not on -- will cause problems to retrieve external data"); ?> </td> </tr>
		<tr> <td> PHP safe mode </td> <td> <?php showResult(!ini_get('safe_mode'), "Safe mode set -- will cause problems for retrieve.php"); ?> </td> </tr>
		<tr> <td> Memory limit </td> <td> <?php showResult(return_bytes(ini_get('memory_limit')) >= (32*1024*1024), "memory_limit below 32M"); ?> </td> </tr>
	</table>
	
	<br>
	
	<table>
		<tr> <th> PHP extension </th> <th> OK ? </th> </tr>

		<tr> <td> MySQL </td> <td> <?php showResult(array_search('mysql', $extList) !== false, "(geen probleem als sqlite3 geinstalleerd is)"); ?>  </td> </tr>
		<tr> <td> OpenSSL </td> <td> <?php showResult(array_search('openssl', $extList) !== false); ?>  </td> </tr>
		<tr> <td> gmp </td> <td> <?php showResult(array_search('gmp', $extList) !== false); ?> </td> </tr>
		<tr> <td> ctype </td> <td> <?php showResult(array_search('ctype', $extList) !== false); ?> </td> </tr>
		<tr> <td> xml </td> <td> <?php showResult(array_search('xml', $extList) !== false); ?> </td> </tr>
		<tr> <td> zlib </td> <td> <?php showResult(array_search('zlib', $extList) !== false); ?> </td> </tr>
		<tr> <td> GD </td> <td> <?php showResult(array_search('gd', $extlist) !== false); ?> </td> </tr>
	</table>

	<br>
	
<?php
	@include('settings.php');
	
	function ownWarning($errno, $errstr) {
		$GLOBALS['iserror'] = true;
		#echo $errstr;
	} # ownWarning

	function testInclude($fname) {
		$GLOBALS['iserror'] = false;
		include($fname);
		return !($GLOBALS['iserror']);
	} # testInclude
		
	set_error_handler("ownWarning",E_WARNING);
?>

	<table>
		<tr> <th> Include files  </th> <th> OK ? </th> </tr>
		<tr> <td> Settings file </td> <td> <?php showResult(testInclude("settings.php"), "settings.php kan niet worden gelezen"); ?>  </td> </tr>
		<tr> <td> PEAR </td> <td> <?php showResult(testInclude("System.php"), "PEAR kan niet gevonden worden"); ?> </td> </tr>
		<tr> <td> PEAR Net/NNTP </td> <td> <?php showResult(testInclude("Net/NNTP/Client.php"), "PEAR Net/NNTP package cannot be found"); ?> </td> </tr>
		<tr> <td> NNTP server </td> <td> <?php showResult(empty($settings['nntp_nzb']['host']) === false, "No server entered"); ?>  </td> </tr>
	</table>

	<br> <br>

	<table>
		<tr> <th> OpenSSL config  </th> <th> OK ? </th> </tr>
		<tr> <td> OpenSSL PHP extension</td> <td> <?php showResult(array_search('openssl', $extList) !== false); ?>  </td> </tr>
		<tr> <td> Can create private key? </td> <td> <?php require_once "lib/SpotSigning.php"; $spotSigning = new SpotSigning(); $privKey = $spotSigning->createPrivateKey($settings['openssl_cnf_path']); showResult(isset($privKey['public']) && !empty($privKey['public']) && !empty($privKey['private'])); ?></th> </tr>
	</table>
	
	<br> <br>

	<table>
		<tr> <th> Path </th> <th> PEAR found? </th> <th> Net/NNTP found? </th> <tr>
		
<?php
		$arInclude = explode(":", ini_get("include_path")); 
		for($i = 0; $i < count($arInclude); $i++) {
			echo "\t\t<tr><td>" . $arInclude[$i] . "</td> <td> " . 
						(file_exists($arInclude[$i] . 'System.php') ? "OK" : "") . "</td> <td>" .
						(file_exists($arInclude[$i] . "Net/NNTP/Client.php") ? "OK" : "") . " </td> </tr>";
		} # foreach
?>  
	</table>
<br>
<table>
<?php
	switch ($settings['nzbhandling']['action'] )
	{
		case "save":
		case "runcommand":
		case "push-sabnzbd":
			echo "<td><b>NZB local download enabled</b></td>";
			echo "<tr><td>NZB action: </td><td>" . $settings['nzbhandling']['action'] . "</td></tr>";
			echo "<tr><td>NZB directory: </td><td>" .$settings['nzbhandling']['local_dir'] ."</td></tr>";
			echo "<tr><td>Directory access: </td><td>";
			$TestFileName = $settings['nzbhandling']['local_dir'] ."testFile.txt";
			$TestFileHandle = fopen($TestFileName, 'w') or die("Cannot create file</td></tr>");
			showResult(true);
			echo "</td></tr>";
			fclose($TestFileHandle);
			unlink($TestFileName);
			break;
	}
?>
</table>

	</body>
</html>
