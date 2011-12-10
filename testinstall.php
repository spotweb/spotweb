<?php
	require_once "lib/SpotClassAutoload.php";
	@include('settings.php');
	set_error_handler("ownWarning",E_WARNING);
	
	/*
	 * We default to a succeeded install, let it prove
	 * otherwise
	 */
	global $_testInstall_Ok;

	$_testInstall_Ok = true;
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Test your Installation</title>
	<style type='text/css'>
		table {margin-left:auto; margin-right:auto; font-size:12px; color:#fff; width:800px; background-color:#666; border:0px; border-collapse:collapse; border-spacing:0px;}
		table td {background-color:#CCC; color:#000; padding:4px; border:1px #fff solid;}
		table th {background-color:#666; color:#fff; padding:4px; text-align:left; border-bottom:2px #fff solid; font-size:12px; font-weight:bold;} 
	</style>
</head>
<body>

<?php
function performAndPrintTests() {
	global $settings;
	global $_testInstall_Ok;
?>
	<table summary="PHP settings">
		<tr> <th> PHP settings </th> <th> Value </th> <th> Result </th> </tr>
		<tr> <td> PHP version </td> <td> <?php echo phpversion(); ?> </td> <td> <?php showResult((version_compare(PHP_VERSION, '5.3.0') >= 0), true, "", "PHP 5.3 or later is recommended"); ?> </td> </tr>
		<tr> <td> timezone settings </td> <td> <?php echo ini_get("date.timezone"); ?> </td> <td> <?php showResult(ini_get("date.timezone"), true, "", "Please specify date.timezone in your PHP.ini"); ?> </td> </tr>
		<tr> <td> Open base dir </td> <td> <?php echo ini_get("open_basedir"); ?> </td> <td> <?php showResult(!ini_get("open_basedir"), true, "", "Not empty, <strong>might</strong> be a problem"); ?> </td> </tr>
		<tr> <td> Allow furl open </td> <td> <?php echo ini_get("allow_url_fopen"); ?> </td> <td> <?php showResult(ini_get("allow_url_fopen") == 1, true, "", "allow_url_fopen not on -- will cause problems to retrieve external data"); ?> </td> </tr>
		<tr> <td> PHP safe mode </td> <td> <?php echo ini_get("safe_mode"); ?> </td> <td> <?php showResult(!ini_get("safe_mode"), true, "", "Safe mode set -- will cause problems for retrieve.php"); ?> </td> </tr>
		<tr> <td> Memory limit </td> <td> <?php echo ini_get("memory_limit"); ?> </td> <td> <?php showResult(return_bytes(ini_get("memory_limit")) >= (128*1024*1024), true, "", "memory_limit below 128M"); ?> </td> </tr>
	</table>
	<br />

	<table summary="PHP extensions">
		<tr> <th colspan="2"> PHP extension </th> <th> Result </th> </tr>
		<tr> <td colspan="2"> DB::<?php echo $settings['db']['engine']; ?> </td> <td> <?php showResult(extension_loaded($settings['db']['engine']), true); ?> </td> </tr>
		<tr> <td colspan="2"> ctype </td> <td> <?php showResult(extension_loaded('ctype'), true); ?> </td> </tr>
		<tr> <td colspan="2"> curl </td> <td> <?php showResult(extension_loaded('curl'), true); ?> </td> </tr>
		<tr> <td colspan="2"> DOM </td> <td> <?php showResult(extension_loaded('dom'), true); ?> </td> </tr>
		<tr> <td colspan="2"> gettext </td> <td> <?php showResult(extension_loaded('gettext'), false); ?> </td> </tr>
		<tr> <td colspan="2"> mbstring </td> <td> <?php showResult(extension_loaded('mbstring'), true); ?> </td> </tr>
		<tr> <td colspan="2"> xml </td> <td> <?php showResult(extension_loaded('xml'), true); ?> </td> </tr>
		<tr> <td colspan="2"> zip </td> <td> <?php showResult(extension_loaded('zip'), false, "", "You need this module to select multiple NZB files"); ?> </td> </tr>
		<tr> <td colspan="2"> zlib </td> <td> <?php showResult(extension_loaded('zlib'), true); ?> </td> </tr>
	<?php if (extension_loaded('gd')) $gdInfo = gd_info(); ?>
		<tr> <th colspan="2"> GD </th> <td> <?php showResult(extension_loaded('gd'), true); ?> </td> </tr>
		<tr> <td colspan="2"> FreeType Support </td> <td> <?php showResult($gdInfo['FreeType Support'], true); ?> </td> </tr>
		<tr> <td colspan="2"> GIF Read Support </td> <td> <?php showResult($gdInfo['GIF Read Support'], true); ?> </td> </tr>
		<tr> <td colspan="2"> GIF Create Support </td> <td> <?php showResult($gdInfo['GIF Create Support'], true); ?> </td> </tr>
		<tr> <td colspan="2"> JPEG Support </td> <td> <?php showResult($gdInfo['JPEG Support'] || $gdInfo['JPG Support'], true); ?> </td> </tr> <!-- Previous to PHP 5.3.0, the JPEG Support attribute was named JPG Support. -->
		<tr> <td colspan="2"> PNG Support </td> <td> <?php showResult($gdInfo['PNG Support'], true); ?> </td> </tr>
		<tr> <th colspan="3"> OpenSSL </th> </tr>
	<?php require_once "lib/SpotSigning.php";
		$spotSigning = new SpotSigning();
		$privKey = $spotSigning->createPrivateKey($settings['openssl_cnf_path']);
		
		/* We need either one of those 3 extensions, so set the error flag manually */
		if ( (!extension_loaded('openssl')) && (!extension_loaded('openssl')) && (!extension_loaded('openssl'))) {
			$_testInstall_Ok = false;
		} # if
		
	?>	<tr> <td rowspan="3"> At least 1 of these must be OK <br />these modules are sorted from fastest to slowest</td> <td> openssl </td> <td> <?php showResult(extension_loaded('openssl'), false); ?> </td> </tr>
		<tr> <td> gmp </td> <td> <?php showResult(extension_loaded('gmp'), false); ?> </td> </tr>
		<tr> <td> bcmath </td> <td> <?php showResult(extension_loaded('bcmath'), false); ?> </td> </tr>
		<tr> <td colspan="2"> Can create private key? </td> <td> <?php showResult(isset($privKey['public']) && !empty($privKey['public']) && !empty($privKey['private']), true); ?> </td> </tr>
	</table>
	<br />

	<table summary="Server settings">
		<tr> <th> Server type </th> <th> Setting </th> </tr>
		<?php if ($settings['db']['engine'] == "pdo_sqlite") { ?>
		<tr> <td> SQLite </td> <td> <?php showResult(empty($settings['db']['path']) === false, true, $settings['db']['path'], "No path entered"); ?> </td> </tr>
		<?php } elseif ($settings['db']['engine'] == "mysql" || $settings['db']['engine'] == "pdo_mysql" || $settings['db']['engine'] == "pdo_pgsql" ) { ?>
		<tr> <td> MySQL server </td> <td> <?php showResult(empty($settings['db']['host']) === false, true, $settings['db']['host'], "No server entered"); ?> </td> </tr>
		<?php } else { ?>
		<tr> <td> Database </td> <td> NOT OK (No valid database engine given) </td> </tr>
		<?php } ?>
	</table>
	<br />

	<table summary="Include files">
		<tr> <th> Include files  </th> <th> Result </th> </tr>
		<tr> <td> Settings file </td> <td> <?php $result=testInclude("settings.php"); echo showResult($result, true, $result); ?> </td> </tr>
		<tr> <td> Own settings file </td> <td> <?php $result=testInclude("ownsettings.php"); echo showResult($result, true, $result, "optional"); ?> </td> </tr>
	</table>
	<br />

	</body>
	</html>
<?php
	} # performAndPrintTests
	
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

	function showResult($b, $isRequired, $okMsg="", $nokMsg="") {
		global $_testInstall_Ok;
		
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
			
			if ($isRequired) {
				$_testInstall_Ok = true;
			} # if
		} # else
		
		return null;
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

	performAndPrintTests();