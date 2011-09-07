<?php
if (!empty($loginresult)) {
	if ((!isset($data['performredirect'])) || ($loginresult['result'] != 'success')) {
		var_dump($formmessages);
	} else {
		$tplHelper->redirect($loginform['http_referer']);
	} # if
} # if

if (($currentSession['user']['userid'] == $settings->get('nonauthenticated_userid')) && (empty($loginresult))) {
	include "includes/form-messages.inc.php"; 

?>
<form class="loginform" name="loginform" action="<?php echo $tplHelper->makeLoginAction(); ?>" method="post">
	<input type="hidden" name="loginform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('loginform'); ?>">
	<?php if (isset($data['performredirect'])) {?>
		<input type="hidden" name="data[performredirect]" value="<?php echo $data['performredirect']; ?>">
	<?php } ?>
	<fieldset>
		<dl>
			<dt><label for="loginform[username]">Gebruikersnaam</label></dt>
			<dd><input type="text" name="loginform[username]" value="<?php echo htmlspecialchars($loginform['username']); ?>"></dd>

			<dt><label for="loginform[password]">Wachtwoord</label></dt>
			<dd><input type="password" name="loginform[password]" value=""></dd>

			<dd><input class="greyButton" type="submit" name="loginform[submit]" value="Inloggen"></dd>
		</dl>
	</fieldset>
</form>
<?php
	}
?>
