<?php 
	/* Render de header en filter templates */
	if (!isset($data['spotsonly'])) {
		require_once "includes/header.inc.php";	
		require_once "includes/filters.inc.php";
	} # if

	// We definieeren hier een aantal settings zodat we niet steeds dezelfde check hoeven uit te voeren
	$show_nzb_button = $tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, '');
	$show_watchlist_button = ($currentSession['user']['prefs']['keep_watchlist'] && $tplHelper->allowed(SpotSecurity::spotsec_keep_own_watchlist, ''));
	$show_comments = ($settings->get('retrieve_comments') && $tplHelper->allowed(SpotSecurity::spotsec_view_comments, ''));
	$show_filesize = $currentSession['user']['prefs']['show_filesize'];
	$show_multinzb_checkbox = ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, '') && ($currentSession['user']['prefs']['show_multinzb']));
	
?>
			<div class="spots">
				<table class="spots" summary="Spots">
					<thead>
						<tr class="head">
							<th class='category'> <a href="<?php echo $tplHelper->makeSortUrl('index', 'category', ''); ?>" title="Sorteren op Categorie">Cat.</a> </th> 
							<th class='title'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'title', 'ASC'); ?>" title="Sorteren op Titel [0-Z]"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'title', 'DESC'); ?>" title="Sorteren op Titel [Z-0]"> </a></span> Titel </th> 
							<?php if ($show_watchlist_button) { ?>
							<th class='watch'> </th>
							<?php }
							if ($show_comments) {
								echo "<th class='comments'> <a title='Aantal reacties'>#</a> </th>";
							} # if ?>
							<th class='genre'> Genre </th> 
							<th class='poster'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'poster', 'ASC'); ?>" title="Sorteren op Afzender [0-Z]"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'poster', 'DESC'); ?>" title="Sorteren op Afzender [Z-0]"> </a></span> Afzender </th> 
							<th class='date'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'stamp', 'DESC'); ?>" title="Sorteren op Leeftijd [oplopend]"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'stamp', 'ASC'); ?>" title="Sorteren op Leeftijd [aflopend]"> </a></span> <?php echo ($currentSession['user']['prefs']['date_formatting'] == 'human') ? "Leeftijd" : "Datum"; ?> </th> 
<?php if ($show_filesize) { ?>
							<th class='filesize'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'filesize', 'DESC'); ?>" title="Sorteren op Omvang [aflopend]"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'filesize', 'ASC'); ?>" title="Sorteren op Omvang [oplopend]"> </a></span> Size </th> 
<?php } ?>
<?php if ($show_nzb_button) { ?>
							<th class='nzb'> NZB </th>
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
							<th class='sabnzbd'><a class="toggle" onclick="toggleSidebarPanel('.sabnzbdPanel')" title='Open "<?php echo $tplHelper->getNzbHandlerName(); ?> paneel"'></a></th>
<?php } ?>						
						</tr>
					</thead>
					<tbody id="spots">
<?php
	if (count($spots) == 0) {
		$colSpan = 5;
		$nzbHandlingTmp = $currentSession['user']['prefs']['nzbhandling'];
		if ($show_comments) { $colSpan++; }
		if ($show_nzb_button) { $colSpan++; }
		if ($show_filesize) { $colSpan++; }
		if ($show_multinzb_checkbox) { $colSpan++; }
		if ($show_watchlist_button) { $colSpan++; }
		if ($nzbHandlingTmp['action'] != 'disable') { $colSpan++; }
		
		echo "\t\t\t\t\t\t\t<tr class='noresults'><td colspan='" . $colSpan . "'>Geen resultaten gevonden</td></tr>\r\n";
	} # if
	
	foreach($spots as $spot) {
		# Format the spot header
		$spot = $tplHelper->formatSpotHeader($spot);
		$newSpotClass = ($tplHelper->isSpotNew($spot)) ? 'new' : '';
	
		if($spot['rating'] == 0) {
			$rating = '';
		} elseif($spot['rating'] == 1) {
			$rating = '<span class="rating" title="Deze spot heeft '.$spot['rating'].' ster"><span style="width:' . $spot['rating'] * 4 . 'px;"></span></span>';
		} else {
			$rating = '<span class="rating" title="Deze spot heeft '.$spot['rating'].' sterren"><span style="width:' . $spot['rating'] * 4 . 'px;"></span></span>';
		}

		if($tplHelper->isModerated($spot)) { 
			$markSpot = '<span class="markSpot">!</span>';
		} else {
			$markSpot = '';
		}

		echo "\t\t\t\t\t\t\t";
		echo "<tr class='" . $tplHelper->cat2color($spot);
		if ($spot['hasbeendownloaded']) {
			echo " downloadedspot";
		} # if
		echo "'>";
		echo "<td class='category'><a href='" . $spot['caturl'] . "' title='Ga naar de categorie \"" . $spot['catshortdesc'] . "\"'>" . $spot['catshortdesc'] . "</a></td>" .
			 "<td class='title " . $newSpotClass . "'><a onclick='openSpot(this,\"".$spot['spoturl']."\")' href='".$spot['spoturl']."' title='" . $spot['title'] . "' class='spotlink'>" . $rating . $markSpot . $spot['title'] . "</a></td>";

		if ($show_watchlist_button) {
			echo "<td class='watch'>";
			echo "<a class='remove watchremove_".$spot['id']."' onclick=\"toggleWatchSpot('".$spot['messageid']."','remove',".$spot['id'].")\""; if(!$spot['isbeingwatched']) { echo " style='display: none;'"; } echo " title='Verwijder uit watchlist (w)'> </a>";
			echo "<a class='add watchadd_".$spot['id']."' onclick=\"toggleWatchSpot('".$spot['messageid']."','add',".$spot['id'].")\""; if($spot['isbeingwatched']) { echo " style='display: none;'"; } echo " title='Plaats in watchlist (w)'> </a>";
			echo "</td>";
		}

		if ($show_comments) {
			echo "<td class='comments'><a onclick='openSpot(this,\"".$spot['spoturl']."\")' class='spotlink' href='" . $spot['spoturl'] . "#comments' title='" . $spot['commentcount'] . " comments bij \"" . $spot['title'] . "\"'>" . $spot['commentcount'] . "</a></td>";
		} # if
		
		echo "<td class='genre'><a href='" . $spot['subcaturl'] . "' title='Zoek spots in de categorie " . $spot['catdesc'] . "'>" . $spot['catdesc'] . "</a></td>" .
			 "<td class='poster'><a href='" . $spot['posterurl'] . "' title='Zoek spots van " . $spot['poster'] . "'>" . $spot['poster'] . "</a></td>" .
			 "<td class='date'>" . $tplHelper->formatDate($spot['stamp'], 'spotlist') . "</td>";

		if ($show_filesize) {
			echo "<td class='filesize'>" . $tplHelper->format_size($spot['filesize']) . "</td>";
		} 

		# only display the NZB button from 24 nov or later
		if ($spot['stamp'] > 1290578400 ) {
			if ($show_nzb_button) {
				echo "<td class='nzb'><a href='" . $tplHelper->makeNzbUrl($spot) . "' title ='Download NZB (n)' class='nzb'>NZB";
				
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

			# display the sabnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				if ($spot['hasbeendownloaded']) {
					echo "<td class='sabnzbd'><a onclick=\"downloadSabnzbd('".$spot['id']."','".$spot['sabnzbdurl']."')\" class='sab_".$spot['id']." sabnzbd-button succes' title='Add NZB to SabNZBd queue (you already downloaded this spot) (s)'> </a></td>";
				} else {
					echo "<td class='sabnzbd'><a onclick=\"downloadSabnzbd('".$spot['id']."','".$spot['sabnzbdurl']."')\" class='sab_".$spot['id']." sabnzbd-button' title='Add NZB to SabNZBd queue (s)'> </a></td>";	
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
