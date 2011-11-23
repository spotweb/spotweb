<?php
	require "includes/header.inc.php";
?>
</div>

	<h4><a href='<?php echo $tplHelper->makeBaseUrl('path');?>'>&lt;&lt;&lt; <?php echo _('Back to mainview'); ?></a></h4>
	<div id="adminpaneltabs" class="ui-tabs">
		<ul>
<!--
			<li><a href="#adminpaneltab-1" title="Instellingen"><span>Instellingen</span></a></li>
-->
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_edit_other_users, '')) { ?>
			<li><a href="?page=render&tplname=listusers" title="<?php echo _('Userlist'); ?>"><span><?php echo _('Userlist');?></span></a></li>
<?php } ?>
			<li><a href="?page=render&tplname=listgroups" title="<?php echo _('Grouplist'); ?>"><span><?php echo _('Grouplist'); ?></span></a></li>
		</ul>

		<div id="adminpaneltab-1" class="ui-tabs-hide">
		</div>
		
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_edit_other_users, '')) { ?>
		<div id="adminpaneltab-2" class="ui-tabs-hide">
		</div>
<?php } ?>

		<div id="adminpaneltab-3" class="ui-tabs-hide">
		</div>

<?php
	require_once "includes/footer.inc.php";
