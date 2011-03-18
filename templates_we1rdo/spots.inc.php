<?php 
	$getUrl = $tplHelper->getQueryParams(); 
	$sortUrl = $tplHelper->getQueryParams(array('sortby', 'sortdir'));
?>
			<div class="spots">
				<table class="spots">
					<tbody>
					<tr class="head"> 
						<th class='category'> <a href="?page=index&sortby=category<?php echo $sortUrl;?>" title="Sorteren op Categorie">Cat.</a> </th> 
						<th class='title'> <span class="sortby"><a href="?page=index&sortby=title&sortdir=ASC<?php echo $sortUrl;?>" title="Sorteren op Titel [0-Z]"><img src='templates_we1rdo/img/arrow_up.png' /></a> <a href="?page=index&sortby=title&sortdir=DESC<?php echo $sortUrl;?>" title="Sorteren op Titel [Z-0]"><img src='templates_we1rdo/img/arrow_down.png' /></a></span> Titel </th> 
                        <?php if ($settings['retrieve_comments']) {
                        	echo "<th class='comments'> <a title='Aantal reacties'>#</a> </th>";
						} # if ?>
						<th class='genre'> Genre </th> 
                        <th class='poster'> <span class="sortby"><a href="?page=index&sortby=poster&sortdir=ASC<?php echo $sortUrl;?>" title="Sorteren op Afzender [0-Z]"><img src='templates_we1rdo/img/arrow_up.png' /></a> <a href="?page=index&sortby=poster&sortdir=DESC<?php echo $sortUrl;?>" title="Sorteren op Afzender [Z-0]"><img src='templates_we1rdo/img/arrow_down.png' /></a></span> Afzender </th> 
						<th class='date'> <span class="sortby"><a href="?page=index&sortby=stamp&sortdir=DESC<?php echo $sortUrl;?>" title="Sorteren op Leeftijd [oplopend]"><img src='templates_we1rdo/img/arrow_up.png' /></a> <a href="?page=index&sortby=stamp&sortdir=ASC<?php echo $sortUrl;?>" title="Sorteren op Leeftijd [aflopend]"><img src='templates_we1rdo/img/arrow_down.png' /></a></span> Datum </th> 
<?php if ($settings['show_nzbbutton']) { ?>
						<!-- Multi Download start -->
						<th class='nzb'> NZB </th><th class='multinzb'><form action='' method="GET" id="checkboxget" name="checkboxget"><input type="hidden" name="page" value="getnzb"><input type='checkbox' name='checkall' onclick='checkedAll("checkboxget");'></th> 						
						<!-- Multi Download einde -->
<?php } ?>						
<?php if ($settings['nzbhandling']['action'] != 'disable') { ?>
						<th class='sabnzbd'> SAB </th> 
<?php } ?>						
					</tr>

<?php
	$count = 0;
	foreach($spots as $spot) {
		# fix the sabnzbdurl en searchurl
		$spot['sabnzbdurl'] = $tplHelper->makeSabnzbdUrl($spot);
		$spot['searchurl'] = $tplHelper->makeSearchUrl($spot);
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

		$subcatFilter =  SpotCategories::SubcatToFilter($spot['category'], $spot['subcata']);
		
		$count++;
		

		echo "\t\t\t\t\t\t\t";
		echo "<tr class='" . $tplHelper->cat2color($spot) . ' ' . ($count % 2 ? "even" : "odd") . "'>" . 
			 "<td class='category'><a href='?search[tree]=" . $subcatFilter . "' title='Ga naar de categorie \"" . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']) . "\"'>" . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']) . "</a></td>" .
			 "<td class='title " . $newSpotClass . "'><a href='?page=getspot&amp;messageid=" . $spot['messageid'] . "' title='" . $spot['title'] . "' class='spotlink'>" . $spot['title'] . $markSpot . "</a></td>";
		
		if ($settings['retrieve_comments']) {
			echo "<td class='comments'><a href='?page=getspot&amp;messageid=" . $spot['messageid'] . "#comments' title='" . $tplHelper->getCommentCount($spot) . " comments bij \"" . $spot['title'] . "\"' class='spotlink'>" . $tplHelper->getCommentCount($spot) . "</a></td>";
		} # if
		
		echo "<td>" . SpotCategories::Cat2Desc($spot['category'], $spot['subcat' . SpotCategories::SubcatNumberFromHeadcat($spot['category'])]) . "</td>" .
			 "<td>" . $spot['poster'] . "</td>" .
			 "<td>" . $tplHelper->formatDate($spot['stamp'], 'spotlist') . "</td>";
			 

		# only display the NZB button from 24 nov or later
		if ($spot['stamp'] > 1290578400 ) {
			if ($settings['show_nzbbutton']) {
				echo "<td><a href='?page=getnzb&amp;messageid=" . $spot['messageid'] . "' title ='Download NZB' class='nzb'>NZB";
				
				if ($tplHelper->hasBeenDownloaded($spot)) {
					echo '*';
				} # if
				
				echo "</a></td>";
				# Multi Download start #
					$multispotid = htmlspecialchars($spot['messageid']);
					echo "<td>";
					echo "<input type=checkbox name='".htmlspecialchars('messageid[]')."' value='".$multispotid."'>";
					echo "</td>";
				# Multi Download einde #
			} # if

			# display the sabnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				if ($tplHelper->hasBeenDownloaded($spot)) {
					echo "<td><a class='sabnzbd-button' target='_blank' href='" . $spot['sabnzbdurl'] . "' title='Add NZB to SabNZBd queue (you allready downloaded this spot)'><img height='16' width='16' class='sabnzbd-button' src='templates_we1rdo/img/succes.png'></a></td>";
				} else {
					echo "<td><a class='sabnzbd-button' target='_blank' href='" . $spot['sabnzbdurl'] . "' title='Add NZB to SabNZBd queue'><img height='16' width='16' class='sabnzbd-button' src='images/download-small.png'></a></td>";	
				} # else
			} # if
		} else {
			if ($settings['show_nzbbutton']) {
				echo "<td> &nbsp; </td>";
			} # if

			# display the sabnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				echo "<td> &nbsp; </td>";
			} # if
		} # else
		
		
		echo "</tr>\r\n";
	}
?>
					<!-- Multi Download start -->
					<tr class="nav">
						<td colspan="9" style='text-align: right;' valign=middle>
							<input id='multisubmit' type='image' value='submit' src='images/buttonmulti.png' border='0' title='Download Multi NZB'></form>
						</td>
					</tr>
					<!-- Multi Download Einde -->
					<tr class="nav">
						<td colspan="4" style='text-align: left;'><?php if ($prevPage >= 0) { ?> <a href="?direction=prev&amp;pagenr=<?php echo $prevPage . $getUrl;?>">< Vorige</a><?php }?></td>
						<!-- COLSPAN +1 voor multi spots nzb download -->
						<td colspan="5" style='text-align: right;'><?php if ($nextPage > 0) { ?> <a href="?direction=next&amp;pagenr=<?php echo $nextPage . $getUrl;?>">Volgende ></a><?php }?></td>
					</tr>

				</tbody>
			</table>
			
		</div>

		<div class="clear"></div>
		
