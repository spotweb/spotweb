<?php
	# We zetten deze zo ver mogelijk bovenaan om een schone error afhandeling te kunnen hebben
	$blacklist = $tplHelper->getSpotterBlacklist();
?>
	<table  class="ui-widget ui-widget-content" summary="BlaclistedSpotters">
		<thead>
			<tr class="ui-widget-header">
				<th><?php echo _('Spotter ID'); ?></th> 
				<th><?php echo _('Origin'); ?></th>
				<th><?php echo _('Remove spotterid'); ?></th>
			</tr>
		</thead>
		<tbody id="blacklist">
				
<?php
	foreach($blacklist as $bannedspotter) {
?>
				<td> <?php echo $bannedspotter['spotterid']; ?> </td>
				<td> <?php echo $bannedspotter['origin']; ?> </td>
				<td><a href="" onclick="return openDialog('editdialogdiv', '<?php echo _('Remove spotter from blacklist') ?>', '?page=render&tplname=editspotterblacklistdelete&data[spotterid]=<?php echo $bannedspotter['spotterid']; ?>', 'blacklistspotterform', null, true, function() { refreshTab('edituserpreferencetabs')}); "><span class="ui-icon ui-icon-circle-close"></span></a></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>

	