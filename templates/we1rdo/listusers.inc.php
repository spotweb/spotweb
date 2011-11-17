<?php
	# We zetten deze zo ver mogelijk bovenaan om een schone error afhandeling te kunnen hebben
	$userlist = $tplHelper->getUserList('');
	$userlist = $userlist['list'];
?>
	<table  class="ui-widget ui-widget-content" summary="Users">
		<thead>
			<tr class="ui-widget-header">
				<th><?php echo _('Username'); ?></th> 
				<th><?php echo _('Lastname'); ?></th>
				<th><?php echo _('Firstname'); ?></th>
				<th><?php echo _('Mail'); ?></th>
				<th><?php echo _('Last visit'); ?></th>
				<th><?php echo _('Member of group'); ?></th>
			</tr>
		</thead>
		<tbody id="userlist">
				
<?php
	foreach($userlist as $user) {
		# We vragen nu de group membership op, en geven die mee als string zodat
		# ze kunnen zien welke groepen een user lid van is
		$groupMember = $tplHelper->getGroupListForUser($user['userid']);
		$groupList = '';
		foreach($groupMember as $group) {
			# We maken een link naar het editten van de security groep hier naar toe
			# dit maakt het simpeler om te zien welke rechten een user heeft
			if ($group['ismember']) {
				if ($tplHelper->allowed(SpotSecurity::spotsec_edit_groupmembership, '')) {
					$groupList .= '<a href="" onclick="return openDialog(\'editdialogdiv\', \'' . _('Change group') . '\', \'?page=editsecgroup&groupid=' . $group['id'] . '\', \'editsecgroupform\', null, false, function() { refreshTab(\'adminpaneltabs\')}); ">' . $group['name'] . '</a>, ';
				} elseif ($tplHelper->allowed(SpotSecurity::spotsec_display_groupmembership, '')) { 
					$groupList .= $group['name'] . ', ';
				} # if
			} # if
		} # foreach
		
		# en wis de laatste comma en spatie
		$groupList = substr($groupList, 0, -2);
?>
				<tr> 
					<td> 
<?php 
	# We kunnen de anonymous user niet editten
	if ($user['userid'] == SPOTWEB_ANONYMOUS_USERID) { 
		echo $user['username'];
	} else {
		echo '<a href="' . $tplHelper->makeEditUserUrl($user['userid'], 'edit') . '" ' .
				'onclick="return openDialog(\'editdialogdiv\', \'' . _('Change user') . '\', \'?page=edituser&userid=' . $user['userid'] . '\', \'edituserform\', null, true, function() { refreshTab(\'adminpaneltabs\')}); ">' .
				 $user['username'] . '</a>'; 
	} # else
?> 
				</td>
				<td> <?php echo $user['firstname']; ?> </td>
				<td> <?php echo $user['lastname']; ?> </td>
				<td> <?php echo $user['mail']; ?> </td>
				<td> <?php echo $tplHelper->formatDate($user['lastvisit'], 'userlist'); ?> </td>
				<td> <?php echo $groupList; ?> </td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>

	