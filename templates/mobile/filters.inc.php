<?php 	$setpath = $tplHelper->makeBaseUrl("path"); ?>
<div data-role="page" id="search"> 
	<div data-role="header" data-backbtn="false">
	<h1>Zoek</h1>

	<div data-role="navbar">
		<ul>
			<li><a href="#spots" data-icon="grid" >Spots</a></li>
			<li><a href="#search" class="ui-btn-active" data-icon="search">Zoek</a></li>
			<li><a href="#filters" data-icon="star">Filters</a></li>
		</ul>
	</div><!-- /navbar -->

</div>
<div data-role="content">
	<div data-role="fieldcontain" >
		<form id="filterform" action="<?php echo $setpath;?>index.php?page=search#spots" method="get" data-ajax="false">
			<fieldset data-role="controlgroup" data-type="horizontal" data-role="fieldcontain">
	         		<input type="radio" name="search[type]" value="Titel" id="radio-choice-1" checked="checked" />
	         		<label for="radio-choice-1">Titel</label>
	
		         	<input type="radio"  name="search[type]" value="Poster" id="radio-choice-2" />
	    	     	<label for="radio-choice-2">Poster</label>
	
	        	 	<input type="radio" name="search[type]" value="Tag" id="radio-choice-3"  />
	         		<label for="radio-choice-3">Tag</label>
			</fieldset>
		    <input type="search" type="text" name="search[text]" value="" />
	</form>
	</div>
 </div>
 </div>
<div data-role="page" id="filters"> 
	<div data-role="header" data-backbtn="false">
	<h1>Spotweb</h1>

	<div data-role="navbar">
		<ul>
			<li><a href="#spots" data-icon="grid" >Spots</a></li>
			<li><a href="#search" data-icon="search">Zoek</a></li>
			<li><a href="#filters" data-icon="star" class="ui-btn-active" >Filters</a></li>

		</ul>
	</div><!-- /navbar -->

</div>
<div data-role="content">

<ul data-role="listview" data-theme="c" data-dividertheme="b">
<?php
	function processFilters($tplHelper, $count_newspots, $filterList) {
		$selfUrl = $tplHelper->makeSelfUrl("path");

		foreach($filterList as $filter) {
			$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $filter['tree'];
			if (!empty($filter['valuelist'])) {
				foreach($filter['valuelist'] as $value) {
					$strFilter .= '&amp;search[value][]=' . $value;
				} # foreach
			} # if
			if (!empty($filter['sorton'])) {
				$strFilter .= '&amp;sortby=' . $filter['sorton'] . '&amp;sortdir=' . $filter['sortorder'];
			} # if

			# escape the filter vlaues
			$filter['title'] = htmlentities($filter['title'], ENT_NOQUOTES, 'UTF-8');
			$filter['icon'] = htmlentities($filter['icon'], ENT_NOQUOTES, 'UTF-8');
			
			# Output de HTML
			echo '<li>';
			echo '	<img src="images/icons/' . $filter['icon'] . '" class="ui-li-icon" />';
			echo '	<h3><a href="' . $strFilter . '#spots" rel="external">' . $filter['title'] . '</a></h3>';
			echo '</li>';
			
			# Als er children zijn, output die ool
			if (!empty($filter['children'])) {
				echo '<ul class="filterlist subfilterlist">';
				processFilters($tplHelper, $count_newspots, $filter['children']);
				echo '</ul>';
			} # if
			
			echo '</li>' . PHP_EOL;
		} # foreach
	} # processFilters
	
	processFilters($tplHelper, false, $filters);
?>
</ul>
</div>
</div>

