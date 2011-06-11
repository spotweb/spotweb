<?php
if (!empty($editresult)) {
	include 'includes/form-xmlresult.inc.php';

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
				<th>Permissie</th> 
				<th>Object</th>
				<?php if ($securitygroup['id'] > 3) { ?>
					<th>Wis</th>
				<?php } ?>
				<th>|</th>
				<th>Permissie</th> 
				<th>Object</th>
				<?php if ($securitygroup['id'] > 3) { ?>
					<th>Wis</th>
				<?php } ?>
			</tr>
		</thead>
		
		<tbody id="secgroupermlist">
<?php for($i = 0; $i < count($permList); $i += 2) { 
		echo '<tr>';
		for($j = 0; $j < 2 && count($permList) > ($i + $j); $j++) { 
			$perm = $permList[$i+$j];
?>
				<td> <?php echo $tplHelper->permToString($perm['permissionid']); ?> </td>
				<td> <?php echo $perm['objectid']; ?> </td>
				<?php if ($securitygroup['id'] > 3) { ?>
				<td> 
					<form action="<?php echo $tplHelper->makeEditSecGroupAction(); ?>" method="post">
						<input type="hidden" name="editsecgroupform[permissionid]" value="<?php echo $perm['permissionid']; ?>">
						<input type="hidden" name="editsecgroupform[objectid]" value="<?php echo $perm['objectid']; ?>">
						<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
						<input type="hidden" name="editsecgroupform[http_referer]" value="<?php echo $http_referer; ?>">
						<input type="hidden" name="groupid" value="<?php echo $securitygroup['id']; ?>">
						<input class="smallGreyButton" type="submit" name="editsecgroupform[submitremoveperm]" value="Wis">
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
<?php if ($securitygroup['id'] > 3) { ?>
	<form class="editsecgroupform" name="editsecgroupform" action="<?php echo $tplHelper->makeEditSecGroupAction(); ?>" method="post">
		<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
		<input type="hidden" name="editsecgroupform[http_referer]" value="<?php echo $http_referer; ?>">
		<input type="hidden" name="groupid" value="<?php echo $securitygroup['id']; ?>">

		<fieldset>
			<dt><label for="editsecgroupform[permissionid]">Volgende recht toevoegen</label></dt>
			<dd>
				<select name="editsecgroupform[permissionid]">
			
<?php foreach($tplHelper->getAllAvailablePerms() as $key => $val) { ?>
					<option value="<?php echo $key; ?>"><?php echo $val; ?></option>
<?php } ?>
				</select>
			</dd>
			
			<dt><label for="editsecgroupform[objectid]">ObjectID (meestal leeg)</label></dt>
			<dd>
				<input type="text" name="editsecgroupform[objectid]" ></input>
			</dd>

			<dd>
				<input class="smallGreyButton" type="submit" name="editsecgroupform[submitaddperm]" value="Voeg toe">
			</dd>
		</fieldset>
	</form>
<?php } ?>

<?php
	require_once "includes/footer.inc.php";

	} # if not only  xml
	
