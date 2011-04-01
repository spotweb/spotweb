				<div id="filter" class="filter">
                    <h4><span class="scroll"><input type="checkbox" name="filterscroll" id="filterscroll" value="Scroll" title="Wissel tussen vaste en meescrollende sidebar"><label>&nbsp;</label></span><span class="viewState"><a id="filterform_link" onclick="toggleFilterBlock('#filterform_link', '.hide', 'viewSearch')"> </a></span> Zoeken </h4>

					<form id="filterform" action="">
<?php
	$search = array_merge(array('type' => 'Titel', 'text' => '', 'tree' => '', 'unfiltered' => ''), $search);
	if (empty($search['type'])) {
		$search['type'] = 'Titel';
	} # if
?>
						<div><input type="hidden" id="search-tree" name="search[tree]" value="<?php echo $search['tree']; ?>"></div>
<?php
	$filterColCount = 3;
	if ($settings['retrieve_full']) {
		$filterColCount++;
	} # if
?>
                        <table class="filters" summary="Filters">
							<tbody>
								<tr<?php if ($filterColCount == 3) {echo " class='short'";} ?>> 
									<td> <input type="radio" name="search[type]" value="Titel" <?php echo $search['type'] == "Titel" ? 'checked="checked"' : "" ?> ><label>Titel</label> </td>
									<td> <input type="radio" name="search[type]" value="Poster" <?php echo $search['type'] == "Poster" ? 'checked="checked"' : "" ?> ><label>Poster</label> </td>
									<td> <input type="radio" name="search[type]" value="Tag" <?php echo $search['type'] == "Tag" ? 'checked="checked"' : "" ?> ><label>Tag</label> </td>
<?php if ($settings['retrieve_full']) { ?>
									<td> <input type="radio" name="search[type]" value="UserID" <?php echo $search['type'] == "UserID" ? 'checked="checked"' : "" ?> ><label>UserID</label> </td>
<?php } ?>									
								</tr>
								
								<tr>
									<td colspan='<?php echo $filterColCount;?>'><input class='searchbox' type="text" name="search[text]" value="<?php echo htmlspecialchars($search['text']); ?>"><span class="filtersubmit"><input type='submit' class="filtersubmit" value='>>' title='Zoeken'></span></td>
								</tr>

								<tr class="unfiltered hide" style="display:none;">
									<td colspan='<?php echo $filterColCount;?>'> <input type="checkbox" name="search[unfiltered]" value="true"  <?php echo $search['unfiltered'] == "true" ? 'checked="checked"' : "" ?>><label>Vergeet filters voor zoekopdracht</label> </td>
								</tr>
							</tbody>
						</table>

						<div id="tree" class="hide"></div>
					</form>
					
                    <h4><span class="viewState"><a id="quicklinks_link" onclick="toggleFilterBlock('#quicklinks_link', 'ul.quicklinks', 'viewQuickLinks')"> </a></span> Quick Links </h4>
					<ul class="filterlist quicklinks">
<?php
    foreach($quicklinks as $quicklink) {
?>
							<li> <a class="filter <?php echo " " . $quicklink[3]; if ($tplHelper->makeSelfUrl() == $tplHelper->makeBaseUrl() . $quicklink[2]) { echo " selected"; } ?>" href="<?php echo $quicklink[2]; ?>">
							<img src='<?php echo $quicklink[1]; ?>' alt='<?php echo $quicklink[0]; ?>'><?php echo $quicklink[0]; if (stripos($quicklink[2], 'New:0')) { echo "<span class='newspots'>".$tplHelper->getNewCountForFilter($quicklink[2])."</span>"; } ?></a>
<?php
    }
?>
					</ul>
					
                    <h4><span class="viewState"><a id="filters_link" onclick="toggleFilterBlock('#filters_link', 'ul.filters', 'viewFilters')"> </a></span> Filters </h4>
                    <ul class="filterlist filters">

<?php
    foreach($filters as $filter) {
		$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $filter[2];
		$newCount = $tplHelper->getNewCountForFilter($strFilter);
?>
						<li<?php if($filter[2]) { echo " class='". $tplHelper->filter2cat($filter[2]) ."'"; } ?>>
						<a class="filter<?php echo " " . $filter[3]; if ($tplHelper->makeSelfUrl() == $strFilter) { echo " selected"; } ?>" href="<?php echo $strFilter;?>">
						<img src='<?php echo $filter[1]; ?>' alt='<?php echo $filter[0]; ?>'><?php echo $filter[0]; if ($newCount) { echo "<span class='newspots'>$newCount</span>"; } ?></a>
<?php
		if (!empty($filter[4])) {
			echo "\t\t\t\t\t\t\t<ul class='filterlist subfilterlist'>\r\n";
			foreach($filter[4] as $subFilter) {
				$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $subFilter[2];
				$newSubCount = $tplHelper->getNewCountForFilter($strFilter);
?>
							<li> <a class="filter<?php echo " " . $subFilter[3]; if ($tplHelper->makeSelfUrl() == $strFilter) { echo " selected"; } ?>" href="<?php echo $strFilter;?>">
							<img src='<?php echo $subFilter[1]; ?>' alt='<?php echo $subFilter[0]; ?>'><?php echo $subFilter[0]; if ($newSubCount) { echo "<span class='newspots'>$newSubCount</span>"; } ?></a>
<?php
				if (!empty($subFilter[4])) {
					echo "\t\t\t\t\t\t\t<ul class='filterlist subfilterlist'>\r\n";
					foreach($subFilter[4] as $sub2Filter) {
						$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $sub2Filter[2];
						$newSub2Count = $tplHelper->getNewCountForFilter($strFilter);
		?>
							<li> <a class="filter<?php echo " " . $sub2Filter[3]; if ($tplHelper->makeSelfUrl() == $strFilter) { echo " selected"; } ?>" href="<?php echo $strFilter;?>">
							<img src='<?php echo $sub2Filter[1]; ?>' alt='<?php echo $subFilter[0]; ?>'><?php echo $sub2Filter[0]; if ($newSub2Count) { echo "<span class='newspots'>$newSub2Count</span>"; } ?></a>
		<?php
					} # foreach 
					echo "\t\t\t\t\t\t\t</ul>\r\n";
				} # is_array
			
			} # foreach 
            echo "\t\t\t\t\t\t\t</ul>\r\n";
        } # is_array
    } # foreach
?>
                    </ul>

					<h4><span class="viewState"><a id="maintenance_link" onclick="toggleFilterBlock('#maintenance_link', 'ul.maintenancebox', 'viewMaintenance')"> </a></span> Onderhoud </h4>

					<ul class="filterlist maintenancebox">
						<li class="info"> Laatste update: <?php echo $tplHelper->formatDate($lastupdate, 'lastupdate'); ?> </li>
<?php
	if ($settings['show_updatebutton']) {
?>
						<li> <a href="retrieve.php?output=xml" id="updatespotsbtn" class="maintenancebtn">Update Spots</a></li>
<?php
	}
?>
<?php
	if ($settings['keep_downloadlist']) {
?>
						<li> <a href="<?php echo $tplHelper->getPageUrl('erasedls'); ?>" id="removedllistbtn" class="maintenancebtn">Verwijder downloadgeschiedenis</a></li>
<?php
	}
?>
						<li> <a href="<?php echo $tplHelper->getPageUrl('markallasread'); ?>" id="markallasreadbtn" class="maintenancebtn">Markeer alles als gelezen</a></li>
					</ul>
				</div>
