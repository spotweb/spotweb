<html>
	<head>
		<title>Install ...</title>
		<style type='text/css'>
		</style>
	</head>

	<body>

<?php
	$extList = get_loaded_extensions();
?>

	<table>
		<tr> <th> PHP extension </th> <th> OK ? </th> </tr>

		<tr> <td> SQLite </td> <td> <?php echo (array_search('SQLite', $extList) === false) ? "Not installed" : "OK" ?>  </td> </tr>
		<tr> <td> bcmath </td> <td> <?php echo (array_search('bcmath', $extList) === false) ? "Not installed" : "OK" ?> </td> </tr>
		<tr> <td> ctype </td> <td> <?php echo (array_search('ctype', $extList) === false) ? "Not installed" : "OK" ?> </td> </tr>
		<tr> <td> xml </td> <td> <?php echo (array_search('xml', $extList) === false) ? "Not installed" : "OK" ?> </td> </tr>
		<tr> <td> zlib </td> <td> <?php echo (array_search('zlib', $extList) === false) ? "Not installed" : "OK" ?> </td> </tr>

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
		<tr> <td> Settings file </td> <td> <?php echo testInclude("settings.php") ? "OK" : "settings.php cannot be read" ?>  </td> </tr>
		<tr> <td> PEAR Net/NNTP </td> <td> <?php echo testInclude("Net/NNTP/Client.php") ? "OK" : "PEAR Net/NNTP package cannot be found" ?> </td> </tr>
		<tr> <td> NNTP server </td> <td> <?php echo (!empty($settings['nntp_nzb']['host']) === false) ? "No server entered" : "OK" ?>  </td> </tr>
	</table>
	
	</body>
</html>
