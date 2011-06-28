<?php
	# is form voor het toevoegen van een groep ipv wijzigen van een
	$isNew = (isset($data['isnew']));
	
	# vraag de opgegeven securitygroup op
	if (!$isNew) {
		$securitygroup = $tplHelper->getSecGroup($data['groupid']);
	} else {
		$securitygroup = array('name' => '');
	}# if

	# bereid alvast een UL voor voor de errors e.d., worden er later
	# via AJAX ingegooid
	include "includes/form-messages.inc.php";
	
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
			
			<dt><label for="editsecgroupform[name]">Naam</label></dt>
			<dd>
				<input type="text" name="editsecgroupform[name]" value="<?php echo htmlspecialchars($securitygroup['name']); ?>"></input>
			</dd>
			
			<dd>
<?php if ($isNew) { ?>			
				<input class="smallGreyButton" type="submit" name="editsecgroupform[submitaddgroup]" value="Voeg toe">
<?php } else { ?>
				<input class="smallGreyButton" type="submit" name="editsecgroupform[submitchangename]" value="Wijzig">
<?php } ?>
			</dd>
		</form>
	</fieldset>
