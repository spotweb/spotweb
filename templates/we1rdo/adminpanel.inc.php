<?php
	require "includes/header.inc.php";
?>
</div>

	<h4><a href='<?php echo $tplHelper->makeBaseUrl('path');?>'>&lt;&lt;&lt; Terug naar overzichtspagina</a></h4>
	<div id="adminpaneltabs" class="ui-tabs">
		<ul>
			<li><a href="#adminpaneltab-1" title="Instellingen"><span>Instellingen</span></a></li>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_edit_other_users, '')) { ?>
			<li><a href="?page=render&tplname=listusers" title="Gebruikerslijst"><span>Gebruikerslijst</span></a></li>
<?php } ?>
			<li><a href="?page=render&tplname=listgroups" title="Groepenlijst"><span>Groepenlijst</span></a></li>
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
