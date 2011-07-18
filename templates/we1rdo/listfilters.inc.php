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
			echo '<img src="images/icons/' . $filter['icon'] . '" alt="' . $filter['title'] . '">' . $filter['title'] . '</a></div>';
			
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
	
<script type='text/javascript'>	
var editfilterformcsrfcookie = '<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>';
</script>
			</div>
