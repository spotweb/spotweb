<?php
    require __DIR__.'/includes/form-messages.inc.php';

    if (!showResults($result)) {

    // Retrieve the requested security group
    $permList = $tplHelper->getSecGroupPerms($securitygroup['id']); ?>
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
<?php
// make sure we have an even number of permissions so that both columns are always filled in
$nrOfPermission = count($permList);
        if ($nrOfPermission % 2 == 1) {
            $permList[] = ['permissionid' => -1,
                'permissionname'          => '&nbsp;',
                'objectid'                => '&nbsp;', ];

            // we now have one permission more
            $nrOfPermission++;
        }

        $rows = $nrOfPermission / 2;

        for ($i = 0; $i < $rows; $i++) {
            echo '<tr>';
            for ($j = 0; $j < 2; $j++) {
                ($j == 0) ? $perm = $permList[$i] : $perm = $permList[$i + $rows]; ?>
				<td> <?php echo $perm['permissionname']; ?> </td>
				<td> <?php echo $perm['objectid']; ?> </td>
				<?php if ($securitygroup['id'] > 5) {
                    if ($perm['permissionid'] != -1) { ?>
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
				<?php 	} else { ?>
				<td>&nbsp;</td><td>&nbsp;</td>
				<?php 	} // else
                } // if?>
				
<?php
            if ($j == 0) {
                echo '<td></td>';
            } // if
            } // for j
            echo '</tr>';
        } ?>
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
			
<?php foreach ($tplHelper->getAllAvailablePerms() as $key => $val) { ?>
					<option value="<?php echo $key; ?>"><?php echo $val; ?></option>
<?php } ?>
				</select>
			</dd>
			
			<dt><label for="editsecgroupform[objectid]"><?php echo _('ObjectID (normally empty)'); ?></label></dt>
			<dd>
				<input type="text" name="editsecgroupform[objectid]" />
			</dd>

			<dd>
				<input class="smallGreyButton" type="submit" name="editsecgroupform[submitaddperm]" value="<?php echo _('Add'); ?>">
			</dd>
		</fieldset>
	</form>
<?php }
    }
