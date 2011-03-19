<?php
	# vul wat basis parameters op
	$getUrl = $tplHelper->getQueryParams(); 
	$sortUrl = $tplHelper->getQueryParams(array('sortby', 'sortdir'));
?>    
    <div class="spots">
        <table class="spots watchlist">
            <tr class="head">
                <th class='category'> Cat. </th> 
                <th class='title'> <span class="sortby"><a href="?page=watchlist&sortby=title&sortdir=ASC" title="Sorteren op Titel [0-Z]"><img src='templates_we1rdo/img/arrow_up.png' /></a> <a href="?page=watchlist&sortby=title&sortdir=DESC" title="Sorteren op Titel [Z-0]"><img src='templates_we1rdo/img/arrow_down.png' /></a></span> Titel </th> 
                <?php if ($settings['retrieve_comments']) {
                    echo "<th class='comments'> <a title='Aantal reacties'>#</a> </th>";
                } # if ?>
                <th class='genre'> Genre </th>
                <th class='poster'> <span class="sortby"><a href="?page=watchlist&sortby=poster&sortdir=ASC<?php echo $sortUrl;?>" title="Sorteren op Afzender [0-Z]"><img src='templates_we1rdo/img/arrow_up.png' /></a> <a href="?page=watchlist&sortby=poster&sortdir=DESC<?php echo $sortUrl;?>" title="Sorteren op Afzender [Z-0]"><img src='templates_we1rdo/img/arrow_down.png' /></a></span> Afzender </th> 
                <th class='date'> <span class="sortby"><a href="?page=watchlist&sortby=stamp&sortdir=DESC<?php echo $sortUrl;?>" title="Sorteren op Leeftijd [oplopend]"><img src='templates_we1rdo/img/arrow_up.png' /></a> <a href="?page=watchlist&sortby=stamp&sortdir=ASC<?php echo $sortUrl;?>" title="Sorteren op Leeftijd [aflopend]"><img src='templates_we1rdo/img/arrow_down.png' /></a></span> Datum </th> 
    <?php if ($settings['show_nzbbutton']) { ?>
                <th class='nzb'> NZB </th>
                <th class='multinzb'> 
                    <form action="" method="GET" id="checkboxget" name="checkboxget">
                        <input type='hidden' name='page' value='getnzb'>
                        <input type='checkbox' name='checkall' onclick='checkedAll("checkboxget");'> 
                </th>
    <?php } ?>						
    <?php if ($settings['nzbhandling']['action'] != 'disable') { ?>
                <th class='sabnzbd'> SAB </th> 
    <?php } ?>
                <th class='delete'> DEL </th>
            </tr>
            
    <?php
        foreach($watchlist as $watch) {
            $watch['sabnzbdurl'] = $tplHelper->makeSabnzbdUrl($watch);
            $watch['searchurl'] = $tplHelper->makeSearchUrl($watch);
            
            if($tplHelper->isModerated($watch)) { 
                $markSpot = '<span class="markSpot">!</span>';
            } else {
                $markSpot = '';
            }
            
		echo "<tr class='" . $tplHelper->cat2color($watch) . "'>" . 
			 "<td class='category'><a href='?search[tree]=" . $subcatFilter . "' title='Ga naar de categorie \"" . SpotCategories::Cat2ShortDesc($watch['category'], $watch['subcata']) . "\"'>" . SpotCategories::Cat2ShortDesc($watch['category'], $watch['subcata']) . "</a></td>" .
			 "<td class='title " . $newSpotClass . "'><a href='" . $tplHelper->makeSpotUrl($watch) . "' title='" . $watch['title'] . "' class='spotlink'>" . $watch['title'] . $markSpot . "</a></td>";
		
		if ($settings['retrieve_comments']) {
			echo "<td class='comments'><a href='" . $tplHelper->makeSpotUrl($watch) . "#comments' title='" . $tplHelper->getCommentCount($watch) . " comments bij \"" . $watch['title'] . "\"' class='spotlink'>" . $tplHelper->getCommentCount($watch) . "</a></td>";
		} # if
		
		echo "<td>" . SpotCategories::Cat2Desc($watch['category'], $watch['subcat' . SpotCategories::SubcatNumberFromHeadcat($watch['category'])]) . "</td>" .
			 "<td>" . $watch['poster'] . "</td>" .
			 "<td>" . $tplHelper->formatDate($watch['stamp'], 'spotlist') . "</td>";
			 

		# only display the NZB button from 24 nov or later
		if ($watch['stamp'] > 1290578400 ) {
			if ($settings['show_nzbbutton']) {
				echo "<td><a href='" . $tplHelper->makeNzbUrl($watch) . "' title ='Download NZB' class='nzb'>NZB";
				
				if ($tplHelper->hasBeenDownloaded($watch)) {
					echo '*';
				} # if
				
				echo "</a></td>";
				
				$multispotid = htmlspecialchars($watch['messageid']);
				echo "<td>";
				echo "<input type='checkbox' name='".htmlspecialchars('messageid[]')."' value='".$multispotid."'>";
				echo "</td>";
			} # if

			# display the sabnzbd button
			if (!empty($watch['sabnzbdurl'])) {
				if ($tplHelper->hasBeenDownloaded($watch)) {
					echo "<td><a class='sabnzbd-button' target='_blank' href='" . $watch['sabnzbdurl'] . "' title='Add NZB to SabNZBd queue (you allready downloaded this spot)'><img width='20' height='17' class='sabnzbd-button' src='templates_we1rdo/img/succes.png'></a></td>";
				} else {
					echo "<td><a class='sabnzbd-button' target='_blank' href='" . $watch['sabnzbdurl'] . "' title='Add NZB to SabNZBd queue'><img width='20' height='17' class='sabnzbd-button' src='templates_we1rdo/img/download.png'></a></td>";	
				} # else
			} # if
		} else {
			if ($settings['show_nzbbutton']) {
				echo "<td> &nbsp; </td>";
			} # if

			# display the sabnzbd button
			if (!empty($watch['sabnzbdurl'])) {
				echo "<td> &nbsp; </td>";
			} # if
		} # else
                 
            echo "<td><a href='?page=watchlist&amp;action=remove&messageid=" . $watch['messageid'] . "'>X</a></td>";
        } # foreach
    ?>
    
        </table>
        <table class="footer">
            <tbody>
                <tr>
                    <td class="prev"><?php if ($prevPage >= 0) { ?> <a href="?direction=prev&amp;pagenr=<?php echo $prevPage . $getUrl;?>">< Vorige</a><?php }?></td>
                    <td class="next"><?php if ($nextPage > 0) { ?> <a href="?direction=next&amp;pagenr=<?php echo $nextPage . $getUrl;?>">Volgende ></a><?php }?></td>
<?php if ($settings['show_nzbbutton']) { ?>
                    <td class="button">  
                        <input id='multisubmit' type='submit' value='Multi NZB' title='Download Multi NZB' />
                    </td>
                    </form>
<?php } ?>
                </tr>
            </tbody>
        </table>
        
	</div>