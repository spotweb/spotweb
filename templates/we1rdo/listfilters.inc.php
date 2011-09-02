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
			echo '"><div><a href="" onclick="return openDialog(\'editdialogdiv\', \'Bewerk een filter\', \'?page=render&tplname=editfilter&data[filterid]=' . $filter['id'] . '\', \'editfilterform\', true, function() { refreshTab(\'edituserpreferencetabs\')});">';
			echo '<img src="images/icons/' . $filter['icon'] . '" alt="' . $filter['title'] . '">' . $filter['title'] . '</a>';
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

			<br>
			
			<form class="editfilterform" name="editfilterform" action="<?php echo $tplHelper->makeEditFilterAction(); ?>" method="post" enctype="multipart/form-data">
				<input type="hidden" name="editfilterform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>">
				<input type="hidden" name="filterid" value="9999">
				<input class="greyButton" onclick="ajaxSubmitFormWithCb('?page=editfilter', this, function() { refreshTab('edituserpreferencetabs')}); return false;" type="submit" name="editfilterform[submitdiscardfilters]" value="Herstel filters naar standaard">
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_set_filters_as_default, ''))  { ?>
				<input class="greyButton" onclick="ajaxSubmitFormWithCb('?page=editfilter', this, function() { refreshTab('edituserpreferencetabs')}); return false;" type="submit" name="editfilterform[submitsetfiltersasdefault]" value="Deze filters gebruiken als standaard">
<?php } ?>
				<input class="greyButton" type="submit" name="editfilterform[submitexportfilters]" value="Download je filters">

				<input type="hidden" name="MAX_FILE_SIZE" value="30720" />
				<input name="filterimport" type="file" />
				<input class="greyButton" type="submit" name="editfilterform[submitimportfilters]" value="Upload je filters">
			</form>
			
		</div>

<script type='text/javascript'>	
var editfilterformcsrfcookie = '<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>';
</script>
