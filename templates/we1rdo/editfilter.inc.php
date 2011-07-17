<?php
if (!empty($editresult)) {
	include 'includes/form-xmlresult.inc.php';

	echo formResult2Xml($editresult, $formmessages, $tplHelper);
} # if

if (empty($editresult)) {
	# is form voor het toevoegen van een groep ipv wijzigen van een
	$isNew = (isset($data['isnew']));
	
	# vraag de opgegeven filter op
	if (!$isNew) {
		$filter = $tplHelper->getUserFilter($data['filterid']);
	} else {
		$filter = array('title' => '', 'icon' => '');
	}# if

	# bereid alvast een UL voor voor de errors e.d., worden er later
	# via AJAX ingegooid
	include "includes/form-messages.inc.php";
	
?>

	<!-- Naam van filter wijzigen of nieuwe filter toevoegen -->
	<fieldset>
		<form class="editfilterform" name="editfilterform" action="<?php echo $tplHelper->makeEditFilterAction(); ?>" method="post">
			<input type="hidden" name="editfilterform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>">
<?php if (!$isNew) { ?>			
			<input type="hidden" name="filterid" value="<?php echo $filter['id']; ?>">
<?php } else {  ?>
			<input type="hidden" name="filterid" value="9999">
			<input type="hidden" name="editfilterform[tree]" value="<?php echo htmlspecialchars($search['tree']); ?>"></input>
			<input type="hidden" name="editfilterform[valuelist]" value="<?php echo implode('&', array_map('urlencode', $search['value'])); ?>"></input>
<?php } ?>
			
			<dt><label for="editfilterform[title]">Naam</label></dt>
			<dd>
				<input type="text" name="editfilterform[title]" value="<?php echo htmlspecialchars($filter['title']); ?>"></input>
			</dd>

			<dt><label for="editfilterform[icon]">Icoon</label></dt>
			<dd>
				<select name="editfilterform[icon]">
					<option <?php if ($filter['icon'] == 'application.png') { echo ' selected="selected" '; } ?> value='application.png'>Applicatie</option>
					<option <?php if ($filter['icon'] == 'bluray.png') { echo ' selected="selected" '; } ?> value='bluray.png'>Blu-Ray</option>
					<option <?php if ($filter['icon'] == 'book.png') { echo ' selected="selected" '; } ?> value='book.png'>Boek</option>
					<option <?php if ($filter['icon'] == 'controller.png') { echo ' selected="selected" '; } ?> value='controller.png'>Game</option>
					<option <?php if ($filter['icon'] == 'custom.png') { echo ' selected="selected" '; } ?> value='custom.png'>Eigen</option>
					<option <?php if ($filter['icon'] == 'divx.png') { echo ' selected="selected" '; } ?> value='divx.png'>DivX</option>
					<option <?php if ($filter['icon'] == 'female.png') { echo ' selected="selected" '; } ?> value='female.png'>Erotiek</option>
					<option <?php if ($filter['icon'] == 'film.png') { echo ' selected="selected" '; } ?> value='film.png'>Film</option>
					<option <?php if ($filter['icon'] == 'hd.png') { echo ' selected="selected" '; } ?> value='hd.png'>HD</option>
					<option <?php if ($filter['icon'] == 'ipod.png') { echo ' selected="selected" '; } ?> value='ipod.png'>iPod</option>
					<option <?php if ($filter['icon'] == 'linux.png') { echo ' selected="selected" '; } ?> value='linux.png'>Linux</option>
					<option <?php if ($filter['icon'] == 'mac.png') { echo ' selected="selected" '; } ?> value='mac.png'>Apple</option>
					<option <?php if ($filter['icon'] == 'mpg.png') { echo ' selected="selected" '; } ?> value='mpg.png'>MPEG</option>
					<option <?php if ($filter['icon'] == 'music.png') { echo ' selected="selected" '; } ?> value='music.png'>Muziek</option>
					<option <?php if ($filter['icon'] == 'nintendo_ds.png') { echo ' selected="selected" '; } ?> value='nintendo_ds.png'>Nintendo DS</option>
					<option <?php if ($filter['icon'] == 'nintendo_wii.png') { echo ' selected="selected" '; } ?> value='nintendo_wii.png'>Nintendo Wii</option>
					<option <?php if ($filter['icon'] == 'phone.png') { echo ' selected="selected" '; } ?> value='phone.png'>Telefoon</option>
					<option <?php if ($filter['icon'] == 'picture.png') { echo ' selected="selected" '; } ?> value='picture.png'>Afbeelding</option>
					<option <?php if ($filter['icon'] == 'playstation.png') { echo ' selected="selected" '; } ?> value='playstation.png'>Playstation</option>
					<option <?php if ($filter['icon'] == 'tv.png') { echo ' selected="selected" '; } ?> value='tv.png'>TV</option>
					<option <?php if ($filter['icon'] == 'vista.png') { echo ' selected="selected" '; } ?> value='vista.png'>Vista</option>
					<option <?php if ($filter['icon'] == 'windows.png') { echo ' selected="selected" '; } ?> value='windows.png'>Windows</option>
					<option <?php if ($filter['icon'] == 'wmv.png') { echo ' selected="selected" '; } ?> value='wmv.png'>WMV</option>
					<option <?php if ($filter['icon'] == 'xbox.png') { echo ' selected="selected" '; } ?> value='xbox.png'>Xbox</option>
					<option <?php if ($filter['icon'] == 'dvd.png') { echo ' selected="selected" '; } ?> value='dvd.png'>DVD</option>
					<option <?php if ($filter['icon'] == 'pda.png') { echo ' selected="selected" '; } ?> value='pda.png'>PDA</option>
				</select>
			</dd>
			
			<dd>
<?php if ($isNew) { ?>			
				<input class="smallGreyButton" type="submit" name="editfilterform[submitaddfilter]" value="Voeg toe">
<?php } else { ?>
				<input class="smallGreyButton" type="submit" name="editfilterform[submitchangefilter]" value="Wijzig">
<?php } ?>
			</dd>
		</form>
	</fieldset>
<?php
	require_once "includes/footer.inc.php";

	} # if not only  xml
	
