<?php
if (!empty($editresult)) {
	include 'includes/form-xmlresult.inc.php';

	$this->sendContentTypeHeader('xml');
	echo formResult2Xml($editresult, $formmessages, $tplHelper);
} # if

if (empty($editresult)) {
	include "includes/form-messages.inc.php";
	
	# vraag de opgegeven securitygroup op
	$permList = $tplHelper->getSecGroupPerms($securitygroup['id']);
?>
	<table class="ui-widget ui-widget-content secgroupperms" summary="Permissions">
		<thead>
			<tr class="ui-widget-header head">
				<th><?php echo _('Permission'); ?></th> 
				<th><?php echo _('Object'); ?></th>
				<?php if ($securitygroup['id'] > 5) { ?>
					<th><?php echo _('Delete'); ?></th>
					<th><?php echo _('Deny/Allow'); ?></th>
				<?php } ?>
				<th>|</th>
				<th><?php echo _('Permission'); ?></th> 
				<th><?php echo _('Object'); ?></th>
				<?php if ($securitygroup['id'] > 5) { ?>
					<th><?php echo _('Delete'); ?></th>
					<th><?php echo _('Deny/Allow'); ?></th>
				<?php } ?>
			</tr>
		</thead>
		
		<tbody id="secgroupermlist">
<?php for($i = 0; $i < count($permList); $i += 2) { 
		echo '<tr>';
		for($j = 0; $j < 2 && count($permList) > ($i + $j); $j++) { 
			$perm = $permList[$i+$j];
?>
				<td> <?php echo _($tplHelper->permToString($perm['permissionid'])); ?> </td>
				<td> <?php echo $perm['objectid']; ?> </td>
				<?php if ($securitygroup['id'] > 5) { ?>
				<td> 
					<form action="<?php echo $tplHelper->makeEditSecGroupAction(); ?>" method="post">
						<input type="hidden" name="editsecgroupform[permissionid]" value="<?php echo $perm['permissionid']; ?>">
						<input type="hidden" name="editsecgroupform[objectid]" value="<?php echo $perm['objectid']; ?>">
						<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
						<input type="hidden" name="editsecgroupform[http_referer]" value="<?php echo $http_referer; ?>">
						<input type="hidden" name="groupid" value="<?php echo $securitygroup['id']; ?>">
						<input class="smallGreyButton" type="submit" name="editsecgroupform[submitremoveperm]" value="<?php echo _('Delete'); ?>">
					</form>
				</td>
				<td> 
					<form action="<?php echo $tplHelper->makeEditSecGroupAction(); ?>" method="post">
						<input type="hidden" name="editsecgroupform[permissionid]" value="<?php echo $perm['permissionid']; ?>">
						<input type="hidden" name="editsecgroupform[objectid]" value="<?php echo $perm['objectid']; ?>">
						<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
						<input type="hidden" name="editsecgroupform[http_referer]" value="<?php echo $http_referer; ?>">
						<input type="hidden" name="groupid" value="<?php echo $securitygroup['id']; ?>">
						<?php if ($perm['deny']) { ?>
							<input class="smallGreyButton" type="submit" name="editsecgroupform[submitsetallow]" value="<?php echo _('Denied'); ?>" title="<?php echo _('Set to allow'); ?>">
						<?php } else { ?>
							<input class="smallGreyButton" type="submit" name="editsecgroupform[submitsetdeny]" value="<?php echo _('Allowed'); ?>" title="<?php echo _('Set to deny'); ?>">
						<?php } ?>
					</form>
				</td>
				<?php } ?>
				
<?php
			if ($j == 0) {
				echo '<td></td>';
			} # if

		} // for j
		echo '</tr>';
	}
?>
		</tbody>
	</table>

	<br >
	<br >
	
	<!-- Security recht toevoegen -->
<?php if ($securitygroup['id'] > 5) { ?>
	<form class="editsecgroupform" name="editsecgroupform" action="<?php echo $tplHelper->makeEditSecGroupAction(); ?>" method="post">
		<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
		<input type="hidden" name="editsecgroupform[http_referer]" value="<?php echo $http_referer; ?>">
		<input type="hidden" name="groupid" value="<?php echo $securitygroup['id']; ?>">

		<fieldset>
			<dt><label for="editsecgroupform[permissionid]"><?php echo _('Add the following rights'); ?></label></dt>
			<dd>
				<select name="editsecgroupform[permissionid]">
			
<?php foreach($tplHelper->getAllAvailablePerms() as $key => $val) { ?>
					<option value="<?php echo $key; ?>"><?php echo _($val); ?></option>
<?php } ?>
				</select>
			</dd>
			
			<dt><label for="editsecgroupform[objectid]"><?php echo _('ObjectID (normally empty)'); ?></label></dt>
			<dd>
				<input type="text" name="editsecgroupform[objectid]" ></input>
			</dd>

			<dd>
				<input class="smallGreyButton" type="submit" name="editsecgroupform[submitaddperm]" value="<?php echo _('Add'); ?>">
			</dd>
		</fieldset>
	</form>
<?php } ?>

<?php
	require_once "includes/footer.inc.php";

	} # if not only  xml
	
