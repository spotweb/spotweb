<?php
	require "includes/header.inc.php";
	
if (!empty($editsecgroupresult)) {
	//include 'includes/form-xmlresult.inc.php';
	//echo formResult2Xml($editsecgroupresult, $formmessages, $tplHelper);
	
	if ($editsecgroupresult['result'] == 'success') {
		$tplHelper->redirect($http_referer);
		return ;
	} # if
} # if

include "includes/form-messages.inc.php";

$permList = $tplHelper->getSecGroup(2);
?>
	<span><a href='<?php echo $http_referer;?>'>Terug naar vorige pagina</a></span>
	<br>

	<!-- Naam van security group wijzigen -->
	<fieldset>
		<form action="<?php echo $tplHelper->makeEditSecGroupAction(); ?>" method="post">
			<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
			<input type="hidden" name="editsecgroupform[http_referer]" value="<?php echo $http_referer; ?>">
			<input type="hidden" name="editsecgroupform[groupid]" value="0">
			
			<dt><label for="editsecgroupform[name]">Naam</label></dt>
			<dd>
				<input type="text" name="editsecgroupform[name]" value="<?php echo htmlspecialchars($editsecgroupform['name']); ?>"></input>
			</dd>
			
			<dd>
				<input class="greyButton" type="submit" name="editsecgroupform[submitchangename]" value="Wijzig">
			</dd>
		</form>
	</fieldset>
	
	
	<table class="secgroupperms" summary="Permissions">
		<thead>
			<tr class="head">
				<th>Permissie</th> 
				<th>Object</th>
				<th>Wis</th>
			</tr>
		</thead>
		
		<tbody id="secgroupermlist">
<?php foreach($permList as $perm) { ?>
			<tr>
				<td> <?php echo $tplHelper->permToString($perm['permissionid']); ?> </td>
				<td> <?php echo $perm['objectid']; ?> </td>
				<td> 
					<form action="<?php echo $tplHelper->makeEditSecGroupAction(); ?>" method="post">
						<input type="hidden" name="editsecgroupform[permissiond]" value="<?php echo $perm['permissionid']; ?>">
						<input type="hidden" name="editsecgroupform[objectid]" value="<?php echo $perm['objectid']; ?>">
						<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
						<input type="hidden" name="editsecgroupform[http_referer]" value="<?php echo $http_referer; ?>">
						<input type="hidden" name="editsecgroupform[groupid]" value="0">
						<input class="greyButton" type="submit" name="editsecgroupform[submitremoveperm]" value="Wis">
					</form>
				</td>
			</tr>
<?php } ?>
		</tbody>
	</table>

	<!-- Security recht toevoegen -->
	<form class="editsecgroupform" name="editsecgroupform" action="<?php echo $tplHelper->makeEditSecGroupAction(); ?>" method="post">
		<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
		<input type="hidden" name="editsecgroupform[http_referer]" value="<?php echo $http_referer; ?>">
		<input type="hidden" name="editsecgroupform[groupid]" value="0">

		<fieldset>
			<dt><label for="editsecgroupform[permissionid]">Volgende recht toevoegen</label></dt>
			<dd>
				<select name="editsecgroupform[permissionid]">
			
<?php foreach($tplHelper->getAllAvailablePerms() as $key => $val) { ?>
					<option value="$key"><?php echo $val; ?></option>
<?php } ?>
				</select>
			</dd>
			
			<dt><label for="editsecgroupform[objectid]">ObjectID (meestal leeg)</label></dt>
			<dd>
				<input type="text" name="editsecgroupform[objectid]" ></input>
			</dd>

			<dd>
				<input class="greyButton" type="submit" name="editsecgroupform[submitaddperm]" value="Voeg toe">
			</dd>
		</fieldset>
	</form>

<?php
	require_once "includes/footer.inc.php";
