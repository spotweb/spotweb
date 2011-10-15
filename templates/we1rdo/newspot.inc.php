<?php 
if (!empty($postresult)) {
	include 'includes/form-xmlresult.inc.php';
	
	echo formResult2Xml($postresult, $formmessages, $tplHelper);
} 

if (empty($postresult)) {
	if (isset($formmessages)) {
		include "includes/form-messages.inc.php"; 
	} # if
require_once "includes/header.inc.php";
?>

<form class="newspotform" name="newspotform" id="newspotform" action="<?php echo $tplHelper->makePostSpotAction(); ?>" method="post"  enctype="multipart/form-data">
	<input type="hidden" name="newspotform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('newspotform'); ?>">
	<input type="hidden" id="subcatlist" name="newspotform[subcatlist]" value="">
	<input type="hidden" name="newspotform[newmessageid]" value="">
	<input type="hidden" name="newspotform[submit]" value="Post">
	<fieldset>
		<dl>
			<dt><label for="newspotform[category]">Categorie</label></dt>
			<dd>
				<select name="newspotform[category]" id="newspotcategoryselectlist" onchange="newspotChangeCategory()">
<?php foreach(SpotCategories::$_head_categories as $catnr => $catvalue) { ?>
					<option value="<?php echo $catnr; ?>" <?php if ($postspotform['category'] == $catnr) { echo 'selected="selected"'; } ?>><?php echo $catvalue; ?></option>
<?php } ?>
				</select>
			</dd>
			
			<dt><label for="newspotform[subcatz]">Type</label></dt>
			<dd>
				<select name="newspotform[subcatz]" id="newspotcategorytypeselectlist" onchange="newspotChangeCategory()">
<?php foreach(SpotCategories::$_categories[0]['z'] as $catnr => $catvalue) { ?>
					<option value="<?php echo $catnr; ?>" <?php if ($postspotform['subcatz'] == $catnr) { echo 'selected="selected"'; } ?>><?php echo $catvalue; ?></option>
<?php } ?>
				</select>
			</dd>

			<dt>Genre</dt>
			<dd>
				<div id="newspotcatselecttree"></div>
			</dd>
			
			<dt><label for="newspotform[title]">Titel</label></dt>
			<dd><input type="text" name="newspotform[title]" maxlength="60" value="<?php echo htmlspecialchars($postspotform['title']); ?>"></dd>

			<dt><label for="newspotform[body]">Omschrijving</label></dt>
			<dd><textarea name="newspotform[body]" id="newspotform[body]" cols="80" rows="12"><?php echo empty($postspotform['body']) ? htmlspecialchars($currentSession['user']['prefs']['newspotdefault_body']) : htmlspecialchars($postspotform['body']); ?></textarea></dd>

			<dt><label for="newspotform[tag]">Tag</label></dt>
			<dd><input type="text" name="newspotform[tag]" maxlength="99" value="<?php echo empty($postspotform['tag']) ? htmlspecialchars($currentSession['user']['prefs']['newspotdefault_tag']) : htmlspecialchars($postspotform['tag']); ?>"></dd>

			<dt><label for="newspotform[website]">Website</label></dt>
			<dd><input type="text" name="newspotform[website]" maxlength="255" value="<?php echo htmlspecialchars($postspotform['website']); ?>"></dd>

			<dt><label for="newspotform[nzbfile]">NZB bestand</label></dt>
			<dd><input name="newspotform[nzbfile]" type="file" /></dd>

			<dt><label for="newspotform[imagefile]">Afbeelding</label></dt>
			<dd><input name="newspotform[imagefile]" type="file" /></dd>

			<dd><input class="greyButton" type="submit" name="dummySubmit" value="Toevoegen"></dd>
		</dl>
	</fieldset>
</form>

<?php
	}
