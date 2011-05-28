<?php
if (!empty($loginresult)) {
	include 'includes/form-xmlresult.inc.php';
	
	echo formResult2Xml($loginresult, $formmessages, $tplHelper);
} # if

if (($currentSession['user']['userid'] == SPOTWEB_ANONYMOUS_USERID) && (empty($loginresult))) {
	include "includes/form-messages.inc.php"; 

?>
<form class="loginform" name="loginform" action="<?php echo $tplHelper->makeLoginAction(); ?>" method="post">
	<input type="hidden" name="loginform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('loginform'); ?>">
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
