    <div class="spots watchlist">
        <table class="spots">
            <tr class="head">
                <th class='category'> Cat. </th> 
                <th class='title'> <span class="sortby"><a href="<?php echo $tplHelper->makeSortUrl('watchlist', 'title', 'ASC'); ?>" title="Sorteren op Titel [0-Z]"><img src='templates_we1rdo/img/arrow_up.png' alt='Sorteren op Titel [0-Z]' /></a> <a href="<?php echo $tplHelper->makeSortUrl('watchlist', 'title', 'DESC'); ?>" title="Sorteren op Titel [Z-0]"><img src='templates_we1rdo/img/arrow_down.png' alt='Sorteren op Titel [Z-0]' /></a></span> Titel </th> 
				<th class='watch'> </th>
				<?php if ($settings['retrieve_comments']) {
                    echo "<th class='comments'> <a title='Aantal reacties'>#</a> </th>";
                } # if ?>
                <th class='genre'> Genre </th>
                <th class='poster'> <span class="sortby"><a href="<?php echo $tplHelper->makeSortUrl('watchlist', 'poster', 'ASC'); ?>" title="Sorteren op Afzender [0-Z]"><img src='templates_we1rdo/img/arrow_up.png' alt='Sorteren op Afzender [0-Z]' /></a> <a href="<?php echo $tplHelper->makeSortUrl('watchlist', 'poster', 'DESC'); ?>" title="Sorteren op Afzender [Z-0]"><img src='templates_we1rdo/img/arrow_down.png' alt='Sorteren op Afzender [Z-0]' /></a></span> Afzender </th> 
                <th class='date'> <span class="sortby"><a href="<?php echo $tplHelper->makeSortUrl('watchlist', 'stamp', 'DESC'); ?>" title="Sorteren op Leeftijd [oplopend]"><img src='templates_we1rdo/img/arrow_up.png' alt='Sorteren op Leeftijd [oplopend]' /></a> <a href="<?php echo $tplHelper->makeSortUrl('watchlist', 'stamp', 'ASC'); ?>" title="Sorteren op Leeftijd [aflopend]"><img src='templates_we1rdo/img/arrow_down.png' alt='Sorteren op Leeftijd [aflopend]' /></a></span> Datum </th> 
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
        foreach($watchlist as $watch) {
			# Format the spot header
			$watch = $tplHelper->formatSpotHeader($watch);
            
			if ($tplHelper->newSinceLastVisit($watch)) {
				$newSpotClass = 'new';
			} else {
				$newSpotClass = '';
			} # else
			
            if($tplHelper->isModerated($watch)) { 
                $markSpot = '<span class="markSpot">!</span>';
            } else {
                $markSpot = '';
            }
			
			$subcatFilter =  SpotCategories::SubcatToFilter($watch['category'], $watch['subcata']);
            
			echo "<tr class='" . $tplHelper->cat2color($watch) . "'>" . 
				 "<td class='category'><a href='?search[tree]=" . $subcatFilter . "' title='Ga naar de categorie \"" . SpotCategories::Cat2ShortDesc($watch['category'], $watch['subcata']) . "\"'>" . SpotCategories::Cat2ShortDesc($watch['category'], $watch['subcata']) . "</a></td>" .
				 "<td class='title " . $newSpotClass . "'><a href='" . $tplHelper->makeSpotUrl($watch) . "' title='" . $watch['title'] . "' class='spotlink'>" . $watch['title'] . $markSpot . "</a></td>";
			
			echo "<td class='watch'>";
			echo "\t<a href='?page=watchlist&amp;action=remove&messageid=" . $watch['messageid'] . "'><img src='templates_we1rdo/img/fav.png' alt='Verwijder uit watchlist' title='Verwijder uit watchlist'/></a>";
			echo "</td>";
			
			if ($settings['retrieve_comments']) {
				echo "<td class='comments'><a href='" . $tplHelper->makeSpotUrl($watch) . "#comments' title='" . $tplHelper->getCommentCount($watch) . " comments bij \"" . $watch['title'] . "\"' class='spotlink'>" . $tplHelper->getCommentCount($watch) . "</a></td>";
			} # if
			
			echo "<td>" . SpotCategories::Cat2Desc($watch['category'], $watch['subcat' . SpotCategories::SubcatNumberFromHeadcat($watch['category'])]) . "</td>" .
				 "<td><a href='" . $tplHelper->makePosterUrl($watch) . "' title='Zoek spots van " . $watch['poster'] . "'>" . $watch['poster'] . "</a></td>" .
				 "<td>" . $tplHelper->formatDate($watch['stamp'], 'spotlist') . "</td>";
				 
	
			# only display the NZB button from 24 nov or later
			if ($watch['stamp'] > 1290578400 ) {
				if ($settings['show_nzbbutton']) {
					echo "<td><a href='" . $tplHelper->makeNzbUrl($watch) . "' title ='Download NZB' class='nzb'>NZB";
					
					if ($tplHelper->hasBeenDownloaded($watch)) {
						echo '*';
					} # if
					
					echo "</a></td>";
				} # if
	
				if ($settings['show_multinzb']) {
					$multispotid = htmlspecialchars($watch['messageid']);
					echo "<td>";
					echo "<input type='checkbox' name='".htmlspecialchars('messageid[]')."' value='".$multispotid."'>";
					echo "</td>";
				}
	
				# display the sabnzbd button
				if (!empty($watch['sabnzbdurl'])) {
					if ($tplHelper->hasBeenDownloaded($watch)) {
						echo "<td><a class='sabnzbd-button' target='_blank' href='" . $watch['sabnzbdurl'] . "' title='Add NZB to SabNZBd queue (you already downloaded this spot)'><img width='20' height='17' class='sabnzbd-button' src='templates_we1rdo/img/succes.png' alt='Add NZB to SabNZBd queue (you already downloaded this spot)'></a></td>";
					} else {
						echo "<td><a class='sabnzbd-button' target='_blank' href='" . $watch['sabnzbdurl'] . "' title='Add NZB to SabNZBd queue'><img width='20' height='17' class='sabnzbd-button' src='templates_we1rdo/img/download.png' alt='Add NZB to SabNZBd queue'></a></td>";	
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
				if (!empty($watch['sabnzbdurl'])) {
					echo "<td> &nbsp; </td>";
				} # if
			} # else
                 
        } # foreach
    ?>
    
        </table>
<?php if ($settings['show_multinzb']) { ?>
        <table class="footer">
            <tbody>
                <tr>
                    <td class="button">  
                        <input id='multisubmit' type='submit' value='' title='Download Multi NZB' />
                    </td>
                    </form>
                </tr>
            </tbody>
        </table>
<?php } ?>

	</div>