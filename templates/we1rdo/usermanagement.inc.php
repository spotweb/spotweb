<?php
	$pagetitle = _('User &amp; group management');
	
	require "includes/header.inc.php";
?>
</div>
	<div id='toolbar'>
		<div class="closeusermanagement"><p><a class='toggle' href='<?php echo $tplHelper->makeBaseUrl('path');?>'><?php echo _('Back to mainview'); ?></a></p></div>
	</div>
	
	<div id="usermanagementtabs" class="ui-tabs">
		<ul>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_edit_other_users, '')) { ?>
			<li><a href="?page=render&tplname=listusers" title="<?php echo _('Userlist'); ?>"><span><?php echo _('Userlist');?></span></a></li>
<?php } ?>
			<li><a href="?page=render&tplname=listgroups" title="<?php echo _('Grouplist'); ?>"><span><?php echo _('Grouplist'); ?></span></a></li>
		</ul>
			
<?php
	require_once "includes/footer.inc.php";
