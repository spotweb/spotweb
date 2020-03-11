<?php
require __DIR__.'/includes/form-messages.inc.php';

/*
 * If this page is rendered without an result variable
 * available, just create one ourselves.
 */
if (!isset($result)) {
    $result = new Dto_FormResult('notsubmitted');
} // if

if ((isset($lastformaction) && ($lastformaction == 'exportfilters'))) {
    $this->sendContentTypeHeader('xml');
    header('Content-Disposition: attachment; filename="spotwebfilters.xml"');

    echo $result->getData('filters');

    return;
} // if

if ((isset($lastformaction) && ($lastformaction == 'importfilters'))) {
    if ($result->isSuccess()) {
        $tplHelper->redirect($http_referer);
    } // if
} // if

/*
 * Render the JSON or the form
 */
if (showResults($result)) {
    return;
} // if

/*
 * Retrieve a list of icons available
 */
$filterIcons = $tplHelper->getFilterIcons();

/*
 * If the user did not submit the form yet, make
 * sure we add some data to the template
 */
if (!$result->isSubmitted()) {
    // Determine whether this an edit of an existing filter or adding a new one
    $isNew = (isset($data['isnew']));

    // Retrieve the requested filter
    if ((!$isNew) && (isset($data['filterid']))) {
        $filter = $tplHelper->getUserFilter($data['filterid']);
    } else {
        $filter = ['id' => 9999, 'title' => '', 'icon' => ''];
    } // if?>

	<!-- Naam van filter wijzigen of nieuwe filter toevoegen -->
	<fieldset>
		<form class="editfilterform" name="editfilterform" action="<?php echo $tplHelper->makeEditFilterAction(); ?>" method="post">
			<input type="hidden" name="editfilterform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>">
<?php if (!$isNew) { ?>			
			<input type="hidden" name="filterid" value="<?php echo (int) $filter['id']; ?>">
<?php } else {  ?>
			<input type="hidden" name="filterid" value="9999">
			<input type="hidden" name="editfilterform[tree]" value="<?php echo isset($search['tree']) ? htmlspecialchars($search['tree'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
			<input type="hidden" name="editfilterform[valuelist]" value="<?php echo htmlspecialchars(implode('&', array_map('urlencode', (isset($search['value']) ? $search['value'] : []))), ENT_QUOTES, 'UTF-8'); ?>" />
			<input type="hidden" name="editfilterform[sorton]" value="<?php echo htmlspecialchars($sortby, ENT_QUOTES, 'UTF-8'); ?>" />
			<input type="hidden" name="editfilterform[sortorder]" value="<?php echo htmlspecialchars($sortdir, ENT_QUOTES, 'UTF-8'); ?>" />
<?php } ?>
			
			<dt><label for="editfilterform[title]"><?php echo _('Name'); ?></label></dt>
			<dd>
				<input type="text" name="editfilterform[title]" value="<?php echo htmlspecialchars($filter['title']); ?>" />
			</dd>

			<dt><label for="editfilterform[icon]"><?php echo _('Icon'); ?></label></dt>
			<dd>
				<select name="editfilterform[icon]">
<?php
                foreach ($filterIcons as $icon => $desc) {
                    echo '<option '.($filter['icon'] == $icon ? 'selected="selected"' : '')."value='".$icon."'>".$desc.'</option>';
                } // foreach?>
				</select>
			</dd>

			<dt><label for="editfilterform[enablenotify]"><?php echo _('Notify me when this filter has new spots?'); ?></label></dt>
			<dd>
				<input type="checkbox" name="editfilterform[enablenotify]" value="1" <?php if ((isset($filter['enablenotify'])) && ($filter['enablenotify'])) {
                    echo ' checked="checked" ';
                } ?> />
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
} // if not only  xml
