<?php 

if (!empty($createresult)) {
	include 'includes/form-xmlresult.inc.php';
	
	echo formResult2Xml($createresult, $formmessages, $tplHelper);
} # if

if (empty($createresult)) {
	include "includes/form-messages.inc.php";

?>
<form class="createuserform" name="createuserform" action="<?php echo $tplHelper->makeCreateUserAction(); ?>" method="post">
	<input type="hidden" name="createuserform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('createuserform'); ?>">
	<fieldset>
		<dl>
			<dt><label for="createuserform[username]">Gebruikersnaam</label></dt>
			<dd><input type="text" name="createuserform[username]" value="<?php echo htmlspecialchars($createuserform['username']); ?>"></dd>

			<dt><label for="createuserform[firstname]">Voornaam</label></dt>
			<dd><input type="text" name="createuserform[firstname]" value="<?php echo htmlspecialchars($createuserform['firstname']); ?>"></dd>

			<dt><label for="createuserform[lastname]">Achternaam</label></dt>
			<dd><input type="text" name="createuserform[lastname]" value="<?php echo htmlspecialchars($createuserform['lastname']); ?>"></dd>
			
			<dt><label for="createuserform[mail]">E-mailadres</label></dt>
			<dd><input type="email" name="createuserform[mail]" value="<?php echo htmlspecialchars($createuserform['mail']); ?>"></dd>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'email') && !$this->_settings->get('sendwelcomemail')) { ?>
			<dt><label for="createuserform[sendmail]">E-mail versturen naar nieuwe gebruiker?</label></dt>
			<dd><input type="checkbox" name="createuserform[sendmail]"></dd>
<?php } ?>
			<dd><input class="greyButton" type="submit" name="createuserform[submit]" value="Toevoegen"></dd>
		</dl>
	</fieldset>
</form>

<?php
} # if