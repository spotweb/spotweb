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
<div id='editdialogdiv'></div>

<form class="edituserprefsform" name="edituserprefsform" action="<?php echo $tplHelper->makeEditUserPrefsAction(); ?>" method="post">
	<input type="hidden" name="edituserprefsform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('edituserprefsform'); ?>">
	<input type="hidden" name="edituserprefsform[http_referer]" value="<?php echo $http_referer; ?>">
	<input type="hidden" name="edituserprefsform[buttonpressed]" value="">
	<input type="hidden" name="userid" value="<?php echo $spotuser['userid']; ?>">
	
	<div id="edituserpreferencetabs" class="ui-tabs">
		<ul>
			<li><a href="#edituserpreftab-1"><span>Algemeen</span></a></li>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, '')) { ?>
			<li><a href="#edituserpreftab-2"><span>NZB afhandeling</span></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_filters, '')) { ?>
			<li><a href="#edituserpreftab-3"><span>Filters</span></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, '') && $tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, '')) { ?>
			<li><a href="#edituserpreftab-4"><span>Notificaties</span></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_allow_custom_stylesheet, '')) { ?>
			<li><a href="#edituserpreftab-5"><span>Eigen CSS</span></a></li>
<?php } ?>
	
		</ul>
			
		<div id="edituserpreftab-1" class="ui-tabs-hide">
			<fieldset>
				<dl>
					<dt><label for="edituserprefsform[perpage]">Aantal items per pagina?</label></dt>
					<dd>
						<select name="edituserprefsform[perpage]">
							<option <?php if ($edituserprefsform['perpage'] == 25) { echo 'selected="selected"'; } ?> value="25">25</option>
							<option <?php if ($edituserprefsform['perpage'] == 50) { echo 'selected="selected"'; } ?> value="50">50</option>
							<option <?php if ($edituserprefsform['perpage'] == 100) { echo 'selected="selected"'; } ?> value="100">100</option>
							<option <?php if ($edituserprefsform['perpage'] == 250) { echo 'selected="selected"'; } ?> value="250">250</option>
						</select>
					</dd>

					<dt><label for="edituserprefsform[date_formatting]">Opmaak van datums</label></dt>
					<dd>
						<select name="edituserprefsform[date_formatting]">
							<option <?php if ($edituserprefsform['date_formatting'] == 'human') { echo 'selected="selected"'; } ?> value="human" selected>Human</option>
							<option <?php if ($edituserprefsform['date_formatting'] == '%a, %d-%b-%Y (%H:%M)') { echo 'selected="selected"'; } ?> value="%a, %d-%b-%Y (%H:%M)"><?php echo strftime("%a, %d-%b-%Y (%H:%M)", time()); ?></option>
							<option <?php if ($edituserprefsform['date_formatting'] == '%d-%m-%Y (%H:%M)') { echo 'selected="selected"'; } ?> value="%d-%m-%Y (%H:%M)"><?php echo strftime("%d-%m-%Y (%H:%M)", time()); ?></option>
						</select>
					</dd>
					
					<dt><label for="edituserprefsform[template]">Template</label></dt>
					<dd>
						<select name="edituserprefsform[template]">
							<option <?php if ($edituserprefsform['template'] == 'we1rdo') { echo 'selected="selected"'; } ?> value="we1rdo" selected>we1rdo (standaard)</option>
<!--
	Deze zijn uitgecommentarieerd omdat als je deze kiest, je niet meer terug kan aangezien beide
	templates geen edit-preferences geimplementeerd hebben
	
							<option value="splendid">Splendid</option>
							<option value="mobile">Mobile</option>
-->
						</select>
					</dd>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_view_spotcount_filtered, '')) { ?>					
					<dt><label for="edituserprefsform[count_newspots]">Nieuwe Spots tellen in de lijst met filters</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[count_newspots]" <?php if ($edituserprefsform['count_newspots']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_seenlist, '')) { ?>					
					<dt><label for="edituserprefsform[keep_seenlist]">Bijhouden wat je bekijkt</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_seenlist]" <?php if ($edituserprefsform['keep_seenlist']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
					<dt><label for="edituserprefsform[auto_markasread]">Moeten spots automatisch na elke visit als gelezen worden gemarkeerd?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[auto_markasread]" <?php if ($edituserprefsform['auto_markasread']) { echo 'checked="checked"'; } ?>></dd>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_downloadlist, '')) { ?>					
					<dt><label for="edituserprefsform[keep_downloadlist]">Moeten we bijhouden welke downloads er gedaan zijn?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_downloadlist]" <?php if ($edituserprefsform['keep_downloadlist']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_watchlist, '')) { ?>
					<dt><label for="edituserprefsform[keep_watchlist]">Moeten we een watchlist bijhouden?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_watchlist]" <?php if ($edituserprefsform['keep_watchlist']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>

					<dt><label for="edituserprefsform[show_filesize]">Toon bestandsgrootte in spotoverzicht?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[show_filesize]" <?php if ($edituserprefsform['show_filesize']) { echo 'checked="checked"'; } ?>></dd>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, '')) { ?>
					<dt><label for="edituserprefsform[show_multinzb]">Toon een checkbox naast elke spot om meerdere NZB files in een keer te downloaden?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[show_multinzb]" <?php if ($edituserprefsform['show_multinzb']) { echo 'checked="checked"'; } ?>></dd>
<?php } ?>
					
					<dt><label for="edituserprefsform[nzb_search_engine]">Welke zoekmachine moet er gebruikt worden?</label></dt>
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
					<dt><label for="edituserprefsform[nzbhandling][action]">Wat moeten we met NZB files doen?</label></dt>
					<dd>
						<select id="nzbhandlingselect" name="edituserprefsform[nzbhandling][action]">
							<option data-fields="" <?php if ($edituserprefsform['nzbhandling']['action'] == "disable") { echo 'selected="selected"'; } ?> value="disable">Geen integratie met download client</option>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'push-sabnzbd')) { ?>
							<option data-fields="sabnzbd" <?php if ($edituserprefsform['nzbhandling']['action'] == "push-sabnzbd") { echo 'selected="selected"'; } ?> value="push-sabnzbd">Roep sabnzbd+ aan via HTTP door SpotWeb</option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'client-sabnzbd')) { ?>
							<option data-fields="sabnzbd" <?php if ($edituserprefsform['nzbhandling']['action'] == "client-sabnzbd") { echo 'selected="selected"'; } ?> value="client-sabnzbd">Roep sabnzbd+ aan via de users' browser</option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'save')) { ?>
							<option data-fields="localdir" <?php if ($edituserprefsform['nzbhandling']['action'] == "save") { echo 'selected="selected"'; } ?> value="save">Save de file op disk</option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'runcommand')) { ?>
							<option data-fields="localdir runcommand" <?php if ($edituserprefsform['nzbhandling']['action'] == "runcommand") { echo 'selected="selected"'; } ?> value="runcommand">Save de file op disk en roep een commando aan</option>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'nzbget')) { ?>
							<option data-fields="nzbget" <?php if ($edituserprefsform['nzbhandling']['action'] == "nzbget") { echo 'selected="selected"'; } ?> value="nzbget">Roep NZBGet aan via HTTP door SpotWeb</option>
<?php } ?>
						</select>
					</dd>

					<dt><label for="edituserprefsform[nzbhandling][prepare_action]">Wat moeten we met meerdere NZB files doen?</label></dt>
					<dd>
						<select name="edituserprefsform[nzbhandling][prepare_action]">
							<option <?php if ($edituserprefsform['nzbhandling']['prepare_action'] == "merge") { echo 'selected="selected"'; } ?> value="merge">Voeg de nzb files samen</option>
							<option <?php if ($edituserprefsform['nzbhandling']['prepare_action'] == "zip") { echo 'selected="selected"'; } ?> value="zip">Comprimeer de NZB files in 1 zip bestand</option>
						</select>
					</dd>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'save') || $tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'runcommand')) { ?>
					<fieldset id="nzbhandling-fieldset-localdir">
						<dt><label for="edituserprefsform[nzbhandling][local_dir]">Waar moet de file opgeslagen worden?</label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][local_dir]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['local_dir']); ?>"></dd>
					</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'runcommand')) { ?>
					<fieldset id="nzbhandling-fieldset-runcommand">
						<dt><label for="edituserprefsform[nzbhandling][command]">Welk programma moet uitgevoerd worden?</label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][command]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['command']); ?>"></dd>
					</fieldset>
<?php } ?>

					<!-- Sabnzbd -->
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'push-sabnzbd') || $tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'client-sabnzbd')) { ?>
					<fieldset id="nzbhandling-fieldset-sabnzbd">
						<dt><label for="edituserprefsform[nzbhandling][sabnzbd][url]">URL naar sabnzbd (inclusief HTTP en portnummer waar sabnzbd geinstalleerd is)?</label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][sabnzbd][url]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['sabnzbd']['url']); ?>"></dd>

						<dt><label for="edituserprefsform[nzbhandling][sabnzbd][apikey]">API key voor sabnzbd?</label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][sabnzbd][apikey]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['sabnzbd']['apikey']); ?>"></dd>
					</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_download_integration, 'nzbget')) { ?>
					<fieldset id="nzbhandling-fieldset-nzbget">
						<!-- NZBget -->
						<input type="hidden" name="edituserprefsform[nzbhandling][nzbget][timeout]" value="30">
						
						<dt><label for="edituserprefsform[nzbhandling][nzbget][host]">Host name van nzbget?</label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][host]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['host']); ?>"></dd>

						<dt><label for="edituserprefsform[nzbhandling][nzbget][port]">Port nummer voor nzbget?</label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][port]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['port']); ?>"></dd>

						<dt><label for="edituserprefsform[nzbhandling][nzbget][username]">Username voor nzbget?</label></dt>
						<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][username]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['username']); ?>"></dd>

						<dt><label for="edituserprefsform[nzbhandling][nzbget][password]">Password voor nzbget?</label></dt>
						<dd><input type="password" name="edituserprefsform[nzbhandling][nzbget][password]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['password']); ?>"></dd>
					</fieldset>
<?php } ?>
				</dl>
			</fieldset>
		</div>
<?php } ?>

		<div id="edituserpreftab-3">
			<div class='filter'>
				<ul id='filterlist' class='filterlist'>
<?php			
	function processFilters($tplHelper, $filterList) {
		foreach($filterList as $filter) {
			# Output de HTML
			echo '<li class="sortable-element-class ' . $tplHelper->filter2cat($filter['tree']) . '" id="orderfilterslist_' . $filter['id'];
			echo '"><div><a href="" onclick="return openDialog(\'editdialogdiv\', \'Bewerk een filter\', \'?page=render&tplname=editfilter&data[filterid]=' . $filter['id'] . '\', \'editfilterform\', true, function() { refreshTab(\'edituserpreferencetabs\')});">';
			echo '<img src="images/icons/' . $filter['icon'] . '" alt="' . $filter['title'] . '">' . $filter['title'] . ' (' . $filter['id'] . ')' . '</a></div>';
			
			# Als er children zijn, output die ool
			if (!empty($filter['children'])) {
				echo '<ul>';
				processFilters($tplHelper, $filter['children']);
				echo '</ul>';
			} # if
			
			echo '</li>' . PHP_EOL;
		} # foreach
	} # processFilters
	
	processFilters($tplHelper, $tplHelper->getUserFilterList());
?>
			</ul>
	
<script type='text/javascript'>	
	var $filterlist = $('#filterlist');
	$filterlist.nestedSortable({
		opacity: .6,
		tabSize: 15,
        forcePlaceholderSize: true,
		forceHelperSize: true,
		maxLevels: 4,
		helper:	'clone',
		items: 'li',
		tabSize: 25,
		listType: 'ul',
		handle: 'div',
		placeholder: 'placeholder',
		revert: 250,
		tolerance: 'pointer',
		update: function() {
			var serialized = $filterlist.nestedSortable('serialize');
			var csrfcookie = '<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>';
			var formdata = 'editfilterform[xsrfid]=' + csrfcookie + '&editfilterform[submitreorder]=true&' + serialized;
			
			// post de data
			$.ajax({
				type: "POST",
				url: '?page=editfilter',
				dataType: "html",
				data: formdata,
				success: function(xml) {
					//alert(xml);
				} // success
			}); // ajax call om de form te submitten
		} 
	});				
</script>
			</div>
		</div>

<!-- Notificaties -->
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, '') && $tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, '')) { ?>
		<div id="edituserpreftab-4">
		
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'email')) { ?>
<!-- E-mail -->
			<fieldset>
				<dt><label for="use_email">E-mail versturen naar <?php echo $currentSession['user']['mail']; ?>?</label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][email][enabled]" id="use_email" <?php if ($edituserprefsform['notifications']['email']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_email">
					<?php showNotificationOptions('email', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'growl')) { ?>
<!-- Growl -->
			<fieldset>
				<dt><label for="use_growl">Growl gebruiken?</label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][growl][enabled]" id="use_growl" <?php if ($edituserprefsform['notifications']['growl']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_growl">
					<dt><label for="edituserprefsform[notifications][growl][host]">Growl IP-adres?</label></dt>
					<dd><input type="input" name="edituserprefsform[notifications][growl][host]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['growl']['host']); ?>"></dd>

					<dt><label for="edituserprefsform[notifications][growl][password]">Growl wachtwoord?</label></dt>
					<dd><input type="password" name="edituserprefsform[notifications][growl][password]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['growl']['password']); ?>"></dd>

					<?php showNotificationOptions('growl', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'notifo')) { ?>
<!-- Notifo -->
			<fieldset>
				<dt><label for="use_notifo">Notifo gebruiken?</label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][notifo][enabled]" id="use_notifo" <?php if ($edituserprefsform['notifications']['notifo']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_notifo">
					<dt><label for="edituserprefsform[notifications][notifo][username]">Notifo Username?</label></dt>
					<dd><input type="input" name="edituserprefsform[notifications][notifo][username]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['notifo']['username']); ?>"></dd>

					<dt><label for="edituserprefsform[notifications][notifo][api]">Notifo <a href="http://notifo.com/user/settings">API secret</a>?</label></dt>
					<dd><input type="text" name="edituserprefsform[notifications][notifo][api]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['notifo']['api']); ?>"></dd>

					<?php showNotificationOptions('notifo', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
<?php } ?>

<?php if (version_compare(PHP_VERSION, '5.3.0') >= 0) { ?>
	<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'prowl')) { ?>
<!-- Prowl -->
			<fieldset>
				<dt><label for="use_prowl">Prowl gebruiken?</label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][prowl][enabled]" id="use_prowl" <?php if ($edituserprefsform['notifications']['prowl']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_prowl">
					<dt><label for="edituserprefsform[notifications][prowl][apikey]">Prowl <a href="https://www.prowlapp.com/api_settings.php">API key</a>?</label></dt>
					<dd><input type="text" name="edituserprefsform[notifications][prowl][apikey]" value="<?php echo htmlspecialchars($edituserprefsform['notifications']['prowl']['apikey']); ?>"></dd>

					<?php showNotificationOptions('prowl', $edituserprefsform, $tplHelper); ?>
				</fieldset>
			</fieldset>
	<?php } ?>
<?php } ?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_services, 'twitter')) { ?>
<!-- Twitter -->
			<fieldset>
				<dt><label for="use_twitter">Twitter gebruiken?</label></dt>
				<dd><input type="checkbox" class="enabler" name="edituserprefsform[notifications][twitter][enabled]" id="use_twitter" <?php if ($edituserprefsform['notifications']['twitter']['enabled']) { echo 'checked="checked"'; } ?>></dd>

				<fieldset id="content_use_twitter">
					<div class="testNotification" id="twitter_result"><b>Stap 1</b>:<br />Klik op de knop "Toestemming Vragen". Dit opent een nieuwe pagina met een PIN nummer.<br />Let op: als er niets gebeurt, controleer je pop-up blocker.</div>
					<input type="button" value="Toestemming Vragen" id="twitter_request_auth" />
	<?php if (!empty($edituserprefsform['notifications']['twitter']['screen_name'])) { ?>
					<input type="button" id="twitter_remove" value="Account <?php echo $edituserprefsform['notifications']['twitter']['screen_name']; ?> verwijderen" />
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
					<label for="edituserprefsform[customcss]">Custom CSS gebruiken</label>
				</dt>
				<dd>
					<textarea name="edituserprefsform[customcss]" rows="15" cols="120"><?php echo $edituserprefsform['customcss']; ?></textarea>
				</dd>
			</fieldset>
		</div>
<?php } ?>
<!-- Einde Custom Stylesheet -->

		<dd>
			<input class="greyButton" type="submit" name="edituserprefsform[submitedit]" value="Bijwerken">
			<input class="greyButton" type="submit" name="edituserprefsform[submitcancel]" value="Afbreken">
		</dd>
	</div>
</form>

<?php
	function showNotificationOptions($provider, $edituserprefsform, $tplHelper) {
		echo "<fieldset>" . PHP_EOL;

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'watchlist_handled')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][watchlist_handled]\">Bericht versturen wanneer een spot is toegevoegd aan of verwijderd van de watchlist?</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][watchlist_handled]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['watchlist_handled']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'nzb_handled')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][nzb_handled]\">Bericht versturen wanneer een NZB is verzonden? Werkt niet voor client-sabnzbd.</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][nzb_handled]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['nzb_handled']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'retriever_finished')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][retriever_finished]\">Bericht versturen wanneer Spots Updaten klaar is?</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][retriever_finished]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['retriever_finished']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'user_added')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][user_added]\">Bericht versturen wanneer een gebruiker is toegevoegd?</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][user_added]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['user_added']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		echo "</fieldset>" . PHP_EOL;
	} # notificationOptions

	require_once "includes/footer.inc.php";
