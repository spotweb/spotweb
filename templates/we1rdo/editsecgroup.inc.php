<?php
	require "includes/header.inc.php";
	
if (!empty($edituserprefsresult)) {
	//include 'includes/form-xmlresult.inc.php';
	//echo formResult2Xml($edituserprefsresult, $formmessages, $tplHelper);
	
	if ($edituserprefsresult['result'] == 'success') {
		$tplHelper->redirect($http_referer);
		return ;
	} # if
} # if

include "includes/form-messages.inc.php";

$permList = $tplHelper->getSecGroup(1);
?>
</div>
<form class="editsecgroupform" name="editsecgroupform" action="<?php echo $tplHelper->makeEditUserPrefsAction(); ?>" method="post">
	<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
	<input type="hidden" name="editsecgroupform[http_referer]" value="<?php echo $http_referer; ?>">
	<input type="hidden" name="groupid" value="0">

	<table class="spotslistusers" summary="Users">
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
				<td> </td>
			</tr>
<?php } ?>
		</tbody>
		
	</table>
</form>

<?php
	require_once "includes/footer.inc.php";
