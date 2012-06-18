<?php 
if (!empty($postresult)) {
	include 'includes/form-xmlresult.inc.php';

	$this->sendContentTypeHeader('xml');
	echo formResult2Xml($postresult, $formmessages, $tplHelper);
} 

if (empty($postresult)) {
	if (isset($formmessages)) {
		include "includes/form-messages.inc.php"; 
	} # if
?>

<div class="newspotdiv">
	<div></div> <!-- Empty div we can set loading to  -->
	<form class="newspotform" name="newspotform" id="newspotform" action="<?php echo $tplHelper->makePostSpotAction(); ?>" method="post"  enctype="multipart/form-data">
		<input type="hidden" name="newspotform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('newspotform'); ?>">
		<input type="hidden" name="newspotform[newmessageid]" value="">
		<input type="hidden" name="newspotform[submitpost]" value="Post">
		<input type="hidden" name="newspotform[randomstr]" value="<?php echo $tplHelper->getCleanRandomString(12); ?>">
		<fieldset>

		<div>
			<div class="categorydropdown">
				<dt id='txtcategory'><?php echo _('Category'); ?></dt>
				<dd>
					<select id='spotcategoryselectbox' name='newspotform[category]' onchange="categorySelectChanged()">
			<?php foreach(SpotCategories::$_head_categories as $catnr => $catvalue) { ?>
							<option value="<?php echo $catnr; ?>"><?php echo $catvalue; ?></option>
			<?php } ?>
					</select>
				</dd>
			</div>
			
			<div class="categorydropdown">
				<dt id='txtsubcatz'><?php echo _('Type'); ?></dt>
				<dd>
					<select id='subcatzselectbox' name='newspotform[subcatz]' onchange="categorySelectChanged()">
					</select>
				</dd>
			</div>

			<div class="categorydropdown">
				<dt id='txtsubcata'><?php echo _('Format'); ?></dt>
				<dd>
					<select id='subcataselectbox' name='newspotform[subcata]'>
					</select>
				</dd>
			</div>
		</div>
		<div class='clear'></div>
	
		<div>
			<dt><label for="newspotform[title]"><?php echo _('Titel'); ?></label></dt>
			<dd><input type="text" name="newspotform[title]" size="60" maxlength="60" value=""></dd>

			<dt><label for="newspotform[body]"><?php echo _('Description'); ?></label></dt>
			<dd><textarea name="newspotform[body]" id="newspotform[body]" cols="70" rows="8"><?php echo htmlspecialchars($currentSession['user']['prefs']['newspotdefault_body']); ?></textarea><br />

	<?php
		$smileyList = $tplHelper->getSmileyList();
		foreach ($smileyList as $name => $image) {
			echo "<a onclick=\"addText(' [img=" . $name . "]', 'newspotform[body]'); return false;\"><img src=\"" . $image . "\" alt=\"" . $name . "\" name=\"" . $name . "\"></a> ";
		}
	?>
			</dd>

			<dt><label for="newspotform[tag]"><?php echo _('Tag'); ?></label></dt>
			<dd><input type="text" name="newspotform[tag]" size="94" maxlength="99" value="<?php echo htmlspecialchars($currentSession['user']['prefs']['newspotdefault_tag']); ?>"></dd>

			<dt><label for="newspotform[website]"><?php echo _('Website'); ?></label></dt>
			<dd><input type="text" name="newspotform[website]" size="94" maxlength="255" value=""></dd>

			<dt><label for="newspotform[nzbfile]"><?php echo _('NZB file'); ?></label></dt>
			<dd><input name="newspotform[nzbfile]" size="82" type="file" /></dd>

			<dt><label for="newspotform[imagefile]"><?php echo _('Picture'); ?></label></dt>
			<dd><input name="newspotform[imagefile]" size="82" type="file" /></dd>
		</div>
		
		<div class='clear'></div>

		<div>
			<div class="subcategorylistbox">
				<dt id='txtsubcatb'><?php echo _('Source'); ?></dt>
				<dd>
					<select id='subcatbselectbox' name='newspotform[subcatb][]' multiple='multiple' size='8'>
					</select>
				</dd>
			</div>
			
			<div class="subcategorylistbox">
				<dt id='txtsubcatc'><?php echo _('Language'); ?></dt>
				<dd>
					<select id='subcatcselectbox' name='newspotform[subcatc][]' multiple='multiple' size='8'>
					</select>
				</dd>
			</div>
			
			<div class="subcategorylistbox">
				<dt id='txtsubcatd'><?php echo _('Genre'); ?></dt>
				<dd>
					<select id='subcatdselectbox' name='newspotform[subcatd][]' multiple='multiple' size='8'>
					</select>
				</dd>
			</div>
		</div>
		
		<div class='clear'><br></div>

		<dd><input class="greyButton" type="submit" name="dummySubmit" value="<?php echo _('Add'); ?>"></dd>
		
		</fieldset>
	</form>
</div>

<script type='text/javascript'>
	categorySelectChanged();
</script>

<?php
	}
