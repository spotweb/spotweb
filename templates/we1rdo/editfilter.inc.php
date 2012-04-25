<?php
if ((isset($lastformaction) && ($lastformaction == 'exportfilters'))) {
	$this->sendContentTypeHeader('xml');
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


# Retrieve a list of icons available
$filterIcons = $tplHelper->getFilterIcons();

if (empty($editresult)) {
	# is form voor het toevoegen van een groep ipv wijzigen van een
	$isNew = (isset($data['isnew']));
	
	# vraag de opgegeven filter op
	if ((!$isNew) && (isset($data['filterid']))) {
		$filter = $tplHelper->getUserFilter($data['filterid']);
	} else {
		$filter = array('id' => 9999, 'title' => '', 'icon' => '');
	} # if

	# bereid alvast een UL voor voor de errors e.d., worden er later
	# via AJAX ingegooid
	include "includes/form-messages.inc.php";
	
?>

	<!-- Naam van filter wijzigen of nieuwe filter toevoegen -->
	<fieldset>
		<form class="editfilterform" name="editfilterform" action="<?php echo $tplHelper->makeEditFilterAction(); ?>" method="post">
			<input type="hidden" name="editfilterform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>">
<?php if (!$isNew) { ?>			
			<input type="hidden" name="filterid" value="<?php echo (int) $filter['id']; ?>">
<?php } else {  ?>
			<input type="hidden" name="filterid" value="9999">
			<input type="hidden" name="editfilterform[tree]" value="<?php echo (isset($search['tree']) ? htmlspecialchars($search['tree'], ENT_QUOTES, "UTF-8") : ""); ?>"></input>
			<input type="hidden" name="editfilterform[valuelist]" value="<?php echo htmlspecialchars(implode('&', array_map('urlencode', (isset($search['value']) ?$search['value'] : array()) )), ENT_QUOTES, "UTF-8"); ?>"></input>
			<input type="hidden" name="editfilterform[sorton]" value="<?php echo htmlspecialchars($sortby, ENT_QUOTES, "UTF-8"); ?>"></input>
			<input type="hidden" name="editfilterform[sortorder]" value="<?php echo htmlspecialchars($sortdir, ENT_QUOTES, "UTF-8"); ?>"></input>
<?php } ?>
			
			<dt><label for="editfilterform[title]"><?php echo _('Name'); ?></label></dt>
			<dd>
				<input type="text" name="editfilterform[title]" value="<?php echo htmlspecialchars($filter['title']); ?>"></input>
			</dd>

			<dt><label for="editfilterform[icon]"><?php echo _('Icon'); ?></label></dt>
			<dd>
				<select name="editfilterform[icon]">
<?php
				foreach($filterIcons as $icon => $desc) {
					echo "<option " . ($filter['icon'] == $icon ? 'selected="selected"' : '') . "value='" . $icon . "'>" . $desc . "</option>";
				} # foreach
?>
				</select>
			</dd>

			<dt><label for="editfilterform[enablenotify]"><?php echo _('Notify me when this filter has new spots?'); ?></label></dt>
			<dd>
				<input type="checkbox" name="editfilterform[enablenotify]" <?php if ((isset($filter['enablenotify'])) && ($filter['enablenotify'])) { echo 'checked="checked" '; } ?>></input>
			</dd>
			
			<dd>
<?php if ($isNew) { ?>			
				<input class="smallGreyButton" type="submit" name="editfilterform[submitaddfilter]" value="<?php echo _('Add'); ?>">
<?php } else { ?>
				<input class="smallGreyButton" type="submit" name="editfilterform[submitchangefilter]" value="<?php echo _('Change'); ?>">
				<input class="smallGreyButton" type="submit" name="editfilterform[submitremovefilter]" value="<?php echo _('Delete'); ?>">
<?php } ?>
			</dd>
		</form>
	</fieldset>
<?php
	} # if not only  xml
