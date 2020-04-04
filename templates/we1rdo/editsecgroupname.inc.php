<?php
    require __DIR__.'/includes/form-messages.inc.php';
    renderResultMessagesHtml(new Dto_FormResult());

    // is form voor het toevoegen van een groep ipv wijzigen van een
    $isNew = (isset($data['isnew']));

    // vraag de opgegeven securitygroup op
    if (!$isNew) {
        $securitygroup = $tplHelper->getSecGroup($data['groupid']);
    } else {
        $securitygroup = ['name' => ''];
    }// if

?>

	<!-- Naam van security group wijzigen of nieuwe security groep toevoegen -->
	<fieldset>
		<form class="editsecgroupform" name="editsecgroupform" action="<?php echo $tplHelper->makeEditSecGroupAction(); ?>" method="post">
			<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
<?php if (!$isNew) { ?>			
			<input type="hidden" name="groupid" value="<?php echo $securitygroup['id']; ?>">
<?php } else {  ?>
			<input type="hidden" name="groupid" value="9999">
<?php } ?>
			
			<dt><label for="editsecgroupform[name]"><?php echo _('Name'); ?></label></dt>
			<dd>
				<input type="text" name="editsecgroupform[name]" value="<?php echo htmlspecialchars($securitygroup['name']); ?>" />
			</dd>
			
			<dd>
<?php if ($isNew) { ?>			
				<input class="smallGreyButton" type="submit" name="editsecgroupform[submitaddgroup]" value="<?php echo _('Add'); ?>">
<?php } else { ?>
				<input class="smallGreyButton" type="submit" name="editsecgroupform[submitchangename]" value="<?php echo _('Change'); ?>">
<?php } ?>
			</dd>
		</form>
	</fieldset>
