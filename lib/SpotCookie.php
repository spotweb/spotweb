<?php
if(empty($_SESSION['last_visit'])) {
	if(!isset($_COOKIE['last_visit'])) {
		$_SESSION['last_visit'] = false;
	} else {
		$_SESSION['last_visit'] = $_COOKIE['last_visit'];
	}
}
// set cookie
setcookie('last_visit', time(), time()+(86400*$settings['cookie_expires']), '/', $settings['cookie_host']);
?>
