<?php
if (!empty($loginresult)) {
	if ($loginresult['result'] != 'success') {
		var_dump($formmessages);
	} else {
		$tplHelper->redirect($tplHelper->makeBaseUrl('')); 

		return ;
	} # if
} # if

if (($currentSession['user']['userid'] == $settings->get('nonauthenticated_userid')) && (empty($loginresult))) {
	require __DIR__ . '/includes/form-messages.inc.php'; 

?>
<!DOCTYPE html> 
<html> 
	<head>
	<title>Inloggen</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.0-rc.1/jquery.mobile-1.1.0-rc.1.min.css" />
	<script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
	<script src="http://code.jquery.com/mobile/1.1.0-rc.1/jquery.mobile-1.1.0-rc.1.min.js"></script>
</head> 
<body>


<div data-role="page" id="login" data-theme="b" data-backbtn="false">

	<div data-role="header">
		<h1>Spotweb</h1>
	</div><!-- /header -->
<br><!-- Return -->
<form class="loginform" name="loginform" action="<?php echo $tplHelper->makeLoginAction(); ?>" method="post">
	<input type="hidden" name="loginform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('loginform'); ?>">
	<?php if (isset($data['performredirect'])) {?>
		<input type="hidden" name="data[performredirect]" value="<?php echo $data['performredirect']; ?>">
	<?php } ?> 
	<fieldset>
    	<label for="loginform[username]"><div align="center"><strong>Gebruikersnaam</strong></div></label>
		<div align="center"><input type="text" style="width:66%; text-align:center"; name="loginform[username]" value="<?php echo htmlspecialchars($loginform['username']); ?>"></div>
<br><!-- Return -->
        <label for="loginform[password]"><div align="center"><strong>Wachtwoord</strong></div></label>
		<div align="center"><input type="password" style="width:66%; text-align:center"; name="loginform[password]" value=""></div>

    	<div align="center"><input class="Button" type="submit" name="loginform[submitlogin]" value="Inloggen" data-inline="true" data-theme="b"></div>
	</fieldset>
</form>
<?php
	}
?>
</div><!-- /page -->

</body>
</html>