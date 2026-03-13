<?php
require __DIR__.'/includes/form-messages.inc.php';

if (!showResults($result)) {
    $catParams =
            "'".$editspotform['category']."', ".
            "'".$editspotform['subcata']."', ".
            "'".$editspotform['subcatb']."', ".
            "'".$editspotform['subcatc']."', ".
            "'".$editspotform['subcatd']."', ".
            "'".$editspotform['subcatz']."'"; ?>
<div class="editspotdiv">
	<div></div> <!-- Empty div we can set loading to  -->
	<form class="editspotform" name="editspotform" id="editspotform" action="<?php echo $tplHelper->makeEditSpotAction(); ?>" method="post"  enctype="multipart/form-data">
		<input type="hidden" name="editspotform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editspotform'); ?>">
		<input type="hidden" name="editspotform[messageid]" value="<?php echo htmlspecialchars($editspotform['messageid']); ?>">
		<input type="hidden" name="messageid" value="<?php echo htmlspecialchars($editspotform['messageid']); ?>">
		<input type="hidden" name="editspotform[submitpost]" value="Post">
		<input type="hidden" name="editspotform[randomstr]" value="<?php echo $tplHelper->getCleanRandomString(12); ?>">

		<input type="hidden" name="editspotform[category]" value="<?php echo $editspotform['category']; ?>">
		<input type="hidden" name="editspotform[subcata]" value="<?php echo $editspotform['subcata']; ?>">
		<input type="hidden" name="editspotform[subcatb]" value="<?php echo $editspotform['subcatb']; ?>">
		<input type="hidden" name="editspotform[subcatc]" value="<?php echo $editspotform['subcatc']; ?>">
		<input type="hidden" name="editspotform[subcatd]" value="<?php echo $editspotform['subcatd']; ?>">
		<input type="hidden" name="editspotform[subcatz]" value="<?php echo $editspotform['subcatz']; ?>">
		<fieldset>

		<div>
			<div class="categorydropdown">
				<dt id='txtcategory'><?php echo _('Category'); ?></dt>
				<dd>
					<select id='spotcategoryselectbox' name='editspotform[category]' onchange="spotEditCategorySelectChanged('<?php echo $editspotform['category'] ?>', '<?php echo $editspotform['subcata'] ?>', '<?php echo $editspotform['subcatb'] ?>', '<?php echo $editspotform['subcatc'] ?>', '<?php echo $editspotform['subcatd'] ?>', '<?php echo $editspotform['subcatz'] ?>')">
			<?php foreach (SpotCategories::$_head_categories as $catnr => $catvalue) {
                $selected = ($catnr == $editspotform['category']) ? ' selected="selected"' : ''; ?>
							<option<?php echo $selected; ?> value="<?php echo $catnr; ?>"><?php echo $catvalue; ?></option>
			<?php
            } ?>
					</select>
				</dd>
			</div>

			<div class="categorydropdown">
				<dt id='txtsubcatz'><?php echo _('Type'); ?></dt>
				<dd>
					<select id='subcatzselectbox' name='editspotform[subcatz]' onchange="spotEditCategorySelectChanged('<?php echo $editspotform['category'] ?>', '<?php echo $editspotform['subcata'] ?>', '<?php echo $editspotform['subcatb'] ?>', '<?php echo $editspotform['subcatc'] ?>', '<?php echo $editspotform['subcatd'] ?>', '<?php echo $editspotform['subcatz'] ?>')">
					</select>
				</dd>
			</div>

			<div class="categorydropdown">
				<dt id='txtsubcata'><?php echo _('Format'); ?></dt>
				<dd>
					<select id='subcataselectbox' name='editspotform[subcata]'>
					</select>
				</dd>
			</div>
		</div>
		<div class='clear'></div>
	
		<div>
			<dt><label for="editspotform[title]"><?php echo _('Titel'); ?></label></dt>
			<dd><input type="text" name="editspotform[title]" size="60" maxlength="60" value="<?php echo $editspotform['title']; ?>"></dd>

			<dt><label for="editspotform[body]"><?php echo _('Description'); ?></label></dt>
			<dd><textarea name="editspotform[body]" id="editspotform[body]" cols="70" rows="8"><?php echo $editspotform['description']; ?></textarea><br />

	<?php
        $smileyList = $tplHelper->getSmileyList();
    foreach ($smileyList as $name => $image) {
        echo "<a onclick=\"addText(' [img=".$name."]', 'editspotform[body]'); return false;\"><img src=\"".$image.'" alt="'.$name.'" name="'.$name.'"></a> ';
    } ?>
			</dd>

			<dt><label for="editspotform[tag]"><?php echo _('Tag'); ?></label></dt>
			<dd><input type="text" name="editspotform[tag]" size="94" maxlength="99" value="<?php echo $editspotform['tag']; ?>"></dd>

			<dt><label for="editspotform[website]"><?php echo _('Website'); ?></label></dt>
			<dd><input type="text" name="editspotform[website]" size="94" maxlength="255" value="<?php echo $editspotform['website']; ?>"></dd>
		</div>

		<div class='clear'></div>

		<div>
			<div class="subcategorylistbox">
				<dt id='txtsubcatb'><?php echo _('Source'); ?></dt>
				<dd>
					<select id='subcatbselectbox' name='editspotform[subcatb][]' multiple='multiple' size='8'>
					</select>
				</dd>
			</div>

			<div class="subcategorylistbox">
				<dt id='txtsubcatc'><?php echo _('Language'); ?></dt>
				<dd>
					<select id='subcatcselectbox' name='editspotform[subcatc][]' multiple='multiple' size='8'>
					</select>
				</dd>
			</div>

			<div class="subcategorylistbox">
				<dt id='txtsubcatd'><?php echo _('Genre'); ?></dt>
				<dd>
					<select id='subcatdselectbox' name='editspotform[subcatd][]' multiple='multiple' size='8'>
					</select>
				</dd>
			</div>
		</div>
		</fieldset>		
		<div class='red'><br>
			<?php
            echo _('Note: ').'<br>';
    echo _('The edited spot will not be posted to usenet. The changes will only be visible for users of this Spotweb site.').'<br>';
    $show_delete_button = $tplHelper->allowed(SpotSecurity::spotsec_delete_spot, '');
    if ($show_delete_button) {
        echo _('Deleting a spot will remove the spot from this Spotweb site only. It will not be removed from other websites and/or other Spotnet clients.');
    } // if
             ?>
		</div>
		<div class='clear'><br></div>
		<div class="editspotButtons">
			<dd>
				<input class="greyButton" type="submit" name="editspotform[submitedit]" value="<?php echo _('Change'); ?>">
				<?php if ($show_delete_button) { ?>
					<input class="greyButton" type="submit" name="editspotform[submitdelete]" value="<?php echo _('Delete'); ?>">
				<?php	} // if?>
			</dd>
		</div>

	</form>
</div>

<script type='text/javascript'>
	spotEditCategorySelectChanged(<?php echo $catParams; ?>);
</script>

<?php
}
