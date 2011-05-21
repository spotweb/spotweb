<?php
require_once "includes/header.inc.php";

if (!empty($edituserprefsresult)) {
	include 'includes/form-xmlresult.inc.php';

	echo formResult2Xml($edituserprefsresult, $formmessages, $tplHelper);
} # if

if (empty($edituserprefsresult)) {
	include "includes/form-messages.inc.php";
?>
<form class="edituserprefsform" name="edituserprefsform" action="<?php echo $tplHelper->makeEditUserPrefsAction(); ?>" method="post">
	<input type="hidden" name="edituserprefsform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('edituserprefsform'); ?>">
	<input type="hidden" name="edituserprefsform[buttonpressed]" value="">
	<input type="hidden" name="userid" value="<?php echo $edituserprefsform['userid']; ?>">
	
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
							<option value="25">25</option>
							<option value="50">50</option>
							<option value="100">100</option>
							<option value="250">250</option>
						</select>
					</dd>

					<dt><label for="edituserprefsform[date_formatting]">Opmaak van datums</label></dt>
					<dd>
						<select name="edituserprefsform[date_formatting]">
							<option value="human" selected>Human</option>
							<option value="%a, %d-%b-%Y (%H:%M)">Th, 12-jun-1980 (12:00)</option>
							<option value="%d-%m-%Y (%H:%M)">12-06-1980 (12:00)</option>
						</select>
					</dd>
					
					<dt><label for="edituserprefsform[template]">Template</label></dt>
					<dd>
						<select name="edituserprefsform[template]">
							<option value="we1rdo" selected>we1rdo (standaard)</option>
<!--
	Deze zijn uitgeocmmentarieerd omdat als je deze kiest, je niet meer terug kan aangezien beide
	templates geen edit-preferences geimplementeerd hebben
	
							<option value="splendid">Splendid</option>
							<option value="mobile">Mobile</option>
-->
						</select>
					</dd>

					<dt><label for="edituserprefsform[count_newspots]">Nieuwe Spots tellen in de lijst met filters</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[count_newspots]" checked="checked" value="<?php echo htmlspecialchars($edituserprefsform['count_newspots']); ?>"></dd>
					
					<dt><label for="edituserprefsform[keep_seenlist]">Bijhouden wat je bekijkt</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_seenlist]" checked="checked" value="<?php echo htmlspecialchars($edituserprefsform['keep_seenlist']); ?>"></dd>
					
					<dt><label for="edituserprefsform[auto_markasread]">Moeten spots automatisch na elke visit als gelezen worden gemarkeerd?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[auto_markasread]" checked="checked" value="<?php echo htmlspecialchars($edituserprefsform['auto_markasread']); ?>"></dd>
					
					<dt><label for="edituserprefsform[keep_downloadlist]">Moeten we bijhouden welke downloads er gedaan zijn?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_downloadlist]" checked="checked" value="<?php echo htmlspecialchars($edituserprefsform['keep_downloadlist']); ?>"></dd>
					
					<dt><label for="edituserprefsform[keep_watchlist]">Moeten we een watchlist bijhouden?</label></dt>
					<dd><input type="checkbox" name="edituserprefsform[keep_watchlist]" checked="checked" value="<?php echo htmlspecialchars($edituserprefsform['keep_watchlist']); ?>"></dd>
					
					<dt><label for="edituserprefsform[search_url]">Welke zoekmachine moet er gebruikt worden?</label></dt>
					<dd>
						<select name="edituserprefsform[search_url]">
							<option value="http://www.binsearch.info/?adv_age=&amp;q=$SPOTFNAME">binsearch</option>
							<option value="http://nzbindex.nl/search/?q=$SPOTFNAME">nzbindex</option>
						</select>
					</dd>

				</dl>
			</fieldset>
		</div>
		
		<div id="edituserpreftab-2">
			<fieldset>
				<dl>
					<!-- NZBHANDLING -->
					<dt><label for="edituserprefsform[nzbhandling][action]">Wat moeten we met NZB files doen?</label></dt>
					<dd>
						<select name="edituserprefsform[nzbhandling][action]">
							<option value="disable">Geen integratie met download client</option>
							<option value="push-sabnzbd">Roep sabnzbd+ aan via HTTP door SpotWeb</option>
							<option value="client-sabnzbd">Roep sabnzbd+ aan via de users' browser</option>
							<option value="save">Save de file op disk</option>
							<option value="runcommand">Save de file op disk en roep een commando aan</option>
							<option value="nzbget">Roep NZBGet aan via HTTP door SpotWeb</option>
						</select>
					</dd>

					<dt><label for="edituserprefsform[nzbhandling][local_dir]">Waar moet de file opgeslagen worden?</label></dt>
					<dd><input type="input" name="edituserprefsform[nzbhandling][local_dir]" value="<?php echo htmlspecialchars($edituserprefsform['nzbhandling']['local_dir']); ?>"></dd>
					
					<dt><label for="edituserprefsform[nzbhandling][action]">Wat moeten we met NZB files doen?</label></dt>
					<dd>
						<select name="edituserprefsform[nzbhandling][prepare_action]">
							<option value="merge">Voeg de nzb files samen</option>
							<option value="zip">Comprimeer de NZB files in 1 zip bestand</option>
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
		</dd>
	</div>
</form>
<?php
}