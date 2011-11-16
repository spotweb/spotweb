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
			<li><a href="#edituserpreftab-1"><span><?php echo _('Algemeen'); ?></span></a></li>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, '')) { ?>
			<li><a href="#edituserpreftab-2"><span><?php echo _('NZB afhandeling'); ?></span></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_filters, '')) { ?>
			<li><a href="?page=render&tplname=listfilters" title="<?php echo _('Filters'); ?>"><span><?php echo _('Filters'); ?></span></a></li>
<!--
			<li><a href="?page=render&tplname=cat2dlmapping" title="<?php echo _('Download Categorieen'); ?>"><span><?php echo _('Download Categorieen'); ?></span></a></li>
-->
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, '') && $tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, '')) { ?>
			<li><a href="#edituserpreftab-4"><span><?php echo _('Notificaties'); ?></span></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_allow_custom_stylesheet, '')) { ?>
			<li><a href="#edituserpreftab-5"><span><?php echo _('Eigen CSS'); ?></span></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_post_spot, '')) { ?>
			<li><a href="#edituserpreftab-6"><span><?php echo _('Posten van spots'); ?></span></a></li>
<?php } ?>
	
		</ul>
			
		<div id="edituserpreftab-1" class="ui-tabs-hide">
			<fieldset>
				<dl>
					<dt><label for="edituserprefsform[perpage]"><?php echo _('Aantal items per pagina?'); ?></label></dt>
					<dd>
						<select name="edituserprefsform[perpage]">
							<option <?php if ($edituserprefsform['perpage'] == 25) { echo 'selected="selected"'; } ?> value="25">25</option>
							<option <?php if ($edituserprefsform['perpage'] == 50) { echo 'selected="selected"'; } ?> value="50">50</option>
							<option <?php if ($edituserprefsform['perpage'] == 100) { echo 'selected="selected"'; } ?> value="100">100</option>
							<option <?php if ($edituserprefsform['perpage'] == 250) { echo 'selected="selected"'; } ?> value="250">250</option>
						</select>
					</dd>

					<dt><label for="edituserprefsform[defaultsortfield]"><?php echo _('Standaard sorteervolgorde bij zoeken?'); ?></label></dt>
					<dd>
						<select name="edituserprefsform[defaultsortfield]">
							<option <?php if ($edituserprefsform['defaultsortfield'] == '') { echo 'selected="selected"'; } ?> value=""><?php echo _('Relevantie');?></option>
							<option <?php if ($edituserprefsform['defaultsortfield'] == 'stamp') { echo 'selected="selected"'; } ?> value="stamp"><?php echo _('Nieuwste eerst'); ?></option>
						</select>
					</dd>


					<dt><label for="edituserprefsform[date_formatting]"><?php echo _('Opmaak van datums'); ?></label></dt>
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
					<dt><label for="edituserprefsform[count_newspots]"><?php echo _('Nieuwe Spots tellen in de lijst met filters'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[count_newspots]" <?php if ($edituserprefsform['count_newspots']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_seenlist, '')) { ?>					
					<dt><label for="edituserprefsform[keep_seenlist]"><?php echo _('Bijhouden wat je bekijkt'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_seenlist]" <?php if ($edituserprefsform['keep_seenlist']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
					<dt><label for="edituserprefsform[auto_markasread]"><?php echo _('Moeten spots automatisch na elke visit als gelezen worden gemarkeerd?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[auto_markasread]" <?php if ($edituserprefsform['auto_markasread']) { echo 'checked="checked"'; } ?>></dd>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_downloadlist, '')) { ?>					
					<dt><label for="edituserprefsform[keep_downloadlist]"><?php echo _('Moeten we bijhouden welke downloads er gedaan zijn?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_downloadlist]" <?php if ($edituserprefsform['keep_downloadlist']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_watchlist, '')) { ?>
					<dt><label for="edituserprefsform[keep_watchlist]"><?php echo _('Moeten we een watchlist bijhouden?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_watchlist]" <?php if ($edituserprefsform['keep_watchlist']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>

					<dt><label for="edituserprefsform[show_filesize]"><?php echo _('Toon bestandsgrootte in spotoverzicht?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[show_filesize]" <?php if ($edituserprefsform['show_filesize']) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="edituserprefsform[show_reportcount]"><?php echo _('Toon aantal spamreports in spotoverzicht?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[show_reportcount]" <?php if ($edituserprefsform['show_reportcount']) { echo 'checked="checked"'; } ?>></dd>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, '')) { ?>
					<dt><label for="edituserprefsform[show_nzbbutton]"><?php echo _('Toon een NZB knop om deze via je browser te downloaden?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[show_nzbbutton]" <?php if ($edituserprefsform['show_nzbbutton']) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="edituserprefsform[show_multinzb]"><?php echo _('Toon een checkbox naast elke spot om meerdere NZB files in een keer te downloaden?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[show_multinzb]" <?php if ($edituserprefsform['show_multinzb']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_filters, '')) { ?>
					<dt><label for="edituserprefsform[_dummy_prevent_porn]"><?php echo _('Erotica spots op de index verbergen?'); ?></label></dt>
					<dd><input type="checkbox" name="edituserprefsform[_dummy_prevent_porn]" <?php $tmpIndexFilter = $tplHelper->getIndexFilter(); if (stripos($tmpIndexFilter['tree'], '~cat0_z3') !== false) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
					<dt><label for="edituserprefsform[nzb_search_engine]"><?php echo _('Welke zoekmachine moet er gebruikt worden?'); ?></label></dt>
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
					<dt><label for="edituserprefsform[nzbhandling][action]"><?php echo _('Wat moeten we met NZB files doen?'); ?></label></dt>
					<dd>
						<select id="nzbhandlingselect" name="edituserprefsform[nzbhandling][action]">
							<option data-fields="" <?php if ($edituserprefsform['nzbhandling']['action'] == "disable") { echo 'selected="selected"'; } ?> value="disable"><?php echo _('Geen integratie met download client'); ?></option>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'push-sabnzbd')) { ?>
							<option data-fields="sabnzbd" <?php if ($edituserprefsform['nzbhandling']['action'] == "push-sabnzbd") { echo 'selected="selected"'; } ?> value="push-sabnzbd"><?php echo _('Roep sabnzbd+ aan via HTTP door SpotWeb'); ?></option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'client-sabnzbd')) { ?>
							<option data-fields="sabnzbd" <?php if ($edituserprefsform['nzbhandling']['action'] == "client-sabnzbd") { echo 'selected="selected"'; } ?> value="client-sabnzbd"><?php echo _('Roep sabnzbd+ aan via de users\' browser'); ?></option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'save')) { ?>
							<option data-fields="localdir" <?php if ($edituserprefsform['nzbhandling']['action'] == "save") { echo 'selected="selected"'; } ?> value="save"><?php echo _('Save de file op disk'); ?></option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'runcommand')) { ?>
							<option data-fields="localdir runcommand" <?php if ($edituserprefsform['nzbhandling']['action'] == "runcommand") { echo 'selected="selected"'; } ?> value="runcommand"><?php echo _('Save de file op disk en roep een commando aan'); ?></option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'nzbget')) { ?>
							<option data-fields="nzbget" <?php if ($edituserprefsform['nzbhandling']['action'] == "nzbget") { echo 'selected="selected"'; } ?> value="nzbget"><?php echo _('Roep NZBGet aan via HTTP door SpotWeb'); ?></option>
<?php } ?>
						</select>
					</dd>

					<dt><label for="edituserprefsform[nzbhandling][prepare_action]"><?php echo _('Wat moeten we met meerdere NZB files doen?'); ?></label></dt>
					<dd>
						<select name="edituserprefsform[nzbhandling][prepare_action]">
							<option <?php if ($edituserprefsform['nzbhandling']['prepare_action'] == "merge") { echo 'selected="selected"'; } ?> value="merge"><?php echo _('Voeg de nzb files samen'); ?></option>
							<option <?php if ($edituserprefsform['nzbhandling']['prepare_action'] == "zip") { echo 'selected="selected"'; } ?> value="zip"><?php echo _('Comprimeer de NZB files in 1 zip bestand'); ?></option>
						</select>
					</dd>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'save') || $tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'runcommand')) { ?>
					<fieldset id="nzbhandling-fieldset-localdir">
						<dt><label for="edituserprefsform[nzbhandling][local_dir]"><?php echo _('Waar moet de file opgeslagen worden?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][local_dir]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['local_dir']); ?>"></dd>
					</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'runcommand')) { ?>
					<fieldset id="nzbhandling-fieldset-runcommand">
						<dt><label for="edituserprefsform[nzbhandling][command]"><?php echo _('Welk programma moet uitgevoerd worden?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][command]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['command']); ?>"></dd>
					</fieldset>
<?php } ?>

					<!-- Sabnzbd -->
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'push-sabnzbd') || $tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'client-sabnzbd')) { ?>
					<fieldset id="nzbhandling-fieldset-sabnzbd">
						<dt><label for="edituserprefsform[nzbhandling][sabnzbd][url]"><?php echo _('URL naar sabnzbd (inclusief HTTP en portnummer waar sabnzbd geinstalleerd is)?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][sabnzbd][url]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['sabnzbd']['url']); ?>"></dd>

						<dt><label for="edituserprefsform[nzbhandling][sabnzbd][apikey]"><?php echo _('API key voor sabnzbd?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][sabnzbd][apikey]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['sabnzbd']['apikey']); ?>"></dd>
					</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'nzbget')) { ?>
					<fieldset id="nzbhandling-fieldset-nzbget">
						<!-- NZBget -->
						<input type="hidden" name="edituserprefsform[nzbhandling][nzbget][timeout]" value="30">
						
						<dt><label for="edituserprefsform[nzbhandling][nzbget][host]"><?php echo _('Host name van nzbget?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][host]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['host']); ?>"></dd>

						<dt><label for="edituserprefsform[nzbhandling][nzbget][port]"><?php echo _('Port nummer voor nzbget?'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][port]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['port']); ?>"></dd>

						<dt><label for="edituserprefsform[nzbhandling][nzbget][username]"><?php echo _('Username voor nzbget? Let op: op dit moment is all&eacute;&eacute;n <u>nzbget</u> een geldige waarde!'); ?></label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][username]" value="nzbget"></dd>

						<dt><label for="edituserprefsform[nzbhandling][nzbget][password]"><?php echo _('Password voor nzbget?'); ?></label></dt>
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
				<dt><label for="use_boxcar"><?php echo _('Boxcar gebruiken?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][boxcar][enabled]" id="use_boxcar" <?php if ($edituserprefsform['notifications']['boxcar']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_boxcar">
					<dt><label for="edituserprefsform[notifications][boxcar][email]"><?php echo _('Boxcar e-mail adres?'); ?></label></dt>
					<dd><input type="input" name="edituserprefsform[notifications][boxcar][email]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['boxcar']['email']); ?>"></dd>

					<?php showNotificationOptions('boxcar', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>
		
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'email')) { ?>
<!-- E-mail -->
			<fieldset>
				<dt><label for="use_email"><?php echo _('E-mail versturen naar') . ' ' . $currentSession['user']['mail']; ?>?</label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][email][enabled]" id="use_email" <?php if ($edituserprefsform['notifications']['email']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_email">
					<?php showNotificationOptions('email', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'growl')) { ?>
<!-- Growl -->
			<fieldset>
				<dt><label for="use_growl"><?php echo _('Growl gebruiken?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][growl][enabled]" id="use_growl" <?php if ($edituserprefsform['notifications']['growl']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_growl">
					<dt><label for="edituserprefsform[notifications][growl][host]"><?php echo _('Growl IP-adres?'); ?></label></dt>
					<dd><input type="input" name="edituserprefsform[notifications][growl][host]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['growl']['host']); ?>"></dd>

					<dt><label for="edituserprefsform[notifications][growl][password]"><?php echo _('Growl wachtwoord?'); ?></label></dt>
					<dd><input type="password" name="edituserprefsform[notifications][growl][password]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['growl']['password']); ?>"></dd>

					<?php showNotificationOptions('growl', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'nma')) { ?>
<!-- Notify My Android -->
			<fieldset>
				<dt><label for="use_nma"><?php echo _('Notify My Android gebruiken?'); ?></label></dt>
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
				<dt><label for="use_notifo"><?php echo _('Notifo gebruiken?'); ?></label></dt>
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
				<dt><label for="use_prowl"><?php echo _('Prowl gebruiken?'); ?></label></dt>
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
				<dt><label for="use_twitter"><?php echo _('Twitter gebruiken?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][twitter][enabled]" id="use_twitter" <?php if ($edituserprefsform['notifications']['twitter']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_twitter">
					<div class="testNotification" id="twitter_result"><b><?php echo _('Klik op de knop "Toestemming Vragen". Dit opent een nieuwe pagina met een PIN nummer.') . '<br />' . _('Let op: als er niets gebeurt, controleer je pop-up blocker'); ?></div>
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
					<label for="edituserprefsform[customcss]"><?php echo _('Custom CSS gebruiken'); ?></label>
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
					<label for="edituserprefsform[newspotdefault_tag]"><?php echo _('De volgende tag standaard invullen'); ?></label>
				</dt>
				<dd>
					<input type="text" name="edituserprefsform[newspotdefault_tag]" maxlength="99" value="<?php echo htmlspecialchars($edituserprefsform['newspotdefault_tag']); ?>">
				</dd>

				<dt>
					<label for="edituserprefsform[newspotdefault_body]"><?php echo _('De volgende body standaard invullen'); ?></label>
				</dt>
				<dd>
					<textarea name="edituserprefsform[newspotdefault_body]" rows="15" cols="80"><?php echo htmlspecialchars($edituserprefsform['newspotdefault_body']); ?></textarea>
				</dd>
			</fieldset>
		</div>
<?php } ?>
<!-- Einde new spot default -->

		<dd>
			<input class="greyButton" type="submit" name="edituserprefsform[submitedit]" value="<?php echo _('Bijwerken'); ?>">
			<input class="greyButton" type="submit" name="edituserprefsform[submitcancel]" value="<?php echo _('Afbreken'); ?>">
		</dd>
	</div>
</form>

<?php
	function showNotificationOptions($provider, $edituserprefsform, $tplHelper) {
		echo "<fieldset>" . PHP_EOL;

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'watchlist_handled') && $tplHelper->allowed(SpotSecurity::spotsec_keep_own_watchlist, '')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][watchlist_handled]\">" . _('Bericht versturen wanneer een spot is toegevoegd aan of verwijderd van de watchlist?') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][watchlist_handled]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['watchlist_handled']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'nzb_handled')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][nzb_handled]\">" . _('Bericht versturen wanneer een NZB is verzonden? Werkt niet voor client-sabnzbd.') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][nzb_handled]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['nzb_handled']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'retriever_finished')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][retriever_finished]\">" . _('Bericht versturen wanneer Spots Updaten klaar is?') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][retriever_finished]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['retriever_finished']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'report_posted') && $tplHelper->allowed(SpotSecurity::spotsec_report_spam, '')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][report_posted]\">" . _('Bericht versturen wanneer Spam Report verzonden is?') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][report_posted]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['report_posted']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'spot_posted') && $tplHelper->allowed(SpotSecurity::spotsec_post_spot, '')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][spot_posted]\">" . _('Bericht versturen wanneer Spot Posten gelukt is?') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][spot_posted]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['spot_posted']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'user_added')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][user_added]\">" . _('Bericht versturen wanneer een gebruiker is toegevoegd?') . "</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][user_added]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['user_added']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		echo "</fieldset>" . PHP_EOL;
	} # notificationOptions

	require_once "includes/footer.inc.php";
