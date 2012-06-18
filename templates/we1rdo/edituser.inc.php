<?php
if (!empty($editresult)) {
	include 'includes/form-xmlresult.inc.php';
	
	$this->sendContentTypeHeader('xml');
	echo formResult2Xml($editresult, $formmessages, $tplHelper);
} # if

if (empty($editresult)) {
	include "includes/form-messages.inc.php";
?>
<form class="edituserform" name="edituserform" action="<?php echo $tplHelper->makeEditUserAction(); ?>" method="post">
	<input type="hidden" name="edituserform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('edituserform'); ?>">
	<input type="hidden" name="edituserform[buttonpressed]" value="">
	<input type="hidden" name="userid" value="<?php echo htmlspecialchars($edituserform['userid']); ?>">
	<fieldset>
		<dl>
			<dt><label for="edituserform[username]"><?php echo _('Username'); ?></label></dt>
			<dd><input type="text" disabled="disabled" value="<?php echo htmlspecialchars($edituserform['username']); ?>"></dd>

<?php if ($edituserform['userid'] > SPOTWEB_ANONYMOUS_USERID) { ?>
			<dt><label for="edituserform[newpassword1]"><?php echo _('New password'); ?></label></dt>
			<dd><input type="password" name="edituserform[newpassword1]" value=""></dd>

			<dt><label for="edituserform[newpassword2]"><?php echo _('Confirm new password'); ?></label></dt>
			<dd><input type="password" name="edituserform[newpassword2]" value=""></dd>
<?php } else { ?>
			<input type="hidden" name="edituserform[newpassword1]" value="dummyvalue">
			<input type="hidden" name="edituserform[newpassword2]" value="dummyvalue">
<?php } ?>

			<dt><label for="edituserform[firstname]"><?php echo _('Firstname'); ?></label></dt>
			<dd><input type="text" name="edituserform[firstname]" value="<?php echo htmlspecialchars($edituserform['firstname']); ?>"></dd>

			<dt><label for="edituserform[lastname]"><?php echo _('Lastname'); ?></label></dt>
			<dd><input type="text" name="edituserform[lastname]"  value="<?php echo htmlspecialchars($edituserform['lastname']); ?>"></dd>

			<dt><label for="edituserform[mail]"><?php echo _('E-mail Address'); ?></label></dt>
			<dd><input type="text" name="edituserform[mail]"  value="<?php echo htmlspecialchars($edituserform['mail']); ?>"></dd>

<?php if ($edituserform['userid'] > SPOTWEB_ADMIN_USERID) { ?>
			<dt><label for="edituserform[apikey]"><?php echo _('API key'); ?></label></dt>
			<dd><input class="withicon apikeyinputfield" type="text" readonly="readonly" value="<?php echo $edituserform['apikey']; ?>">
			<input type="image" class="resetApiSubmit" onclick="ajaxSubmitFormWithCb('?page=edituser', this, requestNewUserApiKeyCbHandler); return false; "  src="images/refresh.png" name="edituserform[submitresetuserapi]" value="<?php echo _('Create new API key'); ?>"></dd>
<?php } ?>

<?php if (($tplHelper->allowed(SpotSecurity::spotsec_edit_groupmembership, '')) || ($tplHelper->allowed(SpotSecurity::spotsec_display_groupmembership, ''))) { ?>
			<!-- Dummy grouplist variable om zeker te zijn dat de grouplist altijd gepost wordt -->
			<input type="hidden" name="edituserform[grouplist][dummy]" value="dummy">
			<table>
				<thead>
					<tr> <th> <?php echo _('Group'); ?> </th> <th> <?php echo _('Member'); ?> </th>
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
				<input class="greyButton" type="submit" name="edituserform[submitedit]" value="<?php echo _('Change'); ?>">
<?php if ($edituserform['userid'] > SPOTWEB_ADMIN_USERID && $tplHelper->allowed(SpotSecurity::spotsec_delete_user, '')) { ?>
				<input class="greyButton" type="submit" name="edituserform[submitdelete]" value="<?php echo _('Delete user'); ?>">
<?php } ?>
				<input class="greyButton" type="submit" name="edituserform[submitremoveallsessions]" value="<?php echo _('Clear all sessions'); ?>">
			</dd>
		</dl>
	</fieldset>
</form>
<?php
}
