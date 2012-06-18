<?php
	# We run tis at the top to get the cleanest error handling possible
	$userlist = $tplHelper->getUserList();
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
				<th><?php echo _('IP address of last visit'); ?></th>
				<th><?php echo _('Edit preferences'); ?></th>
			</tr>
		</thead>
		<tbody id="userlist">
				
<?php
	$allow_edit_groupMembership = $tplHelper->allowed(SpotSecurity::spotsec_edit_groupmembership, '');
	$allow_display_groupMembership = $tplHelper->allowed(SpotSecurity::spotsec_display_groupmembership, '');

	foreach($userlist as $user) {
		# We vragen nu de group membership op, en geven die mee als string zodat
		# ze kunnen zien welke groepen een user lid van is
		$groupMember = $tplHelper->getGroupListForUser($user['userid']);
		$groupList = '';
		foreach($groupMember as $group) {
			# We maken een link naar het editten van de security groep hier naar toe
			# dit maakt het simpeler om te zien welke rechten een user heeft
			if ($group['ismember']) {
				if ($allow_edit_groupMembership) {
					$groupList .= '<a href="" onclick="return openDialog(\'editdialogdiv\', \'' . _('Change group') . '\', \'?page=editsecgroup&groupid=' . $group['id'] . '\', \'editsecgroupform\', null, \'reload\', function() { refreshTab(\'usermanagementtabs\')}, null); ">' . $group['name'] . '</a>, ';
				} elseif ($allow_display_groupMembership) { 
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
		echo '<a href="' . $tplHelper->makeEditUserUrl($user['userid'], 'edit') . '" ' .
				'onclick="return openDialog(\'editdialogdiv\', \'' . _('Change user') . '\', \'?page=edituser&userid=' . $user['userid'] . '\', \'edituserform\', null, \'autoclose\', function() { refreshTab(\'usermanagementtabs\')}, null); ">' .
				 $user['username'] . '</a>'; 
?> 
				</td>
				<td> <?php echo $user['firstname']; ?> </td>
				<td> <?php echo $user['lastname']; ?> </td>
				<td> <?php echo $user['mail']; ?> </td>
				<td> <?php echo $tplHelper->formatDate($user['lastvisit'], 'userlist'); ?> </td>
				<td> <?php echo $groupList; ?> </td>
				<td> <?php echo $user['lastipaddr']; ?> </td>
				<td> 
<?php 
		echo '<a href="' . $tplHelper->makeEditUserUrl($user['userid'], 'edit') . '" ' .
				'onclick="return openDialog(\'editdialogdiv\', \'' . vsprintf(_('Editting user preferences for \\\'%s\\\''), $user['username']) . '\', \'?page=edituserprefs&userid=' . $user['userid'] . '&dialogembedded=1\', \'edituserprefsform\', null, \'autoclose\', function() { refreshTab(\'usermanagementtabs\')}, function() { initializeUserPreferencesScreen(); }); "><span class="ui-icon ui-icon-pencil"></span></a>'; 
?> 
				</td>
		</tr>
<?php
	}
?>
		</tbody>
	</table>

