<?php
if (!empty($edituserprefsresult)) {
	//include 'includes/form-xmlresult.inc.php';
	//echo formResult2Xml($edituserprefsresult, $formmessages, $tplHelper);
	
	if ($edituserprefsresult['result'] == 'success') {
		$tplHelper->redirect($http_referer);
		return ;
	} # if
} # if

require "includes/header.inc.php";
include "includes/form-messages.inc.php";
?>
</div>
<form class="edituserprefsform" name="edituserprefsform" action="<?php echo $tplHelper->makeEditUserPrefsAction(); ?>" method="post">
	<input type="hidden" name="edituserprefsform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('edituserprefsform'); ?>">
	<input type="hidden" name="edituserprefsform[http_referer]" value="<?php echo $http_referer; ?>">
	<input type="hidden" name="edituserprefsform[buttonpressed]" value="">
	<input type="hidden" name="userid" value="<?php echo $spotuser['userid']; ?>">
	
	<div id="edituserpreferencetabs" class="ui-tabs">
		<ul>
			<li><a href="#edituserpreftab-1"><span><?php echo _('General'); ?></span></a></li>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, '')) { ?>
			<li><a href="#edituserpreftab-2"><span><?php echo _('NZB handeling'); ?></span></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_filters, '')) { ?>
			<li><a href="?page=render&tplname=listfilters" title="<?php echo _('Filters'); ?>"><span><?php echo _('Filters'); ?></span></a></li>
<!--
			<li><a href="?page=render&tplname=cat2dlmapping" title="<?php echo _('Download categories'); ?>"><span><?php echo _('Download categories'); ?></span></a></li>
-->
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, '') && $tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, '')) { ?>
			<li><a href="#edituserpreftab-4"><span><?php echo _('Notifications'); ?></span></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_allow_custom_stylesheet, '')) { ?>
			<li><a href="#edituserpreftab-5"><span><?php echo _('Own CSS'); ?></span></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_post_spot, '')) { ?>
			<li><a href="#edituserpreftab-6"><span><?php echo _('Posting of spots'); ?></span></a></li>
<?php } ?>
	
		</ul>
			
		<div id="edituserpreftab-1" class="ui-tabs-hide">
			<fieldset>
				<dl>
					<dt><label for="edituserprefsform[perpage]"><?php echo _('Items per page?'); ?></label></dt>
					<dd>
						<select name="edituserprefsform[perpage]">
							<option <?php if ($edituserprefsform['perpage'] == 25) { echo 'selected="selected"'; } ?> value="25">25</option>
							<option <?php if ($edituserprefsform['perpage'] == 50) { echo 'selected="selected"'; } ?> value="50">50</option>
							<option <?php if ($edituserprefsform['perpage'] == 100) { echo 'selected="selected"'; } ?> value="100">100</option>
							<option <?php if ($edituserprefsform['perpage'] == 250) { echo 'selected="selected"'; } ?> value="250">250</option>
						</select>
					</dd>

					<dt><label for="edituserprefsform[defaultsortfield]"><?php echo _('Standard searchorder?'); ?></label></dt>
					<dd>
						<select name="edituserprefsform[defaultsortfield]">
							<option <?php if ($edituserprefsform['defaultsortfield'] == '') { echo 'selected="selected"'; } ?> value=""><?php echo _('Relevance');?></option>
							<option <?php if ($edituserprefsform['defaultsortfield'] == 'stamp') { echo 'selected="selected"'; } ?> value="stamp"><?php echo _('Latest first'); ?></option>
						</select>
					</dd>


					<dt><label for="edituserprefsform[date_formatting]"><?php echo _('Formatting of dates'); ?></label></dt>
					<dd>
						<select name="edituserprefsform[date_formatting]">
							<option <?php if ($edituserprefsform['date_formatting'] == 'human') { echo 'selected="selected"'; } ?> value="human" selected><?php echo _('Human'); ?></option>
							<option <?php if ($edituserprefsform['date_formatting'] == '%a, %d-%b-%Y (%H:%M)') { echo 'selected="selected"'; } ?> value="%a, %d-%b-%Y (%H:%M)"><?php echo strftime("%a, %d-%b-%Y (%H:%M)", time()); ?></option>
							<option <?php if ($edituserprefsform['date_formatting'] == '%d-%m-%Y (%H:%M)') { echo 'selected="selected"'; } ?> value="%d-%m-%Y (%H:%M)"><?php echo strftime("%d-%m-%Y (%H:%M)", time()); ?></option>
						</select>
					</dd>
					
					<dt><label for="edituserprefsform[template]"><?php echo _('Template');?></label></dt>
					<dd>
						<select name="edituserprefsform[template]">
							<option <?php if ($edituserprefsform['template'] == 'we1rdo') { echo 'selected="selected"'; } ?> value="we1rdo" selected>we1rdo (standaard)</option>
<!--
	Deze zijn uitgecommentarieerd omdat als je deze kiest, je niet meer terug kan aangezien beide
	templates geen edit-preferences geimplementeerd hebben
	
							<option value="mobile">Mobile</option>
-->
						</select>
					</dd>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_view_spotcount_filtered, '')) { ?>					
					<dt><label for="edituserprefsform[count_newspots]"><?php echo _('Count new spots in filterd list'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[count_newspots]" <?php if ($edituserprefsform['count_newspots']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_seenlist, '')) { ?>					
					<dt><label for="edituserprefsform[keep_seenlist]"><?php echo _('Track what you\'re watching'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_seenlist]" <?php if ($edituserprefsform['keep_seenlist']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
					<dt><label for="edituserprefsform[auto_markasread]"><?php echo _('Automatic mark spots as read after each visit?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[auto_markasread]" <?php if ($edituserprefsform['auto_markasread']) { echo 'checked="checked"'; } ?>></dd>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_downloadlist, '')) { ?>					
					<dt><label for="edituserprefsform[keep_downloadlist]"><?php echo _('Should we keep track of the downloads that are done?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_downloadlist]" <?php if ($edituserprefsform['keep_downloadlist']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_watchlist, '')) { ?>
					<dt><label for="edituserprefsform[keep_watchlist]"><?php echo _('Shall we keep track of a watchlist?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_watchlist]" <?php if ($edituserprefsform['keep_watchlist']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>

					<dt><label for="edituserprefsform[show_filesize]"><?php echo _('Show filesize in spotoverview?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[show_filesize]" <?php if ($edituserprefsform['show_filesize']) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="edituserprefsform[show_reportcount]"><?php echo _('Show number of spamreports in spotoverview?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[show_reportcount]" <?php if ($edituserprefsform['show_reportcount']) { echo 'checked="checked"'; } ?>></dd>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, '')) { ?>
					<dt><label for="edituserprefsform[show_nzbbutton]"><?php echo _('Show NZB button to download file with this browser?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[show_nzbbutton]" <?php if ($edituserprefsform['show_nzbbutton']) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="edituserprefsform[show_multinzb]"><?php echo _('Show a checkbox next to each spot for multiplex NZB file download?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[show_multinzb]" <?php if ($edituserprefsform['show_multinzb']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_filters, '')) { ?>
					<dt><label for="edituserprefsform[_dummy_prevent_porn]"><?php echo _('Hide erotic spots in index?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[_dummy_prevent_porn]" <?php $tmpIndexFilter = $tplHelper->getIndexFilter(); if (stripos($tmpIndexFilter['tree'], '~cat0_z3') !== false) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
					<dt><label for="edituserprefsform[nzb_search_engine]"><?php echo _('What NZB searchengine shall we use?'); ?></label></dt>
					<dd>
						<select name="edituserprefsform[nzb_search_engine]">
							<option <?php if ($edituserprefsform['nzb_search_engine'] == 'binsearch') { echo 'selected="selected"'; } ?> value="binsearch">Binsearch</option>
							<option <?php if ($edituserprefsform['nzb_search_engine'] == 'nzbindex') { echo 'selected="selected"'; } ?> value="nzbindex">NZBIndex</option>
						</select>
					</dd>

				</dl>
			</fieldset>
		</div>

		
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, '')) { ?>
		<div id="edituserpreftab-2" class="ui-tabs-hide">
			<fieldset>
				<dl>
					<!-- NZBHANDLING -->
					<dt><label for="edituserprefsform[nzbhandling][action]"><?php echo _('What shall we do with NZB files?'); ?></label></dt>
					<dd>
						<select id="nzbhandlingselect" name="edituserprefsform[nzbhandling][action]">
							<option data-fields="" <?php if ($edituserprefsform['nzbhandling']['action'] == "disable") { echo 'selected="selected"'; } ?> value="disable"><?php echo _('No intergration with download client'); ?></option>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'push-sabnzbd')) { ?>
							<option data-fields="sabnzbd" <?php if ($edituserprefsform['nzbhandling']['action'] == "push-sabnzbd") { echo 'selected="selected"'; } ?> value="push-sabnzbd"><?php echo _('Call sabnzbd+ throught HTTP by SpotWeb'); ?></option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'client-sabnzbd')) { ?>
							<option data-fields="sabnzbd" <?php if ($edituserprefsform['nzbhandling']['action'] == "client-sabnzbd") { echo 'selected="selected"'; } ?> value="client-sabnzbd"><?php echo _('Run sabnzbd+ through users\' browser'); ?></option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'save')) { ?>
							<option data-fields="localdir" <?php if ($edituserprefsform['nzbhandling']['action'] == "save") { echo 'selected="selected"'; } ?> value="save"><?php echo _('Save to file op disk'); ?></option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'runcommand')) { ?>
							<option data-fields="localdir runcommand" <?php if ($edituserprefsform['nzbhandling']['action'] == "runcommand") { echo 'selected="selected"'; } ?> value="runcommand"><?php echo _('Save file to disk and run a command'); ?></option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'nzbget')) { ?>
							<option data-fields="nzbget" <?php if ($edituserprefsform['nzbhandling']['action'] == "nzbget") { echo 'selected="selected"'; } ?> value="nzbget"><?php echo _('Call NZBGet through HTTP by SpotWeb'); ?></option>
<?php } ?>
						</select>
					</dd>

					<dt><label for="edituserprefsform[nzbhandling][prepare_action]"><?php echo _('What shall we do with multiple NZB files?'); ?></label></dt>
					<dd>
						<select name="edituserprefsform[nzbhandling][prepare_action]">
							<option <?php if ($edituserprefsform['nzbhandling']['prepare_action'] == "merge") { echo 'selected="selected"'; } ?> value="merge"><?php echo _('Merge NZB files'); ?></option>
							<option <?php if ($edituserprefsform['nzbhandling']['prepare_action'] == "zip") { echo 'selected="selected"'; } ?> value="zip"><?php echo _('Compress NZB files to 1 zip-file'); ?></option>
						</select>
					</dd>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'save') || $tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'runcommand')) { ?>
					<fieldset id="nzbhandling-fieldset-localdir">
						<dt><label for="edituserprefsform[nzbhandling][local_dir]"><?php echo _('Where shall we store the file?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][local_dir]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['local_dir']); ?>"></dd>
					</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'runcommand')) { ?>
					<fieldset id="nzbhandling-fieldset-runcommand">
						<dt><label for="edituserprefsform[nzbhandling][command]"><?php echo _('What programm should be executed?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][command]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['command']); ?>"></dd>
					</fieldset>
<?php } ?>

					<!-- Sabnzbd -->
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'push-sabnzbd') || $tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'client-sabnzbd')) { ?>
					<fieldset id="nzbhandling-fieldset-sabnzbd">
						<dt><label for="edituserprefsform[nzbhandling][sabnzbd][url]"><?php echo _('URL to sabnzbd (HTTP included and portnumber where sabnzbd is installed)?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][sabnzbd][url]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['sabnzbd']['url']); ?>"></dd>

						<dt><label for="edituserprefsform[nzbhandling][sabnzbd][apikey]"><?php echo _('API key from sabnzbd?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][sabnzbd][apikey]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['sabnzbd']['apikey']); ?>"></dd>
					</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'nzbget')) { ?>
					<fieldset id="nzbhandling-fieldset-nzbget">
						<!-- NZBget -->
						<input type="hidden" name="edituserprefsform[nzbhandling][nzbget][timeout]" value="30">
						
						<dt><label for="edituserprefsform[nzbhandling][nzbget][host]"><?php echo _('Hostname of nzbget?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][host]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['host']); ?>"></dd>

						<dt><label for="edituserprefsform[nzbhandling][nzbget][port]"><?php echo _('Portnumber of nzbget?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][port]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['port']); ?>"></dd>

						<dt><label for="edituserprefsform[nzbhandling][nzbget][username]"><?php echo _('Username for nzbget? Attention: At this moment only <u>nzbget</u> is a valid name!'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][username]" value="nzbget"></dd>

						<dt><label for="edituserprefsform[nzbhandling][nzbget][password]"><?php echo _('Password for nzbget?'); ?></label></dt>
						<dd><input type="password" name="edituserprefsform[nzbhandling][nzbget][password]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['password']); ?>"></dd>
					</fieldset>
<?php } ?>
				</dl>
			</fieldset>
		</div>
<?php } ?>

<!-- Notificaties -->
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, '') && $tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, '')) { ?>
		<div id="edituserpreftab-4">

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'boxcar')) { ?>
<!-- Boxcar -->
			<fieldset>
				<dt><label for="use_boxcar"><?php echo _('Use Boxcar?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][boxcar][enabled]" id="use_boxcar" <?php if ($edituserprefsform['notifications']['boxcar']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_boxcar">
					<dt><label for="edituserprefsform[notifications][boxcar][email]"><?php echo _('Boxcar e-mail address?'); ?></label></dt>
					<dd><input type="input" name="edituserprefsform[notifications][boxcar][email]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['boxcar']['email']); ?>"></dd>

					<?php showNotificationOptions('boxcar', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>
		
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'email')) { ?>
<!-- E-mail -->
			<fieldset>
				<dt><label for="use_email"><?php echo _('Send e-mail to') . ' ' . $currentSession['user']['mail']; ?>?</label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][email][enabled]" id="use_email" <?php if ($edituserprefsform['notifications']['email']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_email">
					<?php showNotificationOptions('email', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'growl')) { ?>
<!-- Growl -->
			<fieldset>
				<dt><label for="use_growl"><?php echo _('Use Growl?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][growl][enabled]" id="use_growl" <?php if ($edituserprefsform['notifications']['growl']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_growl">
					<dt><label for="edituserprefsform[notifications][growl][host]"><?php echo _('Growl IP-address?'); ?></label></dt>
					<dd><input type="input" name="edituserprefsform[notifications][growl][host]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['growl']['host']); ?>"></dd>

					<dt><label for="edituserprefsform[notifications][growl][password]"><?php echo _('Growl password?'); ?></label></dt>
					<dd><input type="password" name="edituserprefsform[notifications][growl][password]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['growl']['password']); ?>"></dd>

					<?php showNotificationOptions('growl', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'nma')) { ?>
<!-- Notify My Android -->
			<fieldset>
				<dt><label for="use_nma"><?php echo _('Use Notiy My Android?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][nma][enabled]" id="use_nma" <?php if ($edituserprefsform['notifications']['nma']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_nma">
					<dt><label for="edituserprefsform[notifications][nma][api]">Notify My Android <a href="https://www.notifymyandroid.com/account.php"><?php echo _('API key'); ?></a>?</label></dt>
					<dd><input type="text" name="edituserprefsform[notifications][nma][api]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['nma']['api']); ?>"></dd>

					<?php showNotificationOptions('nma', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'notifo')) { ?>
<!-- Notifo -->
			<fieldset>
				<dt><label for="use_notifo"><?php echo _('Use Notifo?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][notifo][enabled]" id="use_notifo" <?php if ($edituserprefsform['notifications']['notifo']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_notifo">
					<dt><label for="edituserprefsform[notifications][notifo][username]"><?php echo _('Notifo Username?'); ?></label></dt>
					<dd><input type="input" name="edituserprefsform[notifications][notifo][username]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['notifo']['username']); ?>"></dd>

					<dt><label for="edituserprefsform[notifications][notifo][api]"><?php echo _('Notifo <a href="http://notifo.com/user/settings">API secret</a>?'); ?></label></dt>
					<dd><input type="text" name="edituserprefsform[notifications][notifo][api]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['notifo']['api']); ?>"></dd>

					<?php showNotificationOptions('notifo', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>

<?php if (version_compare(PHP_VERSION, '5.3.0') >= 0) { ?>
	<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'prowl')) { ?>
<!-- Prowl -->
			<fieldset>
				<dt><label for="use_prowl"><?php echo _('Use Prowl?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][prowl][enabled]" id="use_prowl" <?php if ($edituserprefsform['notifications']['prowl']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_prowl">
					<dt><label for="edituserprefsform[notifications][prowl][apikey]"><?php echo _('Prowl <a href="https://www.prowlapp.com/api_settings.php">API key'); ?></a>?</label></dt>
					<dd><input type="text" name="edituserprefsform[notifications][prowl][apikey]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['prowl']['apikey']); ?>"></dd>

					<?php showNotificationOptions('prowl', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
	<?php } ?>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'twitter')) { ?>
<!-- Twitter -->
			<fieldset>
				<dt><label for="use_twitter"><?php echo _('Use Twitter?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][twitter][enabled]" id="use_twitter" <?php if ($edituserprefsform['notifications']['twitter']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_twitter">
					<div class="testNotification" id="twitter_result"><b><?php echo _('Click on "Ask permission". This opens a new page with a PIN number.') . '<br />' . _('Attention: If nothing happens please check you pop-up blocker'); ?></div>
					<input type="button" value="Toestemming Vragen" id="twitter_request_auth" />
	<?php if (!empty($edituserprefsform['notifications']['twitter']['screen_name'])) { ?>
					<input type="button" id="twitter_remove" value="Account <?php echo htmlspecialchars($edituserprefsform['notifications']['twitter']['screen_name']); ?> verwijderen" />
	<?php } ?>
					<?php showNotificationOptions('twitter', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>
		</div>
<?php } ?>
<!-- Einde notificaties -->


<!-- Custom Stylesheet -->
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_allow_custom_stylesheet, '')) { ?>
		<div id="edituserpreftab-5" class="ui-tabs-hide">
			<fieldset>
				<dt>
					<label for="edituserprefsform[customcss]"><?php echo _('Use custom CSS?'); ?></label>
				</dt>
				<dd>
					<textarea name="edituserprefsform[customcss]" rows="15" cols="120"><?php echo htmlspecialchars($edituserprefsform['customcss']); ?></textarea>
				</dd>
			</fieldset>
		</div>
<?php } ?>
<!-- Einde Custom Stylesheet -->

<!-- New spot defaults -->
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_post_spot, '')) { ?>
		<div id="edituserpreftab-6" class="ui-tabs-hide">
			<fieldset>
				<dt>
					<label for="edituserprefsform[newspotdefault_tag]"><?php echo _('Add the following tag'); ?></label>
				</dt>
				<dd>
					<input type="text" name="edituserprefsform[newspotdefault_tag]" maxlength="99" value="<?php echo htmlspecialchars($edituserprefsform['newspotdefault_tag']); ?>">
				</dd>

				<dt>
					<label for="edituserprefsform[newspotdefault_body]"><?php echo _('Use the following standard body'); ?></label>
				</dt>
				<dd>
					<textarea name="edituserprefsform[newspotdefault_body]" rows="15" cols="80"><?php echo htmlspecialchars($edituserprefsform['newspotdefault_body']); ?></textarea>
				</dd>
			</fieldset>
		</div>
<?php } ?>
<!-- Einde new spot default -->

		<dd>
			<input class="greyButton" type="submit" name="edituserprefsform[submitedit]" value="<?php echo _('Change'); ?>">
			<input class="greyButton" type="submit" name="edituserprefsform[submitcancel]" value="<?php echo _('Cancel'); ?>">
		</dd>
	</div>
</form>

<?php
	function showNotificationOptions($provider, $edituserprefsform, $tplHelper) {
		echo "<fieldset>" . PHP_EOL;

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'watchlist_handled') && $tplHelper->allowed(SpotSecurity::spotsec_keep_own_watchlist, '')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][watchlist_handled]\">" . _('Send message when a spot is added or deleted from the watchlist?') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][watchlist_handled]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['watchlist_handled']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'nzb_handled')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][nzb_handled]\">" . _('Send message when a NZB file is send? Doesn\'t work for client-sanzbd.') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][nzb_handled]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['nzb_handled']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'retriever_finished')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][retriever_finished]\">" . _('Send message when updating spots is finish?') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][retriever_finished]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['retriever_finished']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'report_posted') && $tplHelper->allowed(SpotSecurity::spotsec_report_spam, '')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][report_posted]\">" . _('Send message when Spam Reports has been send?') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][report_posted]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['report_posted']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'spot_posted') && $tplHelper->allowed(SpotSecurity::spotsec_post_spot, '')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][spot_posted]\">" . _('Send message when posting a spot has finished?') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][spot_posted]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['spot_posted']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'user_added')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][user_added]\">" . _('Send message when a user has been added?') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][user_added]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['user_added']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		echo "</fieldset>" . PHP_EOL;
	} # notificationOptions

	require_once "includes/footer.inc.php";
