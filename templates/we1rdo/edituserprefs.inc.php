<?php
require_once "includes/header.inc.php";

if (!empty($edituserprefsresult)) {
	include 'includes/form-xmlresult.inc.php';

	echo formResult2Xml($edituserprefsresult, $formmessages, $tplHelper);
} # if

if (empty($edituserprefsresult)) {
	include "includes/form-messages.inc.php";
?>
<h4><a class="closeDetails" title='Sluit "User preferences"'>[x]</a>Gebruikers voorkeuren:</h4>

<form class="edituserprefsform" name="edituserprefsform" action="<?php echo $tplHelper->makeEditUserPrefsAction(); ?>" method="post">
	<input type="hidden" name="edituserprefsform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('edituserprefsform'); ?>">
	<input type="hidden" name="edituserprefsform[buttonpressed]" value="">
	<input type="hidden" name="userid" value="<?php echo $spotuser['userid']; ?>">
	
	<div id="edituserpreferencetabs">
		<ul>
			<li><a href="#edituserpreftab-1"><span>Algemeen</span></a></li>
			<li><a href="#edituserpreftab-2"><span>NZB afhandeling</span></a></li>
<!--
			<li><a href="#edituserpreftab-3"><span>Filters</span></a></li>
-->			
		</ul>
			
		<!-- [ ] Index filter -->
		<!-- [ ] Filters ? -->

		<div id="edituserpreftab-1">
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
							<option <?php if ($edituserprefsform['date_formatting'] == '%a, %d-%b-%Y (%R)') { echo 'selected="selected"'; } ?> value="%a, %d-%b-%Y (%R)"><?php echo strftime("%a, %d-%b-%Y (%R)", time()); ?></option>
							<option <?php if ($edituserprefsform['date_formatting'] == '%d-%m-%Y (%R)') { echo 'selected="selected"'; } ?> value="%d-%m-%Y (%R)"><?php echo strftime("%d-%m-%Y (%R)", time()); ?></option>
						</select>
					</dd>
					
					<dt><label for="edituserprefsform[template]">Template</label></dt>
					<dd>
						<select name="edituserprefsform[template]">
							<option <?php if ($edituserprefsform['template'] == 'we1rdo') { echo 'selected="selected"'; } ?> value="we1rdo" selected>we1rdo (standaard)</option>
<!--
	Deze zijn uitgeocmmentarieerd omdat als je deze kiest, je niet meer terug kan aangezien beide
	templates geen edit-preferences geimplementeerd hebben
	
							<option value="splendid">Splendid</option>
							<option value="mobile">Mobile</option>
-->
						</select>
					</dd>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_view_spotcount_filtered, '')) { ?>					
					<dt><label for="edituserprefsform[count_newspots]">Nieuwe Spots tellen in de lijst met filters</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[count_newspots]" <?php if ($edituserprefsform['count_newspots']) { echo 'checked="checked"'; } ?> value="true"></dd>
} ?>
					
					<dt><label for="edituserprefsform[keep_seenlist]">Bijhouden wat je bekijkt</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_seenlist]" <?php if ($edituserprefsform['keep_seenlist']) { echo 'checked="checked"'; } ?> value="true"></dd>
					
					<dt><label for="edituserprefsform[auto_markasread]">Moeten spots automatisch na elke visit als gelezen worden gemarkeerd?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[auto_markasread]" <?php if ($edituserprefsform['auto_markasread']) { echo 'checked="checked"'; } ?> value="true"></dd>
					
					<dt><label for="edituserprefsform[keep_downloadlist]">Moeten we bijhouden welke downloads er gedaan zijn?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_downloadlist]" <?php if ($edituserprefsform['keep_downloadlist']) { echo 'checked="checked"'; } ?> value="true"></dd>
					
					<dt><label for="edituserprefsform[keep_watchlist]">Moeten we een watchlist bijhouden?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_watchlist]" <?php if ($edituserprefsform['keep_watchlist']) { echo 'checked="checked"'; } ?> value="true"></dd>
					
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

		<div id="edituserpreftab-2">
<?php
/*		
			<fieldset>
				<dl>
					<!-- NZBHANDLING -->
					<dt><label for="edituserprefsform[nzbhandling][action]">Wat moeten we met NZB files doen?</label></dt>
					<dd>
						<select name="edituserprefsform[nzbhandling][action]">
							<option <?php if ($edituserprefsform['nzbhandling']['action'] == "disable") { echo 'selected="selected"'; } ?> value="disable">Geen integratie met download client</option>
							<option <?php if ($edituserprefsform['nzbhandling']['action'] == "push-sabnzbd") { echo 'selected="selected"'; } ?> value="push-sabnzbd">Roep sabnzbd+ aan via HTTP door SpotWeb</option>
							<option <?php if ($edituserprefsform['nzbhandling']['action'] == "client-sabnzbd") { echo 'selected="selected"'; } ?> value="client-sabnzbd">Roep sabnzbd+ aan via de users' browser</option>
							<option <?php if ($edituserprefsform['nzbhandling']['action'] == "save") { echo 'selected="selected"'; } ?> value="save">Save de file op disk</option>
							<option <?php if ($edituserprefsform['nzbhandling']['action'] == "runcommand") { echo 'selected="selected"'; } ?> value="runcommand">Save de file op disk en roep een commando aan</option>
							<option <?php if ($edituserprefsform['nzbhandling']['action'] == "nzbget") { echo 'selected="selected"'; } ?> value="nzbget">Roep NZBGet aan via HTTP door SpotWeb</option>
						</select>
					</dd>

					<dt><label for="edituserprefsform[nzbhandling][local_dir]">Waar moet de file opgeslagen worden?</label></dt>
					<dd><input type="input" name="edituserprefsform[nzbhandling][local_dir]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['local_dir']); ?>"></dd>
					
					<dt><label for="edituserprefsform[nzbhandling][action]">Wat moeten we met NZB files doen?</label></dt>
					<dd>
						<select name="edituserprefsform[nzbhandling][prepare_action]">
							<option <?php if ($edituserprefsform['nzbhandling']['prepare_action'] == "merge") { echo 'selected="selected"'; } ?> value="merge">Voeg de nzb files samen</option>
							<option <?php if ($edituserprefsform['nzbhandling']['prepare_action'] == "zip") { echo 'selected="selected"'; } ?> value="zip">Comprimeer de NZB files in 1 zip bestand</option>
						</select>
					</dd>
					
					<dt><label for="edituserprefsform[nzbhandling][command]">Welk programma moet uitgevoerd worden?</label></dt>
					<dd><input type="input" name="edituserprefsform[nzbhandling][command]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['command']); ?>"></dd>

					<!-- Sabnzbd -->
					<dt><label for="edituserprefsform[nzbhandling][sabnzbd][host]">Host name van sabnzbd?</label></dt>
					<dd><input type="input" name="edituserprefsform[nzbhandling][sabnzbd][host]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['sabnzbd']['host']); ?>"></dd>

					<dt><label for="edituserprefsform[nzbhandling][sabnzbd][apikey]">API key voor sabnzbd?</label></dt>
					<dd><input type="input" name="edituserprefsform[nzbhandling][sabnzbd][apikey]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['sabnzbd']['apikey']); ?>"></dd>

					<!-- NZBget -->
					<dt><label for="edituserprefsform[nzbhandling][nzbget][host]">Host name van nzbget?</label></dt>
					<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][host]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['host']); ?>"></dd>

					<dt><label for="edituserprefsform[nzbhandling][nzbget][port]">Port nummer voor nzbget?</label></dt>
					<dd><input type="input" name="edituserprefsform[nzbhandling][nzbget][port]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['port']); ?>"></dd>

					<dt><label for="edituserprefsform[nzbhandling][nzbget][username]">Username voor nzbget?</label></dt>
					<dd><input type="input" name="edituserprefsform[nzbhandling][nzbgetusername]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['username']); ?>"></dd>

					<dt><label for="edituserprefsform[nzbhandling][nzbget][password]">Password voor nzbget?</label></dt>
					<dd><input type="password" name="edituserprefsform[nzbhandling][nzbget][password]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['nzbget']['password']); ?>"></dd>
				</dl>
			</fieldset>
*/

?>	
		</div>
<!--	
		<div id="edituserpreftab-3">
			<fieldset>
				<dl>
				</dl>
			</fieldset>
		</div>
-->
		<dd>
			<input class="greyButton" type="submit" name="edituserprefsform[submitedit]" value="Bijwerken">
			<input class="greyButton" type="submit" name="" value="Afbreken">
		</dd>
	</div>
</form>
<?php
}