			<div class='filter'>
				<ul id='sortablefilterlist' class='filterlist'>
<?php			
	function processFilters($tplHelper, $filterList) {
		foreach($filterList as $filter) {
			# escape the filter vlaues
			$filter['title'] = htmlentities($filter['title'], ENT_NOQUOTES, 'UTF-8');
			$filter['icon'] = htmlentities($filter['icon'], ENT_NOQUOTES, 'UTF-8');
			
			# Output de HTML
			echo '<li class="sortable-element-class ' . $tplHelper->filter2cat($filter['tree']) . '" id="orderfilterslist_' . $filter['id'];
			echo '"><div><a href="" onclick="return openDialog(\'editfilterdialogdiv\', \'' . _('Edit filter') . '\', \'?page=render&tplname=editfilter&data[filterid]=' . $filter['id'] . '\', \'editfilterform\', null, \'autoclose\', function() { refreshTab(\'edituserpreferencetabs\')}, null);">';
			echo '<span class="spoticon spoticon-' . str_replace('.png', '', $filter['icon']) . '">&nbsp;</span>' . $filter['title'] . '</a>';
			echo '</div>';
			
			# Als er children zijn, output die ook
			if (!empty($filter['children'])) {
				echo '<ul>';
				processFilters($tplHelper, $filter['children']);
				echo '</ul>';
			} # if
			
			echo '</li>' . PHP_EOL;
		} # foreach
	} # processFilters
	
	processFilters($tplHelper, $tplHelper->getUserFilterList());
?>
			</ul>

			<!-- We need our own editdialogdiv because this form can be embedded into another dialog as a whole -->
			<div id='editfilterdialogdiv'></div>
			<br>
			
			<form class="editfilterform" name="editfilterform" action="<?php echo $tplHelper->makeEditFilterAction(); ?>" method="post" enctype="multipart/form-data">
				<input type="hidden" name="editfilterform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>">
				<input type="hidden" name="filterid" value="9999">
				<fieldset class="manageFilters">
					<input class="greyButton" onclick="ajaxSubmitFormWithCb('?page=editfilter', this, function(xml) { refreshTab('edituserpreferencetabs')}); return false;" type="submit" name="editfilterform[submitdiscardfilters]" value="<?php echo _('Restore filter to default'); ?>">
	<?php if ($tplHelper->allowed(SpotSecurity::spotsec_set_filters_as_default, ''))  { ?>
					<input class="greyButton" onclick="ajaxSubmitFormWithCb('?page=editfilter', this, function(xml) { refreshTab('edituserpreferencetabs')}); return false;" type="submit" name="editfilterform[submitsetfiltersasdefault]" value="<?php echo _('Use these filters as standard'); ?>">
	<?php } ?>
					<input class="greyButton" type="submit" name="editfilterform[submitexportfilters]" value="<?php echo _('Download your filters'); ?>">
				</fieldset>
				<fieldset class="uploadFilters">
					<dt><label><?php echo _('Upload filters'); ?></label></dt>
					<dd><input type="hidden" name="MAX_FILE_SIZE" value="3072000" />
					<input name="filterimport" type="file" />
					<input class="greyButton" type="submit" name="editfilterform[submitimportfilters]" value="<?php echo _('Upload filters'); ?>"></dd>
				</fieldset>
			</form>
			
		</div>

<script type='text/javascript'>	
var editfilterformcsrfcookie = '<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>';
</script>
