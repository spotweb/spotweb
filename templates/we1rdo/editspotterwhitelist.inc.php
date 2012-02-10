<?php
	# We zetten deze zo ver mogelijk bovenaan om een schone error afhandeling te kunnen hebben
	$whitelist = $tplHelper->getSpotterWhitelist();
?>
	<!-- We need our own editdialogdiv because this form can be embedded into another dialog as a whole -->
	<div id='editblacklistdialogdiv'></div>
	<table  class="ui-widget ui-widget-content" summary="WhitelistedSpotters">
		<thead>
			<tr class="ui-widget-header">
				<th><?php echo _('Spotter ID'); ?></th> 
				<th><?php echo _('Origin'); ?></th>
				<th><?php echo _('Remove spotterid'); ?></th>
			</tr>
		</thead>
		<tbody id="whitelist">
				
<?php
	foreach($whitelist as $bannedspotter) {
?>
				<td> <?php echo $bannedspotter['spotterid']; ?> </td>
				<td> <?php echo $bannedspotter['origin']; ?> </td>
				<td><a href="" onclick="return openDialog('editblacklistdialogdiv', '<?php echo _('Remove spotter from whitelist') ?>', '?page=render&tplname=editspotterwhitelistdelete&data[spotterid]=<?php echo $bannedspotter['spotterid']; ?>', 'whitelistspotterform', null, 'autoclose', function() { refreshTab('edituserpreferencetabs')}, null); "><span class="ui-icon ui-icon-circle-close"></span></a></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>

	