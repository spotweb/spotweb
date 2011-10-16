<?php
if ((isset($lastformaction) && ($lastformaction == 'exportfilters'))) {
	Header('Content-Type: text/xml; charset=UTF-8');
	Header('Content-Disposition: attachment; filename="spotwebfilters.xml"');
	
	echo $editresult;
	return ;
} # if

if ((isset($lastformaction) && ($lastformaction == 'importfilters'))) {
	$tplHelper->redirect($http_referer);
} # if

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
			<input type="hidden" name="editfilterform[tree]" value="<?php echo (isset($search['tree']) ? htmlspecialchars($search['tree'], ENT_QUOTES, "UTF-8") : ""); ?>"></input>
			<input type="hidden" name="editfilterform[valuelist]" value="<?php echo htmlspecialchars(implode('&', array_map('urlencode', (isset($search['value']) ?$search['value'] : array()) )), ENT_QUOTES, "UTF-8"); ?>"></input>
			<input type="hidden" name="editfilterform[sorton]" value="<?php echo htmlspecialchars($sortby, ENT_QUOTES, "UTF-8"); ?>"></input>
			<input type="hidden" name="editfilterform[sortorder]" value="<?php echo htmlspecialchars($sortdir, ENT_QUOTES, "UTF-8"); ?>"></input>
<?php } ?>
			
			<dt><label for="editfilterform[title]">Naam</label></dt>
			<dd>
				<input type="text" name="editfilterform[title]" value="<?php echo htmlspecialchars($filter['title']); ?>"></input>
			</dd>

			<dt><label for="editfilterform[icon]">Icoon</label></dt>
			<dd>
				<select name="editfilterform[icon]">
					<option <?php if ($filter['icon'] == 'application') { echo ' selected="selected" '; } ?> value='application'>Applicatie</option>
					<option <?php if ($filter['icon'] == 'bluray') { echo ' selected="selected" '; } ?> value='bluray'>Blu-Ray</option>
					<option <?php if ($filter['icon'] == 'book') { echo ' selected="selected" '; } ?> value='book'>Boek</option>
					<option <?php if ($filter['icon'] == 'controller') { echo ' selected="selected" '; } ?> value='controller'>Game</option>
					<option <?php if ($filter['icon'] == 'custom') { echo ' selected="selected" '; } ?> value='custom'>Eigen</option>
					<option <?php if ($filter['icon'] == 'divx') { echo ' selected="selected" '; } ?> value='divx'>DivX</option>
					<option <?php if ($filter['icon'] == 'female') { echo ' selected="selected" '; } ?> value='female'>Erotiek</option>
					<option <?php if ($filter['icon'] == 'film') { echo ' selected="selected" '; } ?> value='film'>Film</option>
					<option <?php if ($filter['icon'] == 'hd') { echo ' selected="selected" '; } ?> value='hd'>HD</option>
					<option <?php if ($filter['icon'] == 'ipod') { echo ' selected="selected" '; } ?> value='ipod'>iPod</option>
					<option <?php if ($filter['icon'] == 'linux') { echo ' selected="selected" '; } ?> value='linux'>Linux</option>
					<option <?php if ($filter['icon'] == 'mac') { echo ' selected="selected" '; } ?> value='mac'>Apple</option>
					<option <?php if ($filter['icon'] == 'mpg') { echo ' selected="selected" '; } ?> value='mpg'>MPEG</option>
					<option <?php if ($filter['icon'] == 'music') { echo ' selected="selected" '; } ?> value='music'>Muziek</option>
					<option <?php if ($filter['icon'] == 'nintendo_ds') { echo ' selected="selected" '; } ?> value='nintendo_ds'>Nintendo DS</option>
					<option <?php if ($filter['icon'] == 'nintendo_wii') { echo ' selected="selected" '; } ?> value='nintendo_wii'>Nintendo Wii</option>
					<option <?php if ($filter['icon'] == 'phone') { echo ' selected="selected" '; } ?> value='phone'>Telefoon</option>
					<option <?php if ($filter['icon'] == 'picture') { echo ' selected="selected" '; } ?> value='picture'>Afbeelding</option>
					<option <?php if ($filter['icon'] == 'playstation') { echo ' selected="selected" '; } ?> value='playstation'>Playstation</option>
					<option <?php if ($filter['icon'] == 'tv') { echo ' selected="selected" '; } ?> value='tv'>TV</option>
					<option <?php if ($filter['icon'] == 'vista') { echo ' selected="selected" '; } ?> value='vista'>Vista</option>
					<option <?php if ($filter['icon'] == 'windows') { echo ' selected="selected" '; } ?> value='windows'>Windows</option>
					<option <?php if ($filter['icon'] == 'wmv') { echo ' selected="selected" '; } ?> value='wmv'>WMV</option>
					<option <?php if ($filter['icon'] == 'xbox') { echo ' selected="selected" '; } ?> value='xbox'>Xbox</option>
					<option <?php if ($filter['icon'] == 'dvd') { echo ' selected="selected" '; } ?> value='dvd'>DVD</option>
					<option <?php if ($filter['icon'] == 'pda') { echo ' selected="selected" '; } ?> value='pda'>PDA</option>
				</select>
			</dd>
			
			<dd>
<?php if ($isNew) { ?>			
				<input class="smallGreyButton" type="submit" name="editfilterform[submitaddfilter]" value="Voeg toe">
<?php } else { ?>
				<input class="smallGreyButton" type="submit" name="editfilterform[submitchangefilter]" value="Wijzig">
				<input class="smallGreyButton" type="submit" name="editfilterform[submitremovefilter]" value="Verwijder">
<?php } ?>
			</dd>
		</form>
	</fieldset>
<?php
	} # if not only  xml
