				<div id="filter" class="filter">					
                    <h4><span class="viewState"><a onclick="toggleSidebarItem(this)"></a></span>Quick Links </h4>
					<ul class="filterlist quicklinks">
<?php
    foreach($quicklinks as $quicklink) {
?>
							<li> <a class="filter <?php echo " " . $quicklink[3]; if ($tplHelper->makeSelfUrl() == $tplHelper->makeBaseUrl() . $quicklink[2]) { echo " selected"; } ?>" href="<?php echo $quicklink[2]; ?>">
							<img src='<?php echo $quicklink[1]; ?>' alt='<?php echo $quicklink[0]; ?>'><?php echo $quicklink[0]; if (stripos($quicklink[2], 'New:0') && $tplHelper->getNewCountForFilter($quicklink[2])) { echo "<span class='newspots'>".$tplHelper->getNewCountForFilter($quicklink[2])."</span>"; } ?></a>
<?php
    }
?>
					</ul>
					
                    <h4><span class="viewState"><a onclick="toggleSidebarItem(this)"></a></span>Filters </h4>
                    <ul class="filterlist filters">

<?php
    foreach($filters as $filter) {
		$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $filter[2];
		$newCount = $tplHelper->getNewCountForFilter($strFilter);
?>
						<li<?php if($filter[2]) { echo " class='". $tplHelper->filter2cat($filter[2]) ."'"; } ?>>
						<a class="filter<?php echo " " . $filter[3]; if ($tplHelper->makeSelfUrl() == $strFilter) { echo " selected"; } ?>" href="<?php echo $strFilter;?>">
						<img src='<?php echo $filter[1]; ?>' alt='<?php echo $filter[0]; ?>'><?php echo $filter[0]; if ($newCount) { echo "<span onclick=\"gotoNew('".$strFilter."')\" class='newspots' title='Laat nieuwe spots in filter &quot;".$filter[0]."&quot; zien'>$newCount</span>"; } ?><span class='toggle' title='Filter uitklappen' onclick='toggleFilter(this)'>&nbsp;</span></a>
<?php
		if (!empty($filter[4])) {
			echo "\t\t\t\t\t\t\t<ul class='filterlist subfilterlist'>\r\n";
			foreach($filter[4] as $subFilter) {
				$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $subFilter[2];
				$newSubCount = $tplHelper->getNewCountForFilter($strFilter);
?>
							<li> <a class="filter<?php echo " " . $subFilter[3]; if ($tplHelper->makeSelfUrl() == $strFilter) { echo " selected"; } ?>" href="<?php echo $strFilter;?>">
							<img src='<?php echo $subFilter[1]; ?>' alt='<?php echo $subFilter[0]; ?>'><?php echo $subFilter[0]; if ($newSubCount) { echo "<span onclick=\"gotoNew('".$strFilter."')\" class='newspots' title='Laat nieuwe spots in filter &quot;".$subFilter[0]."&quot; zien'>$newSubCount</span>"; } ?></a>
<?php
				if (!empty($subFilter[4])) {
					echo "\t\t\t\t\t\t\t<ul class='filterlist subfilterlist'>\r\n";
					foreach($subFilter[4] as $sub2Filter) {
						$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $sub2Filter[2];
						$newSub2Count = $tplHelper->getNewCountForFilter($strFilter);
		?>
							<li> <a class="filter<?php echo " " . $sub2Filter[3]; if ($tplHelper->makeSelfUrl() == $strFilter) { echo " selected"; } ?>" href="<?php echo $strFilter;?>">
							<img src='<?php echo $sub2Filter[1]; ?>' alt='<?php echo $subFilter[0]; ?>'><?php echo $sub2Filter[0]; if ($newSub2Count) { echo "<span onclick=\"gotoNew('".$strFilter."')\" class='newspots' title='Laat nieuwe spots in filter &quot;".$sub2Filter[0]."&quot; zien'>$newSub2Count</span>"; } ?></a>
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

					<h4><span class="viewState"><a onclick="toggleSidebarItem(this)"></a></span>Onderhoud </h4>

					<ul class="filterlist maintenancebox">
						<li class="info"> Laatste update: <?php echo $tplHelper->formatDate($lastupdate, 'lastupdate'); ?> </li>
<?php if ($settings->get('show_updatebutton')) { ?>
						<li><a href="retrieve.php?output=xml" id="updatespotsbtn" class="maintenancebtn">Update Spots</a></li>
<?php } ?>
<?php if ($settings->get('keep_downloadlist')) { ?>
						<li><a href="<?php echo $tplHelper->getPageUrl('erasedls'); ?>" id="removedllistbtn" class="maintenancebtn">Verwijder downloadgeschiedenis</a></li>
<?php } ?>
						<li><a href="<?php echo $tplHelper->getPageUrl('markallasread'); ?>" id="markallasreadbtn" class="maintenancebtn">Markeer alles als gelezen</a></li>
					</ul>
				</div>
