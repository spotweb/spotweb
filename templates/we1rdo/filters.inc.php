			<div id="toolbar">
                <div class="notifications">
                    <?php if ($settings->get('show_multinzb')) { ?>
                    <p class="multinzb"><a class="button" onclick="downloadMultiNZB()" title="MultiNZB"><span class="count"></span></a><a class="clear" onclick="uncheckMultiNZB()" title="Reset selectie">[x]</a></p>
                    <?php } ?>
                </div>
                
				<div class="logininfo"><p><a onclick="toggleSidebarPanel('.userPanel')" class="user" title='Open "Gebruikers Paneel"'>
<?php if ($currentSession['user']['userid'] == 1) { ?>	
                	Inloggen
<?php } else { ?>
					<?php echo $currentSession['user']['firstname']; ?>
<?php } ?>
				</a></p></div>

                <span class="scroll"><input type="checkbox" name="filterscroll" id="filterscroll" value="Scroll" title="Wissel tussen vaste en meescrollende sidebar"><label>&nbsp;</label></span>

                <form id="filterform" action="">
<?php
	$search = array_merge(array('type' => 'Titel', 'text' => '', 'tree' => '', 'unfiltered' => '', 'sortby' => '', 'sortdir' => ''), $search);
	if (empty($search['type'])) {
		$search['type'] = 'Titel';
	} # if
?>
                    <div><input type="hidden" id="search-tree" name="search[tree]" value="<?php echo $search['tree']; ?>"></div>
<?php
	$filterColCount = 3;
	if ($settings->get('retrieve_full')) {
		$filterColCount++;
	} # if
?>
                    <div class="search"><input class='searchbox' type="text" name="search[text]" value="<?php echo htmlspecialchars($search['text']); ?>"><input type='submit' class="filtersubmit" value='>>' title='Zoeken'></div>

                    <div class="sidebarPanel advancedSearch">
                    	<h4><a class="toggle" onclick="toggleSidebarPanel('.advancedSearch')" title='Sluit "Advanced Search"'>[x]</a>Zoeken op:</h4>
                        <ul class="search<?php if ($filterColCount == 3) {echo " small";} ?>">
                            <li> <input type="radio" name="search[type]" value="Titel" <?php echo $search['type'] == "Titel" ? 'checked="checked"' : "" ?> ><label>Titel</label></li>
                            <li> <input type="radio" name="search[type]" value="Poster" <?php echo $search['type'] == "Poster" ? 'checked="checked"' : "" ?> ><label>Poster</label></li>
                            <li> <input type="radio" name="search[type]" value="Tag" <?php echo $search['type'] == "Tag" ? 'checked="checked"' : "" ?> ><label>Tag</label></li>
<?php if ($settings->get('retrieve_full')) { ?>
                            <li> <input type="radio" name="search[type]" value="UserID" <?php echo $search['type'] == "UserID" ? 'checked="checked"' : "" ?> ><label>UserID</label></li>
<?php } ?>
                        </ul>

						<h4>Sorteren op:</h4>
                        <div><input type="hidden" name="sortdir" value="ASC"></div>
                        <ul class="search sorting">
                            <li> <input type="radio" name="sortby" value="" <?php echo $search['sortby'] == "" ? 'checked="checked"' : "" ?>><label>Relevantie</label> </li>
                        	<li> <input type="radio" name="sortby" value="title" <?php echo $search['sortby'] == "title" ? 'checked="checked"' : "" ?>><label>Titel</label> </li>
                            <li> <input type="radio" name="sortby" value="poster" <?php echo $search['sortby'] == "poster" ? 'checked="checked"' : "" ?>><label>Poster</label> </li>
                        	<li> <input type="radio" name="sortby" value="stamp" <?php echo $search['sortby'] == "stamp" ? 'checked="checked"' : "" ?>><label>Datum</label> </li>
                        </ul>

						<h4>Filtering</h4>
                        <div class="unfiltered"><input type="checkbox" name="search[unfiltered]" value="true" <?php echo $search['unfiltered'] == "true" ? 'checked="checked"' : "" ?>><label>Vergeet filters voor zoekopdracht</label></div>
    
                        <div id="tree"></div>
                    </div>
                </form>
                
                <div class="sidebarPanel userPanel">
                    <h4><a class="toggle" onclick="toggleSidebarPanel('.userPanel')" title='Sluit "Gebruikers paneel"'>[x]</a>Gebruikers paneel</h4>
                    <ul class="userInfo">
<?php if ($currentSession['user']['userid'] == 1) { ?>
						<li>U bent niet ingelogd</li>
<?php } else { ?>
						<li><?php echo "Gebruiker: <strong>" . $currentSession['user']['firstname'] . " " . $currentSession['user']['lastname'] . "</strong>"; ?></li>
						<li><?php echo "Laatst gezien: <strong>" . $tplHelper->formatDate($currentSession['user']['lastvisit'], 'lastvisit') . " geleden</strong>"; ?></li>
<?php } ?>
                    </ul>
                    
<?php if ($currentSession['user']['userid'] == 1) { ?>
                    <h4 class="dropDown"><span class="viewState"><a class="down" onclick="toggleCreateUser()"></a></span>Gebruiker toevoegen</h4>
                    <div class="createUser"></div>
<?php } ?>
                    
<?php if ($currentSession['user']['userid'] != 1) { ?>
                    <h4>Uitloggen</h4>
                    <a onclick="userLogout()" class="greyButton">Uitloggen</a>
<?php } else { ?>
                    <h4>Inloggen</h4>
                    <div class="login"></div>
<?php } ?>
				</div>
                
                <div class="sidebarPanel sabnzbdPanel">
                	<h4><a class="toggle" onclick="toggleSidebarPanel('.sabnzbdPanel')" title='Sluit "SabNZBd paneel"'>[x]</a>SabNZBd</h4>
                    <ul class="userInfo">
                    	<li>Work in progress</li>
                    </ul>
                </div>
            </div>

            <div id="filter" class="filter">					
                <h4><span class="viewState"><a onclick="toggleSidebarItem(this)"></a></span>Quick Links </h4>
                <ul class="filterlist quicklinks">
<?php foreach($quicklinks as $quicklink) { ?>
					<li> <a class="filter <?php echo " " . $quicklink[3]; if (parse_url($tplHelper->makeSelfUrl("full"), PHP_URL_QUERY) == parse_url($tplHelper->makeBaseUrl("full") . $quicklink[2], PHP_URL_QUERY)) { echo " selected"; } ?>" href="<?php echo $quicklink[2]; ?>">
					<img src='<?php echo $quicklink[1]; ?>' alt='<?php echo $quicklink[0]; ?>'><?php echo $quicklink[0]; if (stripos($quicklink[2], 'New:0') && $tplHelper->getNewCountForFilter($quicklink[2])) { echo "<span class='newspots'>".$tplHelper->getNewCountForFilter($quicklink[2])."</span>"; } ?></a>
<?php } ?>
					</ul>
					
                    <h4><span class="viewState"><a onclick="toggleSidebarItem(this)"></a></span>Filters </h4>
                    <ul class="filterlist filters">

<?php
    foreach($filters as $filter) {
		$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $filter[2];
		$newCount = $tplHelper->getNewCountForFilter($strFilter);
?>
						<li<?php if($filter[2]) { echo " class='". $tplHelper->filter2cat($filter[2]) ."'"; } ?>>
						<a class="filter<?php echo " " . $filter[3]; if ($tplHelper->makeSelfUrl("path") == $strFilter) { echo " selected"; } ?>" href="<?php echo $strFilter;?>">
						<img src='<?php echo $filter[1]; ?>' alt='<?php echo $filter[0]; ?>'><?php echo $filter[0]; if ($newCount) { echo "<span onclick=\"gotoNew('".$strFilter."')\" class='newspots' title='Laat nieuwe spots in filter &quot;".$filter[0]."&quot; zien'>$newCount</span>"; } ?><span class='toggle' title='Filter uitklappen' onclick='toggleFilter(this)'>&nbsp;</span></a>
<?php
		if (!empty($filter[4])) {
			echo "\t\t\t\t\t\t\t<ul class='filterlist subfilterlist'>\r\n";
			foreach($filter[4] as $subFilter) {
				$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $subFilter[2];
				$newSubCount = $tplHelper->getNewCountForFilter($strFilter);
?>
						<li> <a class="filter<?php echo " " . $subFilter[3]; if ($tplHelper->makeSelfUrl("path") == $strFilter) { echo " selected"; } ?>" href="<?php echo $strFilter;?>">
						<img src='<?php echo $subFilter[1]; ?>' alt='<?php echo $subFilter[0]; ?>'><?php echo $subFilter[0]; if ($newSubCount) { echo "<span onclick=\"gotoNew('".$strFilter."')\" class='newspots' title='Laat nieuwe spots in filter &quot;".$subFilter[0]."&quot; zien'>$newSubCount</span>"; } ?></a>
<?php
				if (!empty($subFilter[4])) {
					echo "\t\t\t\t\t\t\t<ul class='filterlist subfilterlist'>\r\n";
					foreach($subFilter[4] as $sub2Filter) {
						$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $sub2Filter[2];
						$newSub2Count = $tplHelper->getNewCountForFilter($strFilter);
		?>
						<li> <a class="filter<?php echo " " . $sub2Filter[3]; if ($tplHelper->makeSelfUrl("path") == $strFilter) { echo " selected"; } ?>" href="<?php echo $strFilter;?>">
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
						<li><a href="retrieve.php?output=xml" onclick="retrieveSpots()" class="greyButton retrievespots">Update Spots</a></li>
<?php } ?>
<?php if ($settings->get('keep_downloadlist')) { ?>
						<li><a href="<?php echo $tplHelper->getPageUrl('erasedls'); ?>" onclick="eraseDownloads()" class="greyButton erasedownloads">Verwijder downloadgeschiedenis</a></li>
<?php } ?>
						<li><a href="<?php echo $tplHelper->getPageUrl('markallasread'); ?>" onclick="markAsRead()" class="greyButton markasread">Markeer alles als gelezen</a></li>
					</ul>
				</div>