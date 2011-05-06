<?php
	@include('settings.php');
	set_error_handler("ownWarning",E_WARNING);
	$extList = get_loaded_extensions();
	$phpVersion = explode(".", phpversion());
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
   <html>
	<head>
		<title>Install ...</title>
		<style type='text/css'>
			table {margin-left:auto; margin-right:auto; font-size:12px; color:#fff; width:800px; background-color:#666; border:0px; border-collapse:collapse; border-spacing:0px;}
			table td {background-color:#CCC; color:#000; padding:4px; border:1px #fff solid;}
			table th {background-color:#666; color:#fff; padding:4px; text-align:left; border-bottom:2px #fff solid; font-size:12px; font-weight:bold;} 
		</style>
	</head>
	<body>

	<table summary="PHP settings">
		<tr> <th> PHP settings </th> <th> Value </th> <th> Result </th> </tr>
		<tr> <td> PHP version </td> <td> <?php echo phpversion(); ?> </td> <td> <?php showResult(($phpVersion[0] >= '5' && $phpVersion[1] >= 3), "", "PHP 5.3 or later is recommended"); ?>  </td> </tr>
		<tr> <td> timezone settings </td> <td> <?php echo ini_get("date.timezone"); ?> </td> <td> <?php showResult(ini_get("date.timezone"), "", "Please specify date.timezone in your PHP.ini"); ?> </td> </tr>
		<tr> <td> Open base dir </td> <td> <?php echo ini_get("open_basedir"); ?> </td> <td> <?php showResult(!ini_get("open_basedir"), "", "Niet leeg, <strong>kan</strong> een probleem zijn"); ?>  </td> </tr>
		<tr> <td> Allow furl open </td> <td> <?php echo ini_get("allow_url_fopen"); ?> </td> <td> <?php showResult(ini_get("allow_url_fopen") == 1, "", "allow_url_fopen not on -- will cause problems to retrieve external data"); ?> </td> </tr>
		<tr> <td> PHP safe mode </td> <td> <?php echo ini_get("safe_mode"); ?> </td> <td> <?php showResult(!ini_get("safe_mode"), "", "Safe mode set -- will cause problems for retrieve.php"); ?> </td> </tr>
		<tr> <td> Memory limit </td> <td> <?php echo ini_get("memory_limit"); ?> </td> <td> <?php showResult(return_bytes(ini_get("memory_limit")) >= (32*1024*1024), "", "memory_limit below 32M"); ?> </td> </tr>
	</table>
	
	<br />
	
	<table summary="PHP extensions">
		<tr> <th> PHP extension </th> <th> Result </th> </tr>
		<tr> <td> <?php echo $settings['db']['engine']; ?> </td> <td> <?php showResult(array_search($settings['db']['engine'], $extList) !== false, "", ""); ?> </td> </tr>
		<tr> <td> OpenSSL<br />Minimaal 1 moet OK zijn<br />In volgorde van snelste naar langzaamste<hr />Can create private key? </td> <td> <?php echo "openssl: "; showResult(array_search('openssl', $extList) !== false); echo "<br />";
			echo "gmp: "; showResult(array_search('gmp', $extList) !== false); echo "<br />";
			echo "bcmath: "; showResult(array_search('bcmath', $extList) !== false); echo "<hr />";
			require_once "lib/SpotSigning.php"; $spotSigning = new SpotSigning(); $privKey = $spotSigning->createPrivateKey($settings['openssl_cnf_path']); showResult(isset($privKey['public']) && !empty($privKey['public']) && !empty($privKey['private'])); ?> </td> </tr>
		<tr> <td> ctype </td> <td> <?php showResult(array_search('ctype', $extList) !== false); ?> </td> </tr>
		<tr> <td> xml </td> <td> <?php showResult(array_search('xml', $extList) !== false); ?> </td> </tr>
		<tr> <td> zlib </td> <td> <?php showResult(array_search('zlib', $extList) !== false); ?> </td> </tr>
		<tr> <td> GD </td> <td> <?php showResult(array_search('gd', $extList) !== false); ?> </td> </tr>
	</table>

	<br />
	
	<table summary="Include files">
		<tr> <th> Include files  </th> <th> Result </th> </tr>
		<tr> <td> Settings file </td> <td> <?php echo showResult(testInclude("settings.php"), testInclude("settings.php")); ?>  </td> </tr>
		<tr> <td> Own settings file </td> <td> <?php echo showResult(testInclude("ownsettings.php"), testInclude("ownsettings.php"), "optioneel"); ?>  </td> </tr>
		<tr> <td> PEAR </td> <td> <?php echo showResult(testInclude("System.php"), testInclude("System.php")); ?>  </td> </tr>
		<?php if (PHP_OS == "WIN32" || PHP_OS == "WINNT") { ?>
			<tr> <td> PEAR Net_NNTP </td> <td> <?php echo showResult(testInclude("Net\\NNTP\\Client.php"), testInclude("Net\\NNTP\\Client.php")); ?>  </td> </tr>
		<?php } else { ?>
			<tr> <td> PEAR Net_NNTP </td> <td> <?php echo showResult(testInclude("Net/NNTP/Client.php"), testInclude("Net/NNTP/Client.php")); ?>  </td> </tr>
		<?php } ?>
		<tr> <td> NNTP server </td> <td> <?php showResult(empty($settings['nntp_nzb']['host']) === false, $settings['nntp_nzb']['host'], "No server entered"); ?>  </td> </tr>
	</table>

	<br />

	<table summary="NZB handling">
<?php
	switch ($settings['nzbhandling']['action'] )
	{
		case "save":
		case "runcommand":
		case "push-sabnzbd":
			echo "<tr> <th> NZB local download enabled </th> <th> Value </th> </tr>";
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
<?php
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
	
	function showResult($b, $okMsg="", $nokMsg="") {
		if ($b) {
			echo "OK";

			if (!empty($okMsg)) {
				echo ' (' . $okMsg . ')';
			} 
		} else {
			echo "NOT OK";

			if (!empty($nokMsg)) {
				echo ' (' . $nokMsg . ')';
			} 
		} # else
	} # showResult

	function ownWarning($errno, $errstr) {
		$GLOBALS['iserror'] = true;
		#echo $errstr;
	} # ownWarning

	function testInclude($fname) {
		@include_once($fname);
		foreach (get_included_files() as $filename) {
			if (strpos($filename, $fname, strlen($filename) - strlen($fname)) !== false) {
				return dirname($filename);
			}
		}
	} # testInclude
