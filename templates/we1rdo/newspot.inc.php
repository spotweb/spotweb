<?php 
if (!empty($postresult)) {
	include 'includes/form-xmlresult.inc.php';
	
	echo formResult2Xml($postresult, $formmessages, $tplHelper);
} 

if (empty($postresult)) {
	if (isset($formmessages)) {
		include "includes/form-messages.inc.php"; 
	} # if
?>

<div class="newspotdiv">
	<form class="newspotform" name="newspotform" id="newspotform" action="<?php echo $tplHelper->makePostSpotAction(); ?>" method="post"  enctype="multipart/form-data">
		<input type="hidden" name="newspotform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('newspotform'); ?>">
		<input type="hidden" name="newspotform[newmessageid]" value="">
		<input type="hidden" name="newspotform[submit]" value="Post">
		<fieldset>

		<div>
			<div class="categorydropdown">
				<dt id='txtcategory'>Category</dt>
				<dd>
					<select id='spotcategoryselectbox' name='newspotform[category]' onchange="categorySelectChanged()">
			<?php foreach(SpotCategories::$_head_categories as $catnr => $catvalue) { ?>
							<option value="<?php echo $catnr; ?>"><?php echo $catvalue; ?></option>
			<?php } ?>
					</select>
				</dd>
			</div>
			
			<div class="categorydropdown">
				<dt id='txtsubcatz'>Type</dt>
				<dd>
					<select id='subcatzselectbox' name='newspotform[subcatz]' onchange="categorySelectChanged()">
					</select>
				</dd>
			</div>

			<div class="categorydropdown">
				<dt id='txtsubcata'>Formaat</dt>
				<dd>
					<select id='subcataselectbox' name='newspotform[subcata]'>
					</select>
				</dd>
			</div>
		</div>

		<div class='clear'></div>
	
		<dt><label for="newspotform[title]">Titel</label></dt>
		<dd><input type="text" name="newspotform[title]" size="60" maxlength="60" value=""></dd>

		<dt><label for="newspotform[body]">Omschrijving</label></dt>
		<dd><textarea name="newspotform[body]" id="newspotform[body]" cols="70" rows="8"><?php echo htmlspecialchars($currentSession['user']['prefs']['newspotdefault_body']); ?></textarea><br />
<?php
	$smileyList = $tplHelper->getSmileyList();
	foreach ($smileyList as $name => $image) {
		echo "<a onclick=\"addText(' [img=" . $name . "]', 'newspotform[body]'); return false;\"><img src=\"" . $image . "\" alt=\"" . $name . "\" name=\"" . $name . "\"></a> ";
	}
?>
		</dd>

		<dt><label for="newspotform[tag]">Tag</label></dt>
		<dd><input type="text" name="newspotform[tag]" size="94" maxlength="99" value="<?php echo htmlspecialchars($currentSession['user']['prefs']['newspotdefault_tag']); ?>"></dd>

		<dt><label for="newspotform[website]">Website</label></dt>
		<dd><input type="text" name="newspotform[website]" size="94" maxlength="255" value=""></dd>

		<dt><label for="newspotform[nzbfile]">NZB bestand</label></dt>
		<dd><input name="newspotform[nzbfile]" size="82" type="file" /></dd>

		<dt><label for="newspotform[imagefile]">Afbeelding</label></dt>
		<dd><input name="newspotform[imagefile]" size="82" type="file" /></dd>

		<div class='clear'></div>

		<div>
			<div class="subcategorylistbox">
				<dt id='txtsubcatb'>Bron</dt>
				<dd>
					<select id='subcatbselectbox' name='newspotform[subcatb][]' multiple='multiple' size='8'>
					</select>
				</dd>
			</div>
			
			<div class="subcategorylistbox">
				<dt id='txtsubcatc'>Taal</dt>
				<dd>
					<select id='subcatcselectbox' name='newspotform[subcatc][]' multiple='multiple' size='8'>
					</select>
				</dd>
			</div>
			
			<div class="subcategorylistbox">
				<dt id='txtsubcatd'>Genre</dt>
				<dd>
					<select id='subcatdselectbox' name='newspotform[subcatd][]' multiple='multiple' size='8'>
					</select>
				</dd>
			</div>
		</div>
		
		<div class='clear'><br></div>

		<dd><input class="greyButton" type="submit" name="dummySubmit" value="Toevoegen"></dd>
		
		</fieldset>
	</form>
</div>

<script type='text/javascript'>
	categorySelectChanged();
</script>

<?php
	}
