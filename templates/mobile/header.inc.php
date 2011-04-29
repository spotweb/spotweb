<!DOCTYPE html> 
<html>
	<head>

		<link rel="apple-touch-icon" href="images/touch-icon-iphone.png" />
		<link rel="apple-touch-icon" sizes="72x72" href="images/touch-icon-ipad.png" />
		<link rel="apple-touch-icon" sizes="114x114" href="images/touch-icon-iphone4.png" />
		<link rel="apple-touch-startup-image" href="images/startup.png">  
		<meta name="apple-mobile-web-app-capable" content="yes" />  
		<meta name="apple-mobile-web-app-status-bar-style" content="black" /> 
		<title><?php echo $pagetitle?></title>
		<link rel='stylesheet' type='text/css' href='js/jquery.mobile-1.0a3/jquery.mobile-1.0a3.min.css'>
		<link rel='icon' href='images/favicon.ico'>
		<?php if (extension_loaded('gd') && function_exists('gd_info') && @$_SERVER['HTTP_X_PURPOSE'] == 'preview') { echo "<link rel='icon' type='image/png' href='?page=speeddial'>" . PHP_EOL; } ?>
		<script src='js/jquery/jquery.min.js' type='text/javascript'></script>
		<script src='js/jquery.mobile-1.0a3/jquery.mobile-1.0a3.min.js' type='text/javascript'></script>
		<style>
		th{text-align:left;}
		</style>
		<script type='text/javascript'>
		$(function(){ });
		</script>
	</head>
<body>



