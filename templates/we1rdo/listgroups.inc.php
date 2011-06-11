	<div id='editdialogdiv'></div>
	
	<table id="spotslistgroups" class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header">
				<th>Name</th> 
				<th>Wijzig naam</th> 
				<th>Permissies</th> 
				<th>Wis groep</th> 
			</tr>
		</thead>
		<tbody id="grouplist">
				
<?php
	$grouplist = $tplHelper->getGroupList();

	foreach($grouplist as $group) {
?>
			<tr> 
<?php 
	# We kunnen de 'built in' groepen niet bewerken
	if ($group['id'] < 4) {
		echo '<td>' . $group['name'] . '</td>';
		echo '<td></td>';
		echo '<td><a href="" onclick="return openDialog(\'editdialogdiv\', \'Toon rechten in groep\', \'?page=editsecgroup&groupid=' . $group['id'] . '\', \'editsecgroupform\', false, null); "><span class="ui-icon ui-icon-zoomin"></span></a></td>';
		echo '<td></td>';
	} else {
		echo '<td>' . $group['name'] . '</td>';
		echo '<td><a href="" onclick="return openDialog(\'editdialogdiv\', \'Wijzig groepsnaam\', \'?page=render&tplname=editsecgroupname&data[groupid]=' . $group['id'] . '\', \'editsecgroupform\', true, function() { refreshTab(\'adminpaneltabs\')});"><span class="ui-icon ui-icon-pencil"></span></a></td>';
		echo '<td><a href="" onclick="return openDialog(\'editdialogdiv\', \'Wijzig groep\', \'?page=editsecgroup&groupid=' . $group['id'] . '\', \'editsecgroupform\', false, function() { refreshTab(\'adminpaneltabs\')}); "><span class="ui-icon ui-icon-pencil"></span></a></td>';
		echo '<td><a href="" onclick="return openDialog(\'editdialogdiv\', \'Verwijder groep\', \'?page=render&tplname=editsecgroupdelete&data[groupid]=' . $group['id'] . '\', \'editsecgroupform\', true, function() { refreshTab(\'adminpaneltabs\')}); "><span class="ui-icon ui-icon-circle-close"></span></a></td>';
	} # else
?> 
			</tr>
<?php
	}
?>
		<tr>
			<td colspan='4'>
				<a href="" onclick="return openDialog('editdialogdiv', 'Nieuwe groep toevoegen', '?page=render&tplname=editsecgroupname&data[isnew]=true', 'editsecgroupform', true, function() { refreshTab('adminpaneltabs')});"><span class="ui-icon ui-icon-circle-plus"></span></a></td>
			</td>
		</tr>
		
		</tbody>
	</table>

