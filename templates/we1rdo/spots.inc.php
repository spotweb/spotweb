<?php 
	$getUrl = $tplHelper->getQueryParams(); 
?>
			<div class="spots">
				<table class="spots">
					<tbody>
					<tr class="head">
						<th class='category'> <a href="<?php echo $tplHelper->makeSortUrl('index', 'category', ''); ?>" title="Sorteren op Categorie">Cat.</a> </th> 
						<th class='title'> <span class="sortby"><a href="<?php echo $tplHelper->makeSortUrl('index', 'title', 'ASC'); ?>" title="Sorteren op Titel [0-Z]"><img src='templates/we1rdo/img/arrow_up.png' alt='Sorteren op Titel [0-Z]' /></a> <a href="<?php echo $tplHelper->makeSortUrl('index', 'title', 'DESC'); ?>" title="Sorteren op Titel [Z-0]"><img src='templates/we1rdo/img/arrow_down.png' alt='Sorteren op Titel [Z-0]' /></a></span> Titel </th> 
                        <?php if ($settings['keep_watchlist']) { ?>
						<th class='watch'> </th>
						<?php }
						if ($settings['retrieve_comments']) {
                        	echo "<th class='comments'> <a title='Aantal reacties'>#</a> </th>";
						} # if ?>
						<th class='genre'> Genre </th> 
                        <th class='poster'> <span class="sortby"><a href="<?php echo $tplHelper->makeSortUrl('index', 'poster', 'ASC'); ?>" title="Sorteren op Afzender [0-Z]"><img src='templates/we1rdo/img/arrow_up.png' alt='Sorteren op Afzender [0-Z]' /></a> <a href="<?php echo $tplHelper->makeSortUrl('index', 'poster', 'DESC'); ?>" title="Sorteren op Afzender [Z-0]"><img src='templates/we1rdo/img/arrow_down.png' alt='Sorteren op Afzender [Z-0]' /></a></span> Afzender </th> 
						<th class='date'> <span class="sortby"><a href="<?php echo $tplHelper->makeSortUrl('index', 'stamp', 'DESC'); ?>" title="Sorteren op Leeftijd [oplopend]"><img src='templates/we1rdo/img/arrow_up.png' alt='Sorteren op Leeftijd [oplopend]' /></a> <a href="<?php echo $tplHelper->makeSortUrl('index', 'stamp', 'ASC'); ?>" title="Sorteren op Leeftijd [aflopend]"><img src='templates/we1rdo/img/arrow_down.png' alt='Sorteren op Leeftijd [aflopend]' /></a></span> Datum </th> 
<?php if ($settings['show_nzbbutton']) { ?>
						<th class='nzb'> NZB </th>
<?php } ?>
<?php if ($settings['show_multinzb']) { ?>
                        <th class='multinzb'> 
                        	<form action="" method="GET" id="checkboxget" name="checkboxget">
                            	<input type='hidden' name='page' value='getnzb'>
                                <input type='checkbox' name='checkall' onclick='checkedAll("checkboxget");'> 
                        </th>
<?php } ?>						
<?php if ($settings['nzbhandling']['action'] != 'disable') { ?>
						<th class='sabnzbd'> SAB </th> 
<?php } ?>						
					</tr>

<?php
	$count = 0;
	
	if (count($spots) == 0) {
		$colSpan = 5;
		if ($settings['retrieve_comments']) { $colSpan++; }
		if ($settings['show_nzbbutton']) { $colSpan++; }
		if ($settings['show_multinzb']) { $colSpan++; }
		if ($settings['keep_watchlist']) { $colSpan++; }
		if ($settings['nzbhandling']['action'] != 'disable') { $colSpan++; }
		
		echo "\t\t\t\t\t\t\t<tr><td class='noresults' colspan='" . $colSpan . "'>Geen resultaten gevonden</td></tr>\r\n";
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
	
		$subcatFilter = SpotCategories::SubcatToFilter($spot['category'], $spot['subcata']);
		
		$count++;

		echo "\t\t\t\t\t\t\t";
		echo "<tr class='" . $tplHelper->cat2color($spot) . ' ' . ($count % 2 ? "even" : "odd") . "'>" . 
			 "<td class='category'><a href='?search[tree]=" . $subcatFilter . "' title='Ga naar de categorie \"" . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']) . "\"'>" . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']) . "</a></td>" .
			 "<td class='title " . $newSpotClass . "'><a href='" . $tplHelper->makeSpotUrl($spot) . "' title='" . $spot['title'] . "' class='spotlink'>" . $spot['title'] . $markSpot . "</a></td>";

		if ($settings['keep_watchlist']) {
			echo "<td class='watch'>";
			echo "<a onclick=\"toggleWatchSpot('".$spot['messageid']."','remove',".$spot['id'].")\""; if($tplHelper->isBeingWatched($spot) == false) { echo " style='display: none;'"; } echo " id='watchremove_".$spot['id']."'><img src='templates/we1rdo/img/fav.png' alt='Verwijder uit watchlist' title='Verwijder uit watchlist'/></a>";
			echo "<a onclick=\"toggleWatchSpot('".$spot['messageid']."','add',".$spot['id'].")\""; if($tplHelper->isBeingWatched($spot) == true) { echo " style='display: none;'"; } echo " id='watchadd_".$spot['id']."'><img src='templates/we1rdo/img/fav_light.png' alt='Plaats in watchlist' title='Plaats in watchlist' /></a>";
			echo "</td>";
		}

		if ($settings['retrieve_comments']) {
			echo "<td class='comments'><a href='" . $tplHelper->makeSpotUrl($spot) . "#comments' title='" . $tplHelper->getCommentCount($spot) . " comments bij \"" . $spot['title'] . "\"' class='spotlink'>" . $tplHelper->getCommentCount($spot) . "</a></td>";
		} # if
		
		echo "<td>" . SpotCategories::Cat2Desc($spot['category'], $spot['subcat' . SpotCategories::SubcatNumberFromHeadcat($spot['category'])]) . "</td>" .
			 "<td><a href='" . $tplHelper->makePosterUrl($spot) . "' title='Zoek spots van " . $spot['poster'] . "'>" . $spot['poster'] . "</a></td>" .
			 "<td>" . $tplHelper->formatDate($spot['stamp'], 'spotlist') . "</td>";
			 

		# only display the NZB button from 24 nov or later
		if ($spot['stamp'] > 1290578400 ) {
			if ($settings['show_nzbbutton']) {
				echo "<td><a href='" . $tplHelper->makeNzbUrl($spot) . "' title ='Download NZB' class='nzb'>NZB";
				
				if ($tplHelper->hasBeenDownloaded($spot)) {
					echo '*';
				} # if
				
				echo "</a></td>";
			} # if
			
			if ($settings['show_multinzb']) {
				$multispotid = htmlspecialchars($spot['messageid']);
				echo "<td>";
				echo "<input type='checkbox' name='".htmlspecialchars('messageid[]')."' value='".$multispotid."'>";
				echo "</td>";
			} # if

			# display the sabnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				if ($tplHelper->hasBeenDownloaded($spot)) {
					echo "<td><a class='sabnzbd-button' target='_blank' href='" . $spot['sabnzbdurl'] . "' title='Add NZB to SabNZBd queue (you allready downloaded this spot)'><img width='20' height='17' class='sabnzbd-button' src='templates/we1rdo/img/succes.png' alt='Add NZB to SabNZBd queue (you allready downloaded this spot)'></a></td>";
				} else {
					echo "<td><a class='sabnzbd-button' target='_blank' href='" . $spot['sabnzbdurl'] . "' title='Add NZB to SabNZBd queue'><img width='20' height='17' class='sabnzbd-button' src='templates/we1rdo/img/download.png' alt='Add NZB to SabNZBd queue'></a></td>";	
				} # else
			} # if
		} else {
			if ($settings['show_nzbbutton']) {
				echo "<td> &nbsp; </td>";
			} # if
			
			# display (empty) MultiNZB td
			if ($settings['show_multinzb']) {
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
<?php if ($settings['show_multinzb'] || $prevPage >= 0 || $nextPage > 0) { ?>
			<table class="footer">
            	<tbody>
                	<tr>
<?php if ($prevPage >= 0) { ?> 
                        <td class="prev"><a href="?direction=prev&amp;pagenr=<?php echo $prevPage . $getUrl;?>">&lt;&lt;</a></td>
<?php }?> 
						<td class="button">
<?php if ($settings['show_multinzb']) { ?> 
                            <input id='multisubmit' type='submit' value='' title='Download Multi NZB' />
                        </form>
<?php } ?>
						</td>
<?php if ($nextPage > 0) { ?> 
                        <td class="next"><a href="?direction=next&amp;pagenr=<?php echo $nextPage . $getUrl;?>">&gt;&gt;</a></td>
<?php } ?>
					</tr>
                </tbody>
            </table>
<?php } ?>
			
		</div>

		<div class="clear"></div>
		
