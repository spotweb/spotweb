<?php
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < 10; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
?>
<!-- Navnar template -->
		<div data-role="navbar">
		    <ul>
			    <li><a href="#spots"   <?php echo ($active == 'spots') ? 'class="ui-btn-active ui-state-persist"' : ''; ?> data-icon="grid" >Spots</a></li>
				<li><a href="#search"  <?php echo ($active == 'search') ? 'class="ui-btn-active ui-state-persist"' : ''; ?> data-icon="search"><?php echo _('Search'); ?></a></li>
				<li><a href="#filters" <?php echo ($active == 'filters') ? 'class="ui-btn-active ui-state-persist"' : ''; ?> data-icon="star">Filters</a></li>
				<?php if (($currentSession['user']['userid'] == $settings->get('nonauthenticated_userid')) && (empty($loginresult))) { ?>
				<li><a href="#" data-icon="power" onclick="return openDialog(<?php echo "'".$randomString."'"; ?>, '<?php echo _('Login'); ?>', '?page=login&data[htmlheaderssent]=true', null, 'autoclose', function() { window.location.reload(); }, null); "><?php echo _('Login'); ?> </a></li>
				<li><a href="#" data-icon="user" onclick="return openDialog(<?php echo "'".$randomString."'"; ?>, '<?php echo _('Add user'); ?>', '?page=createuser', null, 'showresultsonly', null, null); "><?php echo _('Register'); ?> </a></li>
				<?php } else { ?>
					<li><a href="#" id="anchorLoginControl" data-icon="power">Logout</a></li>
				<?php } ?>
		    </ul>
		</div><!-- /navbar -->

		<div data-role="popup" data-theme="a" class="ui-content">
			<a href="#" data-rel="back" data-role="button" data-theme="a" data-icon="delete" data-iconpos="notext" class="ui-btn-right">Close</a>        
			<div id= <?php echo '"'.$randomString.'"'; ?>></div>
		</div><!--generic popup-->
