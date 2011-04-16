<?php 
	$getUrl = $tplHelper->getQueryParams(); 
?>
            <div class="spots">
				<table class="spots" summary="Spots">
					<thead>
                        <tr class="head">
                            <th class='category'> <a href="<?php echo $tplHelper->makeSortUrl('index', 'category', ''); ?>" title="Sorteren op Categorie">Cat.</a> </th> 
                            <th class='title'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'title', 'ASC'); ?>" title="Sorteren op Titel [0-Z]"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'title', 'DESC'); ?>" title="Sorteren op Titel [Z-0]"> </a></span> Titel </th> 
                            <?php if ($settings->get('keep_watchlist')) { ?>
                            <th class='watch'> </th>
                            <?php }
                            if ($settings->get('retrieve_comments')) {
                                echo "<th class='comments'> <a title='Aantal reacties'>#</a> </th>";
                            } # if ?>
                            <th class='genre'> Genre </th> 
                            <th class='poster'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'poster', 'ASC'); ?>" title="Sorteren op Afzender [0-Z]"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'poster', 'DESC'); ?>" title="Sorteren op Afzender [Z-0]"> </a></span> Afzender </th> 
                            <th class='date'> <span class="sortby"><a class="up" href="<?php echo $tplHelper->makeSortUrl('index', 'stamp', 'DESC'); ?>" title="Sorteren op Leeftijd [oplopend]"> </a> <a class="down" href="<?php echo $tplHelper->makeSortUrl('index', 'stamp', 'ASC'); ?>" title="Sorteren op Leeftijd [aflopend]"> </a></span> Datum </th> 
<?php if ($settings->get('show_nzbbutton')) { ?>
							<th class='nzb'> NZB </th>
<?php } ?>
<?php if ($settings->get('show_multinzb') && !count($spots) == 0) { ?>
                            <th class='multinzb'> 
                                <form action="" method="GET" id="checkboxget" name="checkboxget">
                                    <input type='hidden' name='page' value='getnzb'>
                                    <input type='checkbox' name='checkall' onclick='checkedAll("checkboxget");'> 
                            </th>
<?php } ?>						
<?php $nzbHandlingTmp = $settings->get('nzbhandling'); if ($nzbHandlingTmp['action'] != 'disable') { ?>
							<th class='sabnzbd'> SAB </th>
<?php } ?>						
						</tr>
					</thead>
                    <tbody id="spots">
<?php
	if (count($spots) == 0) {
		$colSpan = 5;
		$nzbHandlingTmp = $settings->get('nzbhandling'); 
		if ($settings->get('retrieve_comments')) { $colSpan++; }
		if ($settings->get('show_nzbbutton')) { $colSpan++; }
		if ($settings->get('show_multinzb')) { $colSpan++; }
		if ($settings->get('keep_watchlist')) { $colSpan++; }
		if ($nzbHandlingTmp['action'] != 'disable') { $colSpan++; }
		
		echo "\t\t\t\t\t\t\t<tr class='noresults'><td colspan='" . $colSpan . "'>Geen resultaten gevonden</td></tr>\r\n";
	} # if
	
	foreach($spots as $spot) {
		# Format the spot header
		$spot = $tplHelper->formatSpotHeader($spot);

		if ($tplHelper->newSinceLastVisit($spot)) {
			$newSpotClass = 'new';
		} else {
			$newSpotClass = '';
		} # else
		
		if($tplHelper->isModerated($spot)) { 
			$markSpot = '<span class="markSpot">!</span>';
		} else {
			$markSpot = '';
		}
	
		echo "\t\t\t\t\t\t\t";
		echo "<tr class='" . $tplHelper->cat2color($spot) . "'>" . 
			 "<td class='category'><a href='?search[tree]=" . $spot['subcatfilter'] . ",~cat0_z3' title='Ga naar de categorie \"" . $spot['catshortdesc'] . "\"'>" . $spot['catshortdesc'] . "</a></td>" .
			 "<td class='title " . $newSpotClass . "'><a onclick='openSpot(this,\"".$spot['spoturl']."\")' href='".$spot['spoturl']."' title='" . $tplHelper->remove_extensive_dots($spot['title']) . "' class='spotlink'>" . $markSpot . $tplHelper->remove_extensive_dots($spot['title']) . "</a></td>";

		if ($settings->get('keep_watchlist')) {
			echo "<td class='watch'>";
			echo "<a class='remove watchremove_".$spot['id']."' onclick=\"toggleWatchSpot('".$spot['messageid']."','remove',".$spot['id'].")\""; if(!$spot['isbeingwatched']) { echo " style='display: none;'"; } echo " title='Verwijder uit watchlist (w)'> </a>";
			echo "<a class='add watchadd_".$spot['id']."' onclick=\"toggleWatchSpot('".$spot['messageid']."','add',".$spot['id'].")\""; if($spot['isbeingwatched']) { echo " style='display: none;'"; } echo " title='Plaats in watchlist (w)'> </a>";
			echo "</td>";
		}

		if ($settings->get('retrieve_comments')) {
			echo "<td class='comments'><a onclick='openSpot(this,\"".$spot['spoturl']."\")' class='spotlink' href='" . $spot['spoturl'] . "#comments' title='" . $spot['commentcount'] . " comments bij \"" . $spot['title'] . "\"'>" . $spot['commentcount'] . "</a></td>";
		} # if
		
		echo "<td>" . $spot['catdesc'] . "</td>" .
			 "<td><a href='" . $spot['posterurl'] . "' title='Zoek spots van " . $spot['poster'] . "'>" . $spot['poster'] . "</a></td>" .
			 "<td>" . $tplHelper->formatDate($spot['stamp'], 'spotlist') . "</td>";
			 

		# only display the NZB button from 24 nov or later
		if ($spot['stamp'] > 1290578400 ) {
			if ($settings->get('show_nzbbutton')) {
				echo "<td><a href='" . $tplHelper->makeNzbUrl($spot) . "' title ='Download NZB (n)' class='nzb'>NZB";
				
				if ($spot['hasbeendownloaded']) {
					echo '*';
				} # if
				
				echo "</a></td>";
			} # if
			
			if ($settings->get('show_multinzb')) {
				$multispotid = htmlspecialchars($spot['messageid']);
				echo "<td class='multinzb'>";
				echo "<input onclick='multinzb()' type='checkbox' name='".htmlspecialchars('messageid[]')."' value='".$multispotid."'>";
				echo "</td>";
			} # if

			# display the sabnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				if ($spot['hasbeendownloaded']) {
					echo "<td><a onclick=\"downloadSabnzbd('".$spot['id']."','".$spot['sabnzbdurl']."')\" class='sab_".$spot['id']." sabnzbd-button succes' title='Add NZB to SabNZBd queue (you already downloaded this spot) (s)'> </a></td>";
				} else {
					echo "<td><a onclick=\"downloadSabnzbd('".$spot['id']."','".$spot['sabnzbdurl']."')\" class='sab_".$spot['id']." sabnzbd-button' title='Add NZB to SabNZBd queue (s)'> </a></td>";	
				} # else
			} # if
		} else {
			if ($settings->get('show_nzbbutton')) {
				echo "<td> &nbsp; </td>";
			} # if
			
			# display (empty) MultiNZB td
			if ($settings->get('show_multinzb')) {
				echo "<td> &nbsp; </td>";
			}

			# display the sabnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				echo "<td> &nbsp; </td>";
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
                        <td class="prev"><a href="?direction=prev&amp;pagenr=<?php echo $prevPage . $getUrl; ?>">&lt;&lt;</a></td>
<?php }?> 
						<td class="button<?php if ($nextPage <= 0) {echo " last";} ?>"></td>
<?php if ($nextPage > 0) { ?> 
                        <td class="next"><a href="?direction=next&amp;pagenr=<?php echo $nextPage . $getUrl; ?>">&gt;&gt;</a></td>
<?php } ?>
					</tr>
                </tbody>
            </table>
            <?php if ($settings->get('show_multinzb')) { echo "</form>"; } ?>
            <input type="hidden" id="perPage" value="<?php echo $currentSession['user']['prefs']['perpage'] ?>" />
			<input type="hidden" id="nextPage" value="<?php echo $nextPage ?>" />
			<input type="hidden" id="getURL" value="<?php echo $getUrl ?>" />
<?php } ?>
			
		</div>
		<div class="clear"></div>
