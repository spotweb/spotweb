<?php
	/* Render de header en filter templates */
	if (!isset($data['spotsonly'])) {
		require_once "includes/header.inc.php";	
		require_once "includes/filters.inc.php";
	} # if

	// We definieeren hier een aantal settings zodat we niet steeds dezelfde check hoeven uit te voeren
	$show_watchlist_button = ($currentSession['user']['prefs']['keep_watchlist'] && $tplHelper->allowed(SpotSecurity::spotsec_keep_own_watchlist, ''));
	$show_comments = ($settings->get('retrieve_comments') && $tplHelper->allowed(SpotSecurity::spotsec_view_comments, ''));
	$show_filesize = $currentSession['user']['prefs']['show_filesize'];
	$show_spamreports = $currentSession['user']['prefs']['show_reportcount'];
	$minimum_spamreports = $currentSession['user']['prefs']['minimum_reportcount'];
	$show_nzb_button = ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, '') && ($currentSession['user']['prefs']['show_nzbbutton']));
	$show_multinzb_checkbox = ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, '') && ($currentSession['user']['prefs']['show_multinzb']));
	$show_mouseover_subcats = ($currentSession['user']['prefs']['mouseover_subcats']);
	$newCommentCount = array();
	$noResults = (count($spots) == 0);

	/*
	 * For Seen, Watched en MyPosted-spots we want to show a list of
	 * comments users haven't seen yet. We check whether this is such
	 * a list by checking for the existence of any of these fields
	 */
	if (!$noResults) { 
		if ( (isset($spots[0]['mypostedspot'])) || (isset($spots[0]['myseenspot'])) || (isset($spots[0]['mywatchedspot'])) ) {
			$newCommentCount = $tplHelper->getNewCommentCountFor($spots);
		} # if
	} # if
?>
			<div class="spots">
				<table class="spots" summary="Spots">
					<thead>
						<tr class="head">
							<th class='category'> <a href="<?php echo $tplHelper->makeSortUrl('index', 'category', ''); ?>" title="<?php echo _('Sort on category'); ?>"><?php echo _('Cat.'); ?></a> </th> 
							<th class='title'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'title', 'ASC'); ?>" title="<?php echo _('Sort on title [0-Z]'); ?>"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'title', 'DESC'); ?>" title="<?php echo _('Sort on title [Z-0]'); ?>"> </a></span> <?php echo _('Title'); ?> </th> 
							<?php if ($show_watchlist_button) { ?>
							<th class='watch'> </th>
							<?php }
							if ($show_comments) {
								echo "<th class='comments'> <a title='" . _('Number of comments') . "' href='" . $tplHelper->makeToggleSortUrl('index', 'commentcount', 'DESC') . "'>#</a> </th>";
							} # if ?>
							<th class='genre'> <?php echo _('Genre'); ?> </th> 
							<th class='poster'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'poster', 'ASC'); ?>" title="<?php echo _('Sort on sender [0-Z]'); ?>"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'poster', 'DESC'); ?>" title="<?php echo _('Sort on sender [Z-0]'); ?>"> </a></span> <?php echo _('Sender'); ?> </th> 
							<th class='date'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'stamp', 'DESC'); ?>" title="<?php echo _('Sort on age [ascending]'); ?>"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'stamp', 'ASC'); ?>" title="<?php echo _('Sort on age [descending]'); ?>"> </a></span> <?php echo ($currentSession['user']['prefs']['date_formatting'] == 'human') ? _('Age') : _('Date'); ?> </th> 
<?php if ($show_filesize) { ?>
							<th class='filesize'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'filesize', 'DESC'); ?>" title="<?php echo _('Sort on size [descending]'); ?>"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'filesize', 'ASC'); ?>" title="<?php echo _('Sort on size [ascending]'); ?>"> </a></span> <?php echo _('Size'); ?> </th> 
<?php } ?>
<?php if ($show_nzb_button) { ?>
							<th class='nzb'> <?php echo _('NZB'); ?> </th>
<?php } ?>
<?php if ($show_multinzb_checkbox && !count($spots) == 0) { ?>
							<th class='multinzb'> 
								<form action="" method="GET" id="checkboxget" name="checkboxget">
									<input type='hidden' name='page' value='getnzb'>
									<input type='checkbox' name='checkall' onclick='checkedAll("checkboxget");'> 
							</th>
<?php } ?>						
<?php $nzbHandlingTmp = $currentSession['user']['prefs']['nzbhandling'];
if (($tplHelper->allowed(SpotSecurity::spotsec_download_integration, $nzbHandlingTmp['action'])) && ($nzbHandlingTmp['action'] != 'disable')) { ?>
							<th class='sabnzbd'><a class="toggle" onclick="toggleSidebarPanel('.sabnzbdPanel')" title='<?php echo sprintf(_('Open "%s panel"'), $tplHelper->getNzbHandlerName()); ?>'></a></th>
<?php } ?>						
						</tr>
					</thead>
					<tbody id="spots">
<?php
	if ($noResults) {
		$colSpan = 5;
		$nzbHandlingTmp = $currentSession['user']['prefs']['nzbhandling'];
		if ($show_comments) { $colSpan++; }
		if ($show_nzb_button) { $colSpan++; }
		if ($show_filesize) { $colSpan++; }
		if ($show_multinzb_checkbox) { $colSpan++; }
		if ($show_watchlist_button) { $colSpan++; }
		if ($nzbHandlingTmp['action'] != 'disable') { $colSpan++; }
		
		echo "\t\t\t\t\t\t\t<tr class='noresults'><td colspan='" . $colSpan . "'>" . _('No results found') . "</td></tr>\r\n";
	} # if
	
	foreach($spots as $spot) {
		# Format the spot header
		$spot = $tplHelper->formatSpotHeader($spot);
		$newSpotClass = ($tplHelper->isSpotNew($spot)) ? 'new' : '';
        $tipTipClass = $show_mouseover_subcats ? 'showTipTip' : '';
		$dateTitleText = $tplHelper->formatDate($spot['stamp'], 'force_spotlist');
		$commentCountValue = $spot['commentcount'];
		if (isset($newCommentCount[$spot['messageid']])) {
			$commentCountValue .= '*';
		} # if

		$catMap = array();
		foreach($spot['subcatlist'] as $sub) {
			$subcatType = substr($sub, 0, 1);
			$subCatDesc = SpotCategories::SubcatDescription($spot['category'], $subcatType);
			$catDesc = SpotCategories::Cat2Desc($spot['category'], $sub);

			if (isset($catMap[$subCatDesc])) {
				$catMap[$subCatDesc] .= ', ' . $catDesc;
			} else {
				$catMap[$subCatDesc] = $catDesc;
			} # else
		} # foreach
		$catData = json_encode($catMap);
	
		if($spot['rating'] == 0) {
			$rating = '';
		} elseif($spot['rating'] > 0) {
			$rating = '<span class="rating" title="' . sprintf(ngettext('This spot has %d star', 'This spot has %d stars', $spot['rating']), $spot['rating']) . '"><span style="width:' . $spot['rating'] * 4 . 'px;"></span></span>';
		}

		if($tplHelper->isModerated($spot)) { 
			$markSpot = '<span class="markSpot">!</span>';
		} else {
			$markSpot = '';
		}
		
		if($spot['idtype'] == 2) {
			$markSpot = '<span class="markGreen">W</span>' . $markSpot;
		}
		
		if($spot['idtype'] == 1) {
			$markSpot = '<span class="markSpot">B</span>' . $markSpot;
		}
		
		$reportSpam = '';
		if ($show_spamreports && $spot['reportcount'] >= $minimum_spamreports) {
			if($spot['reportcount'] == 1) {
				$reportSpamClass = ' grey';
			} elseif ($spot['reportcount'] >= 2 && $spot['reportcount'] < 4) {
				$reportSpamClass = ' orange';
			} elseif ($spot['reportcount'] >= 4 && $spot['reportcount'] < 6) {
				$reportSpamClass = ' darkorange';
			} elseif ($spot['reportcount'] >= 6) {
				$reportSpamClass = ' red';
			}

			$reportSpam = '<span class="reportedSpam'.$reportSpamClass.'" title="' . sprintf(ngettext('There is %d spamreport found for this spot', 'There are %d spamreports found for this spot', $spot['reportcount']), $spot['reportcount']) . '"><span>'.$spot['reportcount'].'</span></span>';
		}

		echo "\t\t\t\t\t\t\t";
		echo "<tr class='" . $tplHelper->cat2color($spot);
		if ($spot['hasbeendownloaded']) {
			echo " downloadedspot";
			
			$dateTitleText .= "\r\n " . _("downloaded on") . ' ' . $tplHelper->formatDate($spot['downloadstamp'], 'force_spotlist');
 		} # if
		if ($spot['hasbeenseen']) {
			echo " seenspot";

			$dateTitleText .= "\r\n " . _("opened on") . ' ' . $tplHelper->formatDate($spot['seenstamp'], 'force_spotlist');
		} # if
		
		if($tplHelper->isModerated($spot)) {
		     echo " moderatedspot";
          } #if 
		echo "'>";
		echo "<td class='category'><a href='" . $spot['caturl'] . "' title=\"" . sprintf(_("Go to category '%s'"), $spot['catshortdesc']) . "\">" . $spot['catshortdesc'] . "</a></td>" .
			 "<td class='title " . $newSpotClass . " ". $tipTipClass . "'><a data-cats='" . $catData. "' onclick='openSpot(this,\"".$spot['spoturl']."\")' href='".$spot['spoturl']."' title='" . $spot['title'] . "' class='spotlink'>" . $reportSpam . $rating . $markSpot . $spot['title'] . "</a></td>";

		if ($show_watchlist_button) {
			echo "<td class='watch'>";
			echo "<a class='remove watchremove_".$spot['id']."' onclick=\"toggleWatchSpot('".$spot['messageid']."','remove',".$spot['id'].")\""; if(!$spot['isbeingwatched']) { echo " style='display: none;'"; } echo " title='" . _('Delete from watchlist (w)') . "'> </a>";
			echo "<a class='add watchadd_".$spot['id']."' onclick=\"toggleWatchSpot('".$spot['messageid']."','add',".$spot['id'].")\""; if($spot['isbeingwatched']) { echo " style='display: none;'"; } echo " title='" . _('Position in watchlist (w)') . "'> </a>";
			echo "</td>";
		}

		if ($show_comments) {
			echo "<td class='comments'><a onclick='openSpot(this,\"".$spot['spoturl']."\")' class='spotlink' href='" . $spot['spoturl'] . "#comments' title=\"" . sprintf(_("%d comments on '%s'"), $spot['commentcount'], $spot['title']) . "\">" . $commentCountValue . "</a></td>";
		} # if
		
		echo "<td class='genre'><a href='" . $spot['subcaturl'] . "' title='" . sprintf(_('Search spot in category %s'), $spot['catdesc']) . "'>" . $spot['catdesc'] . "</a></td>" .
			 "<td class='poster'><a href='" . $spot['posterurl'] . "' title='" . sprintf(_('Search spot from %s'), $spot['poster']) . "'>" . $spot['poster'] . "</a></td>" .
			 "<td class='date' title='" . $dateTitleText . "'>" . $tplHelper->formatDate($spot['stamp'], 'spotlist') . "</td>";

		if ($show_filesize) {
			echo "<td class='filesize'>" . $tplHelper->format_size($spot['filesize']) . "</td>";
		}
		
		# only display the NZB button from 24 nov or later
		if ($spot['stamp'] > 1290578400 ) {
			if ($show_nzb_button) {
				echo "<td class='nzb'><a href='" . $tplHelper->makeNzbUrl($spot) . "' title ='" . _('Download NZB (n)') . "' class='nzb'>NZB";
				
				if ($spot['hasbeendownloaded']) {
					echo '*';
				} # if
				
				echo "</a></td>";
			} # if
			
			if ($show_multinzb_checkbox) {
				$multispotid = htmlspecialchars($spot['messageid']);
				echo "<td class='multinzb'>";
				echo "<input onclick='multinzb()' type='checkbox' name='".htmlspecialchars('messageid[]')."' value='".$multispotid."'>";
				echo "</td>";
			} # if

			# display the SABnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				if ($spot['hasbeendownloaded']) {
					echo "<td class='sabnzbd'><a onclick=\"downloadSabnzbd('".$spot['id']."','".$spot['sabnzbdurl']."')\" class='sab_".$spot['id']." sabnzbd-button succes' title='" . _('Add NZB to SABnzbd queue (you already downloaded this spot) (s)') . "'> </a></td>";
				} else {
					echo "<td class='sabnzbd'><a onclick=\"downloadSabnzbd('".$spot['id']."','".$spot['sabnzbdurl']."')\" class='sab_".$spot['id']." sabnzbd-button' title='" . _('Add NZB to SABnzbd queue (s)'). "'> </a></td>";	
				} # else
			} # if
		} else {
			if ($show_nzb_button) {
				echo "<td class='nzb'> &nbsp; </td>";
			} # if
			
			# display (empty) MultiNZB td
			if ($show_multinzb_checkbox) { 
				echo "<td class='multinzb'> &nbsp; </td>";
			}

			# display the sabnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				echo "<td class='sabnzbd'> &nbsp; </td>";
			} # if
		} # else
		
		echo "</tr>\r\n";
	}
?>
					</tbody>
				</table>
<?php if ($prevPage >= 0 || $nextPage > 0) { ?>
				<table class="footer" summary="Footer">
					<tbody>
						<tr>
<?php if ($prevPage >= 0) { ?> 
							<td class="prev"><a href="?direction=prev&amp;pagenr=<?php echo $prevPage . $tplHelper->convertSortToQueryParams() . $tplHelper->convertFilterToQueryParams(); ?>">&lt;&lt;</a></td>
<?php }?> 
							<td class="button<?php if ($nextPage <= 0) {echo " last";} ?>"></td>
<?php if ($nextPage > 0) { ?> 
							<td class="next"><a href="?direction=next&amp;pagenr=<?php echo $nextPage . $tplHelper->convertSortToQueryParams() . $tplHelper->convertFilterToQueryParams(); ?>">&gt;&gt;</a></td>
<?php } ?>
						</tr>
					</tbody>
				</table>
			<?php if ($show_multinzb_checkbox) { echo "</form>"; } ?>
				<input type="hidden" id="perPage" value="<?php echo $currentSession['user']['prefs']['perpage'] ?>">
				<input type="hidden" id="nextPage" value="<?php echo $nextPage; ?>">
				<input type="hidden" id="getURL" value="<?php echo $tplHelper->convertSortToQueryParams() . $tplHelper->convertFilterToQueryParams(); ?>">
<?php } ?>
			
			</div>
			<div class="clear"></div>

<?php 
	/* Render de header en filter templates */
	if (!isset($data['spotsonly'])) {
		/* Render de footer template */
		require_once "includes/footer.inc.php";
	} # if
