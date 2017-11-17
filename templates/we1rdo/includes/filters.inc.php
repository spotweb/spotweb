<?php
    SpotTiming::start('tpl:filters');

	// We definieeren hier een aantal settings zodat we niet steeds dezelfde check hoeven uit te voeren
	$count_newspots = ($currentSession['user']['prefs']['count_newspots']);
	$show_multinzb_checkbox = ($currentSession['user']['prefs']['show_multinzb']);
?>
		
			<div id="toolbar">
				<div class="notifications">
					<?php if ($show_multinzb_checkbox) { ?>
					<p class="multinzb"><a class="button" onclick="downloadMultiNZB(spotweb_nzbhandler_type)" title="<?php echo _('MultiNZB'); ?>"><span class="count"></span></a><a class="clear" onclick="uncheckMultiNZB()" title="<?php echo _('Reset selection'); ?>">[x]</a></p>
					<?php } ?>
				</div>

				<div class="toolbarButton logininfo dropdown right"><ul>
					<li><p><a
<?php if ($currentSession['user']['userid'] != SPOTWEB_ANONYMOUS_USERID) { ?>
					title="<?php echo sprintf(_('Last seen: %s ago'), $tplHelper->formatDate($currentSession['user']['lastvisit'], 'lastvisit')); ?>"
					><?php echo $currentSession['user']['username']; ?></a></p>
<?php } else { ?>
					><?php echo _("Log in"); ?></a></p>
<?php } ?>
					<ul>
<?php if (($tplHelper->allowed(SpotSecurity::spotsec_perform_login, '')) && ($currentSession['user']['userid'] == $settings->get('nonauthenticated_userid'))) { ?>
					<li><a href="<?php echo $tplHelper->makeLoginAction(); ?>" onclick="return openDialog('editdialogdiv', '<?php echo _('Login'); ?>', '?page=login&data[htmlheaderssent]=true', null, 'autoclose', function() { window.location.reload(); }, null); "><?php echo _('Login'); ?></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_create_new_user, '')) { ?>
						<li><a href="" onclick="return openDialog('editdialogdiv', '<?php echo _('Add user'); ?>', '?page=createuser', null, 'showresultsonly', null, null); "><?php echo _('Add user'); ?></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_edit_own_user, '')) { ?>
						<li><a href="<?php echo $tplHelper->makeEditUserUrl($currentSession['user']['userid'], 'edit'); ?>" onclick="return openDialog('editdialogdiv', '<?php echo _('Change user'); ?>', '?page=edituser&userid=<?php echo $currentSession['user']['userid'] ?>', null, 'autoclose',  function() { window.location.reload(); }, null);"><?php echo _('Change user'); ?></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_perform_logout, '')) { ?>
						<li><a href="#" onclick="userLogout()"><?php echo _('Log out'); ?></a></li>
<?php } ?>
					</ul></li>
					</li>
				</ul></div>

<?php if (
			($tplHelper->allowed(SpotSecurity::spotsec_edit_own_userprefs, '')) 
				||
			($tplHelper->allowed(SpotSecurity::spotsec_view_spotweb_updates, ''))
				|| 
			($tplHelper->allowed(SpotSecurity::spotsec_edit_settings, ''))
				||
			($tplHelper->allowed(SpotSecurity::spotsec_edit_other_users, ''))
				|| 
			($tplHelper->allowed(SpotSecurity::spotsec_edit_securitygroups, ''))
				|| 
			($tplHelper->allowed(SpotSecurity::spotsec_list_all_users, ''))
		 ) { ?>
				<div class="toolbarButton config dropdown right"><ul>
					<li><p><a><?php echo _('Config'); ?></a></p>
					<ul>
	<?php if ($tplHelper->allowed(SpotSecurity::spotsec_edit_own_userprefs, '')) { ?>
						<li><a href="<?php echo $tplHelper->makeEditUserPrefsUrl($currentSession['user']['userid']); ?>"><?php echo _('Change preferences'); ?></a></li>
	<?php } ?>
	<?php if (
			($tplHelper->allowed(SpotSecurity::spotsec_view_spotweb_updates, ''))
				|| 
			($tplHelper->allowed(SpotSecurity::spotsec_edit_settings, ''))
	) { ?>
						<li><a href="?page=editsettings"><?php echo _('Settings'); ?></a></li>
	<?php } ?>
	<?php if (
			($tplHelper->allowed(SpotSecurity::spotsec_edit_other_users, ''))
				|| 
			($tplHelper->allowed(SpotSecurity::spotsec_edit_securitygroups, ''))
				|| 
			($tplHelper->allowed(SpotSecurity::spotsec_list_all_users, ''))
		 ) { ?>
					<li><a href="?page=render&amp;tplname=usermanagement"><?php echo _('User &amp; group management'); ?></a></li>
	<?php } ?>
					</ul></li>
					</li>
				</ul></div>
<?php 
	}
?>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_post_spot, '') && $currentSession['user']['userid'] > SPOTWEB_ADMIN_USERID) { ?>
				<div class="toolbarButton addspot"><p><a onclick="return openDialog('editdialogdiv', '<?php echo _('Add spot'); ?>', '<?php echo $tplHelper->getPageUrl('postspot'); ?>', function() { new SpotPosting().postNewSpot(this.form, postSpotUiStart, postSpotUiDone); return false; }, 'autoclose', null, null);" title='<?php echo _('Add spot'); ?>'><?php echo _('Add spot'); ?></a></p></div>
<?php } ?>

			<span class="scroll"><input type="checkbox" name="filterscroll" id="filterscroll" value="Scroll" title="<?php echo _('Switch between static or scrolling sidebar'); ?>"><label>&nbsp;</label></span>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_perform_search, '')) { ?>
				<form id="filterform" action="<?php echo $tplHelper->makeSelfUrl('')?>" onsubmit="submitFilterBtn(this)">
				<input type="hidden" id="searchfilter-includeprevfilter-toggle" name="search[includeinfilter]" value="false" />
<?php
	// Omdat we nu op meerdere criteria tegelijkertijd kunnen zoeken is dit onmogelijk
	// om 100% juist in de UI weer te geven. We doen hierdoor een gok die altijd juist
	// is zolang je maar zoekt via de UI.
	// Voor uitgebreide filters tonen we een lijst met op dat moment actieve filters
	$searchType = 'Title';
	$searchText = '';
	
	# Zoek nu een filter op dat eventueel matched, dan gebruiken we die. We willen deze 
	# boom toch doorlopen ook al is er meer dan 1 filter, anders kunnen we de filesize
	# en reportcount niet juist zetten
    $textSearchCount = 0;
    foreach($parsedsearch['filterValueList'] as $filterType) {
		if (in_array($filterType['fieldname'], array('Titel', 'Title', 'Poster', 'Tag', 'SpotterID'))) {
			$searchType = $filterType['fieldname'];
			$searchText = $filterType['value'];
            $textSearchCount++;
		} elseif ($filterType['fieldname'] == 'filesize' && $filterType['operator'] == ">") {
			$minFilesize = $filterType['value'];
		} elseif ($filterType['fieldname'] == 'filesize' && $filterType['operator'] == "<") {
			$maxFilesize = $filterType['value'];
		} elseif ($filterType['fieldname'] == 'reportcount' && $filterType['operator'] == "<=") {
			$maxReportCount = $filterType['value'];
		} elseif ($filterType['fieldname'] == 'date') {
			$ageFilter = $filterType['operator'] . $filterType['value'];
		} # if
	} # foreach

	# Als er een sortering is die we kunnen gebruiken, dan willen we ook dat
	# in de UI weergeven
	$tmpSort = $tplHelper->getActiveSorting();
	$sortType = strtolower($tmpSort['friendlyname']);
	$sortOrder = strtolower($tmpSort['direction']);
	
	/*
	 * Als er geen sorteer volgorde opgegeven is door de user, dan gebruiken we de user
	 * preference om een sorteerveld te pakken
	 */	
	if (empty($sortType)) {
		$sortType = $currentSession['user']['prefs']['defaultsortfield'];
	} # if

	# als er meer dan 1 filter is, dan tonen we dat als een lijst
	if ($textSearchCount > 1) {
		$searchText = '';
		$searchType = 'Title';
	} # if

	# Zorg er voor dat de huidige filterwaardes nog beschikbaar zijn
	foreach($parsedsearch['filterValueList'] as $filterType) {
		if (in_array($filterType['fieldname'], array('Titel', 'Title', 'Poster', 'Tag', 'SpotterID'))) {
			echo '<input data-currentfilter="true" type="hidden" name="search[value][]" value="' . $filterType['fieldname'] . ':=:'  . htmlspecialchars($filterType['booloper']) . ':' . htmlspecialchars($filterType['value'], ENT_QUOTES, 'utf-8') . '">';
		} # if
	} # foreach
?>


<script type='text/javascript'>
    var sliderMinFileSize = <?php echo (isset($minFilesize)) ? $minFilesize : "0"; ?>;
    var sliderMaxFileSize = <?php echo (isset($maxFilesize)) ? $maxFilesize : (1024*1024*1024) * 512; ?>;
    var sliderMaxReportCount = <?php echo (isset($maxReportCount)) ? $maxReportCount : "21"; ?>;
</script>

				<div><input type="hidden" id="search-tree" name="search[tree]" value="<?php echo $tplHelper->categoryListToDynatree(); ?>"></div>
<?php
	$filterColCount = 4;
?>
					<div class="search"><input class='searchbox' type="text" name="search[text]" value="<?php echo htmlspecialchars($searchText); ?>"><input type='submit' class="filtersubmit" value='+' onclick='$("#searchfilter-includeprevfilter-toggle").val("true");' title='<?php echo _('Search within current filters'); ?>'><input type='submit' class="filtersubmit default" onclick='$("#searchfilter-includeprevfilter-toggle").val(""); return true;' value='>>' title='<?php echo _('Search'); ?>'></div>

					<div class="sidebarPanel advancedSearch">
					<h4><a class="toggle" onclick="toggleSidebarPanel('.advancedSearch')" title="<?php echo _("Close 'Advanced Search'"); ?>">[x]</a><?php echo _('Search on:'); ?></h4>
						<ul class="search <?php if ($filterColCount == 3) {echo " threecol";} else {echo " fourcol";} ?>">
							<li> <input type="radio" name="search[type]" value="Title" <?php echo $searchType == "Title" ? 'checked="checked"' : "" ?> ><label><?php echo _('Title'); ?></label></li>
							<li> <input type="radio" name="search[type]" value="Poster" <?php echo $searchType == "Poster" ? 'checked="checked"' : "" ?> ><label><?php echo _('Poster'); ?></label></li>
							<li> <input type="radio" name="search[type]" value="Tag" <?php echo $searchType == "Tag" ? 'checked="checked"' : "" ?> ><label><?php echo _('Tag'); ?></label></li>
							<li> <input type="radio" name="search[type]" value="SpotterID" <?php echo $searchType == "SpotterID" ? 'checked="checked"' : "" ?> ><label><?php echo _('SpotterID'); ?></label></li>
						</ul>

<?php
	if ($textSearchCount > 0) {
?>
						<h4><?php echo _('Active filters:'); ?></h4>
						<table class='search currentfilterlist'>
<?php
	foreach($parsedsearch['filterValueList'] as $filterType) {
		if (in_array($filterType['fieldname'], array('Titel', 'Title', 'Poster', 'Tag', 'SpotterID'))) {
?>
							<tr> <th> <?php echo ($filterType['fieldname'] == 'Title') ? _('Title') : _($filterType['fieldname']); ?> </th> <td> <?php echo htmlspecialchars($filterType['booloper'], ENT_QUOTES, 'UTF-8'); ?> </td> <td> <?php echo htmlentities($filterType['value'], ENT_QUOTES, 'UTF-8'); ?> </td> <td> <a href="javascript:location.href=removeFilter('?page=index<?php echo addcslashes(urldecode($tplHelper->convertFilterToQueryParams()), "\\\'\"&\n\r<>"); ?>', '<?php echo $filterType['fieldname']; ?>', '<?php echo $filterType['operator']; ?>', '<?php echo $filterType['booloper']; ?>', '<?php echo $filterType['booloper']; ?>', '<?php echo addcslashes(htmlspecialchars($filterType['value'], ENT_QUOTES, 'utf-8'), "\\\'\"&\n\r<>"); ?>');">x</a> </td> </tr>
<?php
		} # if
	} # foreach
?>
						</table>
<?php						
	}
?>
						 <h4><?php echo _('Sort by'); ?>:</h4>
						<input type="hidden" name="sortdir" value="<?php if($sortType == "stamp" || $sortType == "spotrating" || $sortType == "commentcount") {echo "DESC";} else {echo "ASC";} ?>">
						<ul class="search sorting threecol">
							<li> <input type="radio" name="sortby" value="" <?php echo $sortType == "" ? 'checked="checked"' : "" ?>><label><?php echo _('Relevance'); ?></label> </li>
							<li> <input type="radio" name="sortby" value="title" <?php echo $sortType == "title" ? 'checked="checked"' : "" ?>><label><?php echo _('Title'); ?></label> </li>
							<li> <input type="radio" name="sortby" value="poster" <?php echo $sortType == "poster" ? 'checked="checked"' : "" ?>><label><?php echo _('Poster');?></label> </li>
							<li> <input type="radio" name="sortby" value="stamp" <?php echo $sortType == "stamp" ? 'checked="checked"' : "" ?>><label><?php echo _('Date');?></label> </li>
							<li> <input type="radio" name="sortby" value="commentcount" <?php echo $sortType == "commentcount" ? 'checked="checked"' : "" ?>><label><?php echo _('Comments'); ?></label> </li>
							<li> <input type="radio" name="sortby" value="spotrating" <?php echo $sortType == "spotrating" ? 'checked="checked"' : "" ?>><label><?php echo _('Rating'); ?></label> </li>
						</ul>

						<h4><?php echo _('Limit age'); ?></h4>
						<ul class="search age onecol">
<?php if (!isset($ageFilter)) { $ageFilter = ''; } ?>
							<li><select name="search[value][]">
								<option value=""><?php echo _('Show all'); ?></option>
								<option value="date:>:DEF:-1 day" <?php echo $ageFilter == ">-1 day" ? 'selected="selected"' : "" ?>><?php echo _('1 day'); ?></option>
								<option value="date:>:DEF:-3 days" <?php echo $ageFilter == ">-3 days" ? 'selected="selected""' : "" ?>><?php echo _('3 days'); ?></option>
								<option value="date:>:DEF:-1 week" <?php echo $ageFilter == ">-1 week" ? 'selected="selected""' : "" ?>><?php echo _('1 week'); ?></option>
								<option value="date:>:DEF:-2 weeks" <?php echo $ageFilter == ">-2 weeks" ? 'selected="selected"' : "" ?>><?php echo _('2 weeks'); ?></option>
								<option value="date:>:DEF:-1 month" <?php echo $ageFilter == ">-1 month" ? 'selected="selected"' : "" ?>><?php echo _('1 month'); ?></option>
								<option value="date:>:DEF:-3 months" <?php echo $ageFilter == ">-3 months" ? 'selected="selected"' : "" ?>><?php echo _('3 months'); ?></option>
								<option value="date:>:DEF:-6 months" <?php echo $ageFilter == ">-6 months" ? 'selected="selected"' : "" ?>><?php echo _('6 months'); ?></option>
								<option value="date:>:DEF:-1 year" <?php echo $ageFilter == ">-1 year" ? 'selected="selected"' : "" ?>><?php echo _('1 year'); ?></option>
							</select></li>
						</ul>
					
						<h4><?php echo _('Size'); ?></h4>
						<input type="hidden" name="search[value][]" id="min-filesize" />
						<input type="hidden" name="search[value][]" id="max-filesize" />
						<div id="human-filesize"></div>
						<div id="slider-filesize"></div>

						<h4><?php echo _('Categories'); ?></h4>
						<div id="tree"></div>
						<ul class="search clearCategories onecol">
							<li> <input type="checkbox" name="search[unfiltered]" value="true" <?php echo $parsedsearch['unfiltered'] == "true" ? 'checked="checked"' : '' ?>>
							
							<label><?php if ($parsedsearch['unfiltered'] == 'true') { echo _("Use categories"); } else { echo _("Don't use categories"); } ?></label> </li>
						</ul>

<?php if ($settings->get('retrieve_reports')) { ?>
						<h4><?php echo _('Number of reports'); ?></h4>
						<input type="hidden" name="search[value][]" id="max-reportcount" />
						<div id="human-reportcount"></div>
						<div id="slider-reportcount"></div>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_filters, '')) { ?>
						<h4><?php echo _('Filters'); ?></h4>
						<a onclick="return openDialog('editdialogdiv', '<?php echo _('Add a filter'); ?>', '?page=render&amp;tplname=editfilter&amp;data[isnew]=true<?php echo addcslashes($tplHelper->convertTreeFilterToQueryParams() .$tplHelper->convertTextFilterToQueryParams() . $tplHelper->convertSortToQueryParams(), "\\\'\"&\n\r<>"); ?>', null, 'autoclose', null, null); " class="greyButton addFilter"><?php echo _('Save search as filter'); ?></a>
<?php } ?>
				</div>
			</form>
<?php } # if perform search ?>

				<div class="sidebarPanel sabnzbdPanel">
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_use_sabapi, '')) { ?>
					<h4><a class="toggle" onclick="toggleSidebarPanel('.sabnzbdPanel')" title='<?php echo _('Sluit "' . $tplHelper->getNzbHandlerName() . 'paneel"'); ?>'>[x]</a><?php echo $tplHelper->getNzbHandlerName(); ?></h4>
<?php 
		$apikey = $tplHelper->apiToHash($currentSession['user']['apikey']);
		echo "<input class='apikey' type='hidden' value='".$apikey."'>";
		if ($tplHelper->getNzbHandlerApiSupport() === false)
		{?>
					<table class="sabInfo" summary="SABnzbd infomatie">
						<tr><td><?php echo _('Selected NZB download methode doesn\'t support sidepanel'); ?></td></tr>
					</table>			
<?php	}
		else
		{
?>					<table class="sabInfo" summary="SABnzbd infomatie">
						<tr><td><?php echo _('Status:'); ?></td><td class="state"></td></tr>
						<tr><td><?php echo _('Free storage:'); ?></td><td class="diskspace"></td></tr>
						<tr><td><?php echo _('Speed:'); ?></td><td class="speed"></td></tr>
						<tr><td><?php echo _('Max. speed:'); ?></td><td class="speedlimit"></td></tr>
						<tr><td><?php echo _('To go:'); ?></td><td class="timeleft"></td></tr>
						<tr><td><?php echo _('ETA:'); ?></td><td class="eta"></td></tr>
						<tr><td><?php echo _('Queue:'); ?></td><td class="mb"></td></tr>
					</table>
					<canvas id="graph" width="215" height="125"></canvas>
					<table class="sabGraphData" summary="SABnzbd Graph Data" style="display:none;"><tbody><tr><td></td></tr></tbody></table>
					<h4><?php echo _('Queue'); ?></h4>
					<table class="sabQueue" summary="SABnzbd queue"><tbody><tr><td></td></tr></tbody></table>
<?php 	}
	  } ?>
				</div>
			</div>

			<div id="filter" class="filter">
				<a class="viewState" onclick="toggleSidebarItem(this)"><h4><?php echo _('Quick Links'); ?><span></span></h4></a>
				<ul class="filterlist quicklinks">
<?php foreach($quicklinks as $quicklink) {
		if ($tplHelper->allowed($quicklink[4][0], $quicklink[4][1])) {
			if (empty($quicklink[5]) || $currentSession['user']['prefs'][$quicklink[5]]) {
				$newCount = ($count_newspots && stripos($quicklink[2], 'New:0')) ? $tplHelper->getNewCountForFilter($quicklink[2]) : "";
?>
					<li> <a class="filter <?php echo " " . $quicklink[3]; if (parse_url($tplHelper->makeSelfUrl("full"), PHP_URL_QUERY) == parse_url($tplHelper->makeBaseUrl("full") . $quicklink[2], PHP_URL_QUERY)) { echo " selected"; } ?>" href="<?php echo $quicklink[2]; ?>">
					<a class="filter <?php if (parse_url($tplHelper->makeSelfUrl("full"), PHP_URL_QUERY) == parse_url($tplHelper->makeBaseUrl("full") . $quicklink[2], PHP_URL_QUERY)) { echo " selected"; } ?>" href="<?php echo $quicklink[2]; ?>">
					<span class='spoticon spoticon-<?php echo str_replace('images/icons/', '', str_replace('.png', '', $quicklink[1])); ?>'>&nbsp;</span><?php echo $quicklink[0]; if ($newCount > 0) { echo "<span class='newspots'>".$newCount."</span>"; } ?></a>
<?php 		}
		}
	} ?>
					</ul>

					<a class="viewState" onclick="toggleSidebarItem(this)"><h4><?php echo _('Filters'); ?><span></span></h4></a>
					<ul class="filterlist filters">

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
			$newCount = ($count_newspots) ? $tplHelper->getNewCountForFilter($strFilter) : "";

			/* add the current search terms */
			$strFilterInclusive =  $strFilter . $tplHelper->convertSortToQueryParams() . $tplHelper->convertTextFilterToQueryParams();

			# escape the filter values
			$filter['title'] = htmlentities($filter['title'], ENT_QUOTES, 'UTF-8');
			$filter['icon'] = htmlentities($filter['icon'], ENT_QUOTES, 'UTF-8');
			
			# Output de HTML
			echo '<li class="'. $tplHelper->filter2cat($filter['tree']) .'">';
			echo '<a class="filter ' . $filter['title'];
			
			if ($selfUrl == $strFilter) { 
				echo ' selected';
			} # if
			
			echo '" href="' . $strFilter . '">';
			echo '<span class="spoticon spoticon-' . str_replace('.png', '', $filter['icon']) . '">&nbsp;</span>' . $filter['title'];
			if ($newCount > 0) { 
				echo "<span onclick=\"gotoNew('".$strFilter."')\" class='newspots' title='" . sprintf(_('Show new spots in filter &quot;%s&quot;'), $filter['title']) . "'>$newCount</span>";
			} # if 

			# als er children zijn, moeten we de category kunnen inklappen
			if (!empty($filter['children'])) {
				echo '<span class="toggle" title="' . _('Collapse filter') . '" onclick="toggleFilter(this)">&nbsp;</span>';
			} # if

			# show the inclusive filter
			echo '<span onclick="gotoFilteredCategory(\'' . $strFilterInclusive . '\')" class="inclusive" title="' . _('Include current search terms') . '">+</span>';
			
			echo '</a>';
			
			# Als er children zijn, output die ook
			if (!empty($filter['children'])) {
				echo '<ul class="filterlist subfilterlist">';
				processFilters($tplHelper, $count_newspots, $filter['children']);
				echo '</ul>';
			} # if
			
			echo '</li>' . PHP_EOL;
		} # foreach
	} # processFilters
	
	processFilters($tplHelper, $count_newspots, $filters);
?>
					</ul>

					<a class="viewState" onclick="toggleSidebarItem(this)"><h4><?php echo _('Maintenance'); ?><span></span></h4></a>
					<ul class="filterlist maintenancebox">
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_view_spotcount_total, '')) { ?>
						<li class="info"> <?php echo _('Last update:'); ?> <?php echo $tplHelper->formatDate($tplHelper->getLastSpotUpdates(), 'lastupdate'); ?> </li>
<?php } ?>
<?php 
		if ($currentSession['user']['userid'] > SPOTWEB_ADMIN_USERID) {
			if ( ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_spots, '')) && ($tplHelper->allowed(SpotSecurity::spotsec_consume_api, ''))) { ?>
						<li><a href="<?php echo $tplHelper->makeRetrieveUrl(); ?>" onclick="retrieveSpots(this)" class="greyButton retrievespots"><?php echo _('Retrieve'); ?></a></li>
<?php 		}
		} ?>
<?php if (($tplHelper->allowed(SpotSecurity::spotsec_keep_own_downloadlist, '')) && ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_downloadlist, 'erasedls'))) { ?>
						<li><a href="<?php echo $tplHelper->getPageUrl('erasedls'); ?>" onclick="eraseDownloads()" class="greyButton erasedownloads"><?php echo _('Erase downloadhistory'); ?></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_seenlist, '')) { ?>
						<li><a href="<?php echo $tplHelper->getPageUrl('markallasread'); ?>" onclick="markAsRead()" class="greyButton markasread"><?php echo _('Mark everything as read'); ?></a></li>
<?php } ?>
					</ul>
				</div>

<?php
    SpotTiming::stop('tpl:filters');
