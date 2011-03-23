				<div class="filter">
					<h4 class="search">Zoeken</h4>

					<form id="filterform" action="">
<?php
	$search = array_merge(array('type' => 'Titel', 'text' => '', 'tree' => '', 'unfiltered' => ''), $search);
?>
						<input type="hidden" id="search-tree" name="search[tree]" value="<?php echo $search['tree']; ?>">
<?php
	if ($settings['retrieve_full']) {
		$filterColCount = 4;
	} else {
		$filterColCount = 3;
	} # if
?>
                        <table class="filters">
							<tbody>
								<tr<?php if ($filterColCount == 3) {echo " class='short'";} ?>> 
									<td> <input type="radio" name="search[type]" value="Titel" <?php echo $search['type'] == "Titel" ? 'checked="checked"' : "" ?> /><label>Titel</label> </td>
									<td> <input type="radio" name="search[type]" value="Poster" <?php echo $search['type'] == "Poster" ? 'checked="checked"' : "" ?> /><label>Poster</label> </td>
									<td> <input type="radio" name="search[type]" value="Tag" <?php echo $search['type'] == "Tag" ? 'checked="checked"' : "" ?> /><label>Tag</label> </td>
<?php if ($settings['retrieve_full']) { ?>
									<td> <input type="radio" name="search[type]" value="UserID" <?php echo $search['type'] == "UserID" ? 'checked="checked"' : "" ?> /><label>UserID</label> </td>
<?php } ?>									
								</tr>
								
								<tr>
									<td colspan="<?php echo $filterColCount;?>"><input class='searchbox' type="text" name="search[text]" value="<?php echo htmlspecialchars($search['text']); ?>"></td>
								</tr>

								<tr class="unfiltered"> 
									<td colspan='<?php echo $filterColCount;?>'> <input type="checkbox" name="search[unfiltered]" value="true"  <?php echo $search['unfiltered'] == "true" ? 'checked="checked"' : "" ?> /><label>Vergeet filters voor zoekopdracht</label> </td>
								</tr>
							</tbody>
						</table>

                        <div id="tree"> 
                            <ul>
                            </ul>
                        </div>
						
						<input type='submit' class="filtersubmit" value='Zoek en filter'>
					</form>

<h4>Filters</h4>
                    
                    <ul class="filterlist">
                    	<li><a href="<?php echo $tplHelper->getPageUrl('watchlist'); ?>"><img src="images/icons/fav.png" alt='Watchlist'> Watchlist </a></li>
<?php
    foreach($filters as $filter) {
?>
                        <li<?php if($filter[2]) { echo " class='". $tplHelper->filter2cat($filter[2]) ."'"; } ?>> <a class="filter <?php echo $filter[3]; ?>" href="<?php echo $tplHelper->getPageUrl('index'); ?>&amp;search[tree]=<?php echo $filter[2];?>"><img src='<?php echo $filter[1]; ?>' alt='<?php echo $filter[0]; ?>'><?php echo $filter[0]; ?></a>
<?php
        if (!empty($filter[4])) {
            echo "\t\t\t\t\t\t\t<ul class='filterlist subfilterlist'>\r\n";
            foreach($filter[4] as $subFilter) {
				$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $subFilter[2];
?>
            			<li> <a class="filter <?php echo $subFilter[3];?>" href="<?php echo $strFilter;?>"><img src='<?php echo $subFilter[1]; ?>' alt='<?php echo $subFilter[0]; ?>'><?php echo $subFilter[0]; ?></a>
<?php
				if (!empty($subFilter[4])) {
					echo "\t\t\t\t\t\t\t<ul class='filterlist subfilterlist'>\r\n";
					foreach($subFilter[4] as $sub2Filter) {
						$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $sub2Filter[2];
		?>
							<li> <a class="filter <?php echo $sub2Filter[3];?>" href="<?php echo $strFilter;?>"><img src='<?php echo $sub2Filter[1]; ?>' alt='<?php echo $subFilter[0]; ?>'><?php echo $sub2Filter[0]; ?></a>
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
					
					<h4>Maintenance</h4>
					<ul class="filterlist maintenancebox">
						<li class="info"> Laatste update: <?php echo $tplHelper->formatDate($lastupdate, 'lastupdate'); ?> </li>
<?php
	if ($settings['show_updatebutton']) {
?>
						<li> <a href="retrieve.php?output=xml" id="updatespotsbtn" class="updatespotsbtn">Update Spots</a></li>
<?php
	}
?>
<?php
	if ($settings['keep_downloadlist']) {
?>
						<li> <a href="<?php echo $tplHelper->getPageUrl('erasedls'); ?>" id="removedllistbtn" class="erasedlsbtn">Remove history of downloads</a></li>
<?php
	}
?>
						<li> <a href="<?php echo $tplHelper->getPageUrl('markallasread'); ?>" id="markallasreadbtn" class="markallasreadbtn">Mark all as read</a></li>
					</ul>

				</div>
