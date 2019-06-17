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
    <link rel="stylesheet" href="js/jquery.mobile-1.5.0-rc1/jquery.mobile-1.5.0-rc1.css" />
    <script src="js/jquery/jquery.js"></script>
    <script src="js/jquery.mobile-1.5.0-rc1/jquery.mobile-1.5.0-rc1.js"></script>
  </head>
<body>
<div data-role="page" id="login">
  <div data-role="toolbar" data-type="header" data-theme="b">
    <h1>Spotweb</h1>
  </div>
  <div data-role="main" class="ui-content">
    <form class="loginform" name="loginform" action="<?php echo $tplHelper->makeLoginAction(); ?>" method="post">
      <input type="hidden" name="loginform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('loginform'); ?>">
      <?php if (isset($data['performredirect'])) {?>
      <input type="hidden" name="data[performredirect]" value="<?php echo $data['performredirect']; ?>">
      <?php } ?>
        <div align="center">
          <fieldset data-role="controlgroup">
            <label for="loginform[username]"><strong>Gebruikersnaam</strong></label>
            <input style="text-align: center" type="text" name="loginform[username]" value="<?php echo htmlspecialchars($loginform['username']); ?>">
          </fieldset>
        </div>
        <div align="center">
          <fieldset data-role="controlgroup">
            <label for="loginform[password]"><strong>Wachtwoord</strong></label>
            <input style="text-align: center" type="password" name="loginform[password]" value="">
          </fieldset>
        </div>
        <div align="center">
          <input class="Button" type="submit" name="loginform[submitlogin]" value="Inloggen" data-inline="true">
        <div>
    </form>
  </div>
<?php
	}
?>
</div>
</body>
</html>
