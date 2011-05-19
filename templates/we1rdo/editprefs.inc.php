<?php
if (!empty($edituserprefsresult)) {
	include 'includes/form-xmlresult.inc.php';

	echo formResult2Xml($edituserprefsresult, $formmessages, $tplHelper);
} # if

if (empty($edituserprefsresult)) {
	include "includes/form-messages.inc.php";
?>
<form class="edituserprefsform" name="edituserprefsform" action="<?php echo $tplHelper->makeEditUserAction(); ?>" method="post">
	<input type="hidden" name="edituserprefsform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('edituserprefsform'); ?>">
	<input type="hidden" name="edituserprefsform[buttonpressed]" value="">
	<input type="hidden" name="userid" value="<?php echo $edituserprefsform['userid']; ?>">
	
	<!-- [ ] Downloader (sanzbd) settings -->
	<!-- [ ] SabNzbd category mapping -->
	<!-- [x] Aantal items per pagina -->
	<!-- [x] Date formatting -->
	<!-- [x] Template -->
	<!-- [ ] Toon NZB knop -->
	<!-- [ ] New spots tellen? -->
	<!-- [ ] Bijhouden wat er bekeken wordt ->
	<!-- [ ] Automatisch markeren wat er gelezen is? -->
	<!-- [ ] Download list bijhouden? -->
	<!-- [ ] Watchlist mogelijkheid bieden? -->
	<!-- [ ] Search URL -->
	<!-- [ ] Index filter -->
	<!-- [ ] Filters ? -->
	
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
					<option value="human" selected>25</option>
					<option value="%a, %d-%b-%Y (%H:%M)">Th, 12-jun-1980 (12:00)</option>
					<option value="%d-%m-%Y (%H:%M)">12-06-1980 (12:00)</option>
				</select>
			</dd>
			
			<dt><label for="edituserprefsform[template]">Template</label></dt>
			<dd>
				<select name="edituserprefsform[template]">
					<option value="we1rdo" selected>we1rdo (standaard)</option>
					<option value="splendid">Splendid</option>
					<option value="mobile">Mobile</option>
				</select>
			</dd>

			<dt><label for="edituserprefsform[perpage]">Aantal items per pagina?</label></dt>
			<dd><input type="text" disabled="disabled" value="<?php echo htmlspecialchars($edituserprefsform['perpage']); ?>"></dd>

			<dd>
				<input class="greyButton" type="submit" name="edituserprefsform[submitedit]" value="Bijwerken">
			</dd>
		</dl>
	</fieldset>
</form>
<?php
}