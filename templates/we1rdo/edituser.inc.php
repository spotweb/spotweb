<?php 

if (!empty($createresult)) {
	include 'includes/form-xmlresult.inc.php';
	
	echo formResult2Xml($createresult, $formmessages, $tplHelper);
} # if

if (empty($createresult)) {
	include "includes/form-messages.inc.php";

?>
<form class="edituserform" name="edituserform" action="<?php echo $tplHelper->makeEditUserAction(); ?>" method="post">
	<input type="hidden" name="edituserform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('edituserform'); ?>">
	<input type="hidden" name="edituserform[action]" value="edit">
	<input type="hidden" name="userid" value="<?php echo $edituserform['userid']; ?>">
    <fieldset>
        <dl>
            <dt><label for="edituserform[username]">Gebruikersnaam</label></dt>
            <dd><?php echo htmlspecialchars($edituserform['username']); ?></dd>

            <dt><label for="edituserform[username]">Wachtwoord</label></dt>
            <dd><input type="text" name="edituserform[newpassword1]" value=""></dd>

            <dt><label for="edituserform[username]">Wachtwoord (bevestig)</label></dt>
            <dd><input type="text" name="edituserform[newpassword2]" value=""></dd>

            <dt><label for="edituserform[firstname]">Voornaam</label></dt>
            <dd><input type="text" name="edituserform[firstname]" value="<?php echo htmlspecialchars($edituserform['firstname']); ?>"></dd>

            <dt><label for="edituserform[lastname]">Achternaam</label></dt>
            <dd><input type="text" name="edituserform[lastname]"  value="<?php echo htmlspecialchars($edituserform['lastname']); ?>"></dd>
            
            <dt><label for="edituserform[mail]">Mailadres</label></dt>
            <dd><input type="text" name="edituserform[mail]"  value="<?php echo htmlspecialchars($edituserform['mail']); ?>"></dd>

			<dd><input class="greyButton" type="submit" name="edituserform[submit]" value="Bijwerken"></dd>
		</dl>
	</fieldset>
</form>
<?php
	}
?>
