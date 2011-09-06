<?php
	require_once "lib/SpotClassAutoload.php";
	@include('settings.php');
	set_error_handler("ownWarning",E_WARNING);
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
	<tr> <td> PHP version </td> <td> <?php echo phpversion(); ?> </td> <td> <?php showResult((version_compare(PHP_VERSION, '5.3.0') >= 0), "", "PHP 5.3 or later is recommended"); ?> </td> </tr>
	<tr> <td> timezone settings </td> <td> <?php echo ini_get("date.timezone"); ?> </td> <td> <?php showResult(ini_get("date.timezone"), "", "Please specify date.timezone in your PHP.ini"); ?> </td> </tr>
	<tr> <td> Open base dir </td> <td> <?php echo ini_get("open_basedir"); ?> </td> <td> <?php showResult(!ini_get("open_basedir"), "", "Niet leeg, <strong>kan</strong> een probleem zijn"); ?> </td> </tr>
	<tr> <td> Allow furl open </td> <td> <?php echo ini_get("allow_url_fopen"); ?> </td> <td> <?php showResult(ini_get("allow_url_fopen") == 1, "", "allow_url_fopen not on -- will cause problems to retrieve external data"); ?> </td> </tr>
	<tr> <td> PHP safe mode </td> <td> <?php echo ini_get("safe_mode"); ?> </td> <td> <?php showResult(!ini_get("safe_mode"), "", "Safe mode set -- will cause problems for retrieve.php"); ?> </td> </tr>
	<tr> <td> Memory limit </td> <td> <?php echo ini_get("memory_limit"); ?> </td> <td> <?php showResult(return_bytes(ini_get("memory_limit")) >= (32*1024*1024), "", "memory_limit below 32M"); ?> </td> </tr>
</table>
<br />

<table summary="PHP extensions">
	<tr> <th colspan="2"> PHP extension </th> <th> Info </th> <th> Result </th> </tr>
	<tr> <td colspan="2"> DB::<?php echo $settings['db']['engine']; ?> </td> <td> </td> <td> <?php showResult(extension_loaded($settings['db']['engine'])); ?> </td> </tr>
	<tr> <td colspan="2"> ctype </td> <td> </td> <td> <?php showResult(extension_loaded('ctype')); ?> </td> </tr>
	<tr> <td colspan="2"> curl </td> <td> Notifo & Prowl </td> <td> <?php showResult(extension_loaded('curl')); ?> </td> </tr>
	<tr> <td colspan="2"> DOM </td> <td> </td> <td> <?php showResult(extension_loaded('dom')); ?> </td> </tr>
	<tr> <td colspan="2"> GD </td> <td> Opera Speed Dial </td> <td> <?php showResult(extension_loaded('gd')); ?> </td> </tr>
	<tr> <td colspan="2"> xml </td> <td> </td> <td> <?php showResult(extension_loaded('xml')); ?> </td> </tr>
	<tr> <td colspan="2"> zip </td> <td> NZB files comprimeren </td> <td> <?php showResult(extension_loaded('zip')); ?> </td> </tr>
	<tr> <td colspan="2"> zlib </td> <td> </td> <td> <?php showResult(extension_loaded('zlib')); ?> </td> </tr>
	<tr> <th colspan="3"> OpenSSL </th> </tr>
<?php require_once "lib/SpotSigning.php";
	$spotSigning = new SpotSigning();
	$privKey = $spotSigning->createPrivateKey($settings['openssl_cnf_path']);
?>	<tr> <td rowspan="3"> Minimaal 1 moet OK zijn<br />In volgorde van snelste naar langzaamste </td> <td> openssl </td> <td> </td> <td> <?php showResult(extension_loaded('openssl')); ?> </td> </tr>
	<tr> <td> gmp </td> <td> </td> <td> <?php showResult(extension_loaded('gmp')); ?> </td> </tr>
	<tr> <td> bcmath </td> <td> </td> <td> <?php showResult(extension_loaded('bcmath')); ?> </td> </tr>
	<tr> <td colspan="2"> Can create private key? </td> <td> </td> <td> <?php showResult(isset($privKey['public']) && !empty($privKey['public']) && !empty($privKey['private'])); ?> </td> </tr>
</table>
<br />

<table summary="Server settings">
	<tr> <th> Server type </th> <th> Setting </th> </tr>
	<?php if ($settings['db']['engine'] == "pdo_sqlite") { ?>
	<tr> <td> SQLite </td> <td> <?php showResult(empty($settings['db']['path']) === false, $settings['db']['path'], "No path entered"); ?> </td> </tr>
	<?php } elseif ($settings['db']['engine'] == "mysql" || $settings['db']['engine'] == "pdo_mysql" || $settings['db']['engine'] == "pdo_pgsql" ) { ?>
	<tr> <td> MySQL server </td> <td> <?php showResult(empty($settings['db']['host']) === false, $settings['db']['host'], "No server entered"); ?> </td> </tr>
	<?php } else { ?>
	<tr> <td> Database </td> <td> NOT OK (No valid database engine given) </td> </tr>
	<?php } ?>
	<tr> <td> NNTP server </td> <td> <?php showResult(empty($settings['nntp_nzb']['host']) === false, $settings['nntp_nzb']['host'], "No server entered"); ?> </td> </tr>
	<?php if ($settings['nntp_nzb'] != $settings['nntp_hdr']) { ?>
	<tr> <td> NNTP server (headers) </td> <td> <?php showResult(empty($settings['nntp_hdr']['host']) === false, $settings['nntp_hdr']['host'], "No server entered"); ?> </td> </tr>
	<?php }
	if ($settings['nntp_nzb'] != $settings['nntp_post']) { ?>
	<tr> <td> NNTP server (post) </td> <td> <?php showResult(empty($settings['nntp_post']['host']) === false, $settings['nntp_post']['host'], "No server entered"); ?> </td> </tr>
	<?php } ?>
</table>
<br />

<table summary="Include files">
	<tr> <th> Include files  </th> <th> Result </th> </tr>
	<tr> <td> Settings file </td> <td> <?php $result=testInclude("settings.php"); echo showResult($result, $result); ?> </td> </tr>
	<tr> <td> Own settings file </td> <td> <?php $result=testInclude("ownsettings.php"); echo showResult($result, $result, "optioneel"); ?> </td> </tr>
</table>
<br />

</body>
</html><?php
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
	} # return_bytes

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