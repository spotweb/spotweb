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
			<dt><label for="createuserform[username]"><?php echo _('Gebruikersnaam'); ?></label></dt>
			<dd><input type="text" name="createuserform[username]" value="<?php echo htmlspecialchars($createuserform['username']); ?>"></dd>

			<dt><label for="createuserform[firstname]"><?php echo _('Voornaam'); ?></label></dt>
			<dd><input type="text" name="createuserform[firstname]" value="<?php echo htmlspecialchars($createuserform['firstname']); ?>"></dd>

			<dt><label for="createuserform[lastname]"><?php echo _('Achternaam'); ?></label></dt>
			<dd><input type="text" name="createuserform[lastname]" value="<?php echo htmlspecialchars($createuserform['lastname']); ?>"></dd>
			
			<dt><label for="createuserform[mail]"><?php echo _('E-mailadres'); ?></label></dt>
			<dd><input type="text" name="createuserform[mail]" value="<?php echo htmlspecialchars($createuserform['mail']); ?>"></dd>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'email') && !$this->_settings->get('sendwelcomemail')) { ?>
			<dt><label for="createuserform[sendmail]"><?php echo _('E-mail versturen naar nieuwe gebruiker?'); ?></label></dt>
			<dd><input type="checkbox" name="createuserform[sendmail]"></dd>
<?php } ?>
			<dd><input class="greyButton" type="submit" name="createuserform[submit]" value="<?php echo _('Toevoegen'); ?>"></dd>
		</dl>
	</fieldset>
</form>

<?php
} # if