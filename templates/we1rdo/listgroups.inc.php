<?php
	# We zetten deze zo ver mogelijk bovenaan om een schone error afhandeling te kunnen hebben
	$grouplist = $tplHelper->getGroupList();
?>

	<table id="spotslistgroups" class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header">
				<th><?php echo _('Name'); ?></th> 
				<th><?php echo _('Change name'); ?></th> 
				<th><?php echo _('Permissions'); ?></th> 
				<th><?php echo _('Delete group'); ?></th> 
			</tr>
		</thead>
		<tbody id="grouplist">
				
<?php
	foreach($grouplist as $group) {
?>
			<tr> 
<?php 
	# We kunnen de 'built in' groepen niet bewerken
	if ($group['id'] < 6) {
		echo '<td>' . $group['name'] . '</td>';
		echo '<td></td>';
		echo '<td><a href="" onclick="return openDialog(\'editdialogdiv\', \'' . _('Show rights in group') . '\', \'?page=editsecgroup&groupid=' . $group['id'] . '\', \'editsecgroupform\', null, \'reload\', null, null); "><span class="ui-icon ui-icon-zoomin"></span></a></td>';
		echo '<td></td>';
	} else {
		echo '<td>' . $group['name'] . '</td>';
		echo '<td><a href="" onclick="return openDialog(\'editdialogdiv\', \'' . _('Change groupname') . '\', \'?page=render&tplname=editsecgroupname&data[groupid]=' . $group['id'] . '\', \'editsecgroupform\', null, \'autoclose\', function() { refreshTab(\'usermanagementtabs\')}, null);"><span class="ui-icon ui-icon-pencil"></span></a></td>';
		echo '<td><a href="" onclick="return openDialog(\'editdialogdiv\', \'' . _('Change group') . '\', \'?page=editsecgroup&groupid=' . $group['id'] . '\', \'editsecgroupform\', null, \'reload\', function() { refreshTab(\'usermanagementtabs\')}, null); "><span class="ui-icon ui-icon-pencil"></span></a></td>';
		echo '<td><a href="" onclick="return openDialog(\'editdialogdiv\', \'' . _('Delete group') . '\', \'?page=render&tplname=editsecgroupdelete&data[groupid]=' . $group['id'] . '\', \'editsecgroupform\', null, \'autoclose\', function() { refreshTab(\'usermanagementtabs\')}, null); "><span class="ui-icon ui-icon-circle-close"></span></a></td>';
	} # else
?> 
			</tr>
<?php
	}
?>
		<tr>
			<td colspan='4'>
				<a href="" onclick="return openDialog('editdialogdiv', '<?php echo _('Add new group'); ?>', '?page=render&tplname=editsecgroupname&data[isnew]=true', 'editsecgroupform', null, 'autoclose', function() { refreshTab('usermanagementtabs')}, null);"><span class="ui-icon ui-icon-circle-plus"></span></a></td>
			</td>
		</tr>
		
		</tbody>
	</table>

