<?php
	# Converteer filter parameters naar queries
	$getUrl = '';
	foreach($activefilter as $val => $key) {
		$getUrl .= '&amp;search[' .  $val . ']=' . urlencode($key);
	} # foreach
?>

			<div class="spotscontainer">
				<table class="spots">
					<tr> 
						<th> Formaat </th> 
						<th> <a href="?page=index&sortby=category">Cat.</a> </th> 
						<th> <a href="?page=index&sortby=title">Titel</a> </th> 
						<th> Genre </th> 
						<th> <a href="?page=index&sortby=poster">Afzender</a> </th> 
						<th> <a href="?page=index&sortby=stamp">Datum</a> </th> 
<?php if ($settings['show_nzbbutton']) { ?>
						<th> Dnl. </th> 
<?php } ?>						
<?php if ($settings['nzbhandling']['action'] != 'disable') { ?>
						<th> sabnzbd </th> 
<?php } ?>						
					</tr>
			
<?php
	$count = 0;
	foreach($spots as $spot) {
		# fix the sabnzbdurl en searchurl
		$spot['sabnzbdurl'] = $tplHelper->makeSabnzbdUrl($spot);
		$spot['searchurl'] = $tplHelper->makeSearchUrl($spot);
	
		$count++;

		echo "\t\t\t\t\t";
		echo "<tr class='" . ($count % 2 ? "even" : "odd") . "' >" . 
			 "<td>" . SpotCategories::Cat2Desc($spot['category'], $spot['subcata']) . "</td>" .
			 "<td>" . SpotCategories::HeadCat2Desc($spot['category']) . "</td>" .
			 "<td><a href='?page=getspot&amp;messageid=" . $spot['messageid'] . "' class='spotlink'>" . $spot['title'] . "</a></td>" .
			 "<td>" . SpotCategories::Cat2Desc($spot['category'], $spot['subcat' . SpotCategories::SubcatNumberFromHeadcat($spot['category'])]) . "</td>" .
			 "<td>" . $spot['poster'] . "</td>" .
			 "<td>" . strftime("%a, %d-%b-%Y (%H:%M)", $spot['stamp']) . "</td>";
			 

		# only display the NZB button from 24 nov or later
		if ($spot['stamp'] > 1290578400 ) {
			if ($settings['show_nzbbutton']) {
				echo "<td><a href='?page=getnzb&amp;messageid=" . $spot['messageid'] . "'>NZB</a>";
				
				if ($tplHelper->hasBeenDownloaded($spot)) {
					echo '*';
				} # if
				
				echo "</td>";
			} # if

			# display the sabnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				echo "<td><a target='_blank' href='" . $spot['sabnzbdurl'] . "' ><img height='16 widt='16'  class='sabnzbd-button' src='images/download-small.png'></a></td>";
			} # if
		} else {
			if ($settings['show_nzbbutton']) {
				echo "<td> &nbsp; </td>";
			} # if

			# display the sabnzbd button
			if (isset($settings['sabnzbd'])) {
				echo "<td> &nbsp; </td>";
			} # if
		} # else
		
		
		echo "</tr>\r\n";
	}
?>

			<tr>
				<td colspan="4" style='text-align: left;'><?php if ($prevPage >= 0) { ?> <a href="?direction=prev&amp;page=<?php echo $prevPage . $getUrl;?>">Vorige</a><?php }?></td>
				<td colspan="4" style='text-align: right;'><?php if ($nextPage > 0) { ?> <a href="?direction=next&amp;page=<?php echo $nextPage . $getUrl;?>">Volgende</a><?php }?></td>
			</tr>

			
			</table>
			
		</div>
