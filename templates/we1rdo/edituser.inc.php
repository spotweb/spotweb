<?php
if (!empty($editresult)) {
	include 'includes/form-xmlresult.inc.php';

	echo formResult2Xml($editresult, $formmessages, $tplHelper);
} # if

if (empty($editresult)) {
	include "includes/form-messages.inc.php";
?>
<form class="edituserform" name="edituserform" action="<?php echo $tplHelper->makeEditUserAction(); ?>" method="post">
	<input type="hidden" name="edituserform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('edituserform'); ?>">
	<input type="hidden" name="edituserform[buttonpressed]" value="">
	<input type="hidden" name="userid" value="<?php echo $edituserform['userid']; ?>">
	<fieldset>
		<dl>
			<dt><label for="edituserform[username]">Gebruikersnaam</label></dt>
			<dd><input type="text" disabled="disabled" value="<?php echo htmlspecialchars($edituserform['username']); ?>"></dd>

			<dt><label for="edituserform[newpassword1]">Wachtwoord</label></dt>
			<dd><input type="password" name="edituserform[newpassword1]" value=""></dd>

			<dt><label for="edituserform[newpassword2]">Wachtwoord (bevestig)</label></dt>
			<dd><input type="password" name="edituserform[newpassword2]" value=""></dd>

			<dt><label for="edituserform[firstname]">Voornaam</label></dt>
			<dd><input type="text" name="edituserform[firstname]" value="<?php echo htmlspecialchars($edituserform['firstname']); ?>"></dd>

			<dt><label for="edituserform[lastname]">Achternaam</label></dt>
			<dd><input type="text" name="edituserform[lastname]"  value="<?php echo htmlspecialchars($edituserform['lastname']); ?>"></dd>

			<dt><label for="edituserform[mail]">E-mail Adres</label></dt>
			<dd><input type="text" name="edituserform[mail]"  value="<?php echo htmlspecialchars($edituserform['mail']); ?>"></dd>

			<dt><label for="edituserform[apikey]">API key</label></dt>
			<dd><input class="withicon" type="text" readonly="readonly" value="<?php echo $edituserform['apikey']; ?>">
			<input type="image" src="images/icons/refresh.png" name="edituserform[submitresetuserapi]" value="Nieuwe API key genereren"></dd>

<?php if (($tplHelper->allowed(SpotSecurity::spotsec_edit_groupmembership, '')) || ($tplHelper->allowed(SpotSecurity::spotsec_display_groupmembership, ''))) { ?>
			<!-- Dummy grouplist variable om zeker te zijn dat de grouplist altijd gepost wordt -->
			<input type="hidden" name="edituserform[grouplist][dummy]" value="dummy">
			<table>
				<thead>
					<tr> <th> Group </th> <th> Member </th>
				</thead>
				
				<tbody>
<?php
	foreach($groupMembership as $secGroup) {
?>
					<tr> <td> <?php echo $secGroup['name']; ?> </td> <td> <input <?php if (!$tplHelper->allowed(SpotSecurity::spotsec_edit_groupmembership, '')) { echo "readonly='readonly'"; } ?> type="checkbox" name="edituserform[grouplist][<?php echo $secGroup['id'];?>]" value="<?php echo $secGroup['id'];?>" <?php if ($secGroup['ismember']) { echo 'checked="checked"'; } ?> /></td> </tr>
<?php } ?>

				</tbody>
			</table>
<?php } ?>

			<dd>
				<input class="greyButton" type="submit" name="edituserform[submitedit]" value="Bijwerken">
<?php if ($edituserform['userid'] > SPOTWEB_ADMIN_USERID && $tplHelper->allowed(SpotSecurity::spotsec_delete_user, '')) { ?>
				<input class="greyButton" type="submit" name="edituserform[submitdelete]" value="Wis gebruiker">
<?php } ?>
				<input class="greyButton" type="submit" name="edituserform[removeallsessions]" value="Wis alle sessies">
			</dd>
		</dl>
	</fieldset>
</form>
<?php
}