<?php $getUrl = $tplHelper->getFilterParams(); ?>
			<div class="spots">
				<table class="spots">
					<tbody>
					<tr class="head"> 
						<th class='category'> <a href="?page=index&sortby=category">Cat.</a> </th> 
						<th class='title'> <a href="?page=index&sortby=title">Titel</a> </th> 
						<th class='genre'> Genre </th> 
						<th class='poster'> <a href="?page=index&sortby=poster">Afzender</a> </th> 
						<th class='date'> <a href="?page=index&sortby=stamp">Datum</a> </th> 
<?php if ($settings['show_nzbbutton']) { ?>
						<th class='nzb'> NZB </th> 
<?php } ?>						
<?php if (isset($settings['sabnzbd']['apikey'])) { ?>
						<th class='sabnzbd'> SAB </th> 
<?php } ?>						
					</tr>

<?php
	$count = 0;
	foreach($spots as $spot) {
		# fix the sabnzbdurl en searchurl
		$spot['sabnzbdurl'] = $tplHelper->makeSabnzbdUrl($spot);
		$spot['searchurl'] = $tplHelper->makeSearchUrl($spot);
	
		$count++;

		echo "\t\t\t\t\t\t\t";
		echo "<tr class=' " . $tplHelper->cat2color($spot) . ' ' . ($count % 2 ? "even" : "odd") . "' >" . 
			 "<td class='category'>" . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']) . "</td>" .
			 "<td class='title'><a href='?page=getspot&amp;messageid=" . $spot['messageid'] . "' class='spotlink'>" . $spot['title'] . "</a></td>" .
			 "<td>" . SpotCategories::Cat2Desc($spot['category'], $spot['subcat' . SpotCategories::SubcatNumberFromHeadcat($spot['category'])]) . "</td>" .
			 "<td>" . $spot['poster'] . "</td>" .
			 "<td>" . strftime("%a, %d-%b-%Y (%H:%M)", $spot['stamp']) . "</td>";
			 

		# only display the NZB button from 24 nov or later
		if ($spot['stamp'] > 1290578400 ) {
			if ($settings['show_nzbbutton']) {
				echo "<td><a href='?page=getnzb&amp;messageid=" . $spot['messageid'] . "' class='nzb'>NZB";
				
				if ($tplHelper->hasBeenDownloaded($spot)) {
					echo '*';
				} # if
				
				echo "</a></td>";
			} # if

			# display the sabnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				echo "<td><a target='_blank' href='" . $spot['sabnzbdurl'] . "' ><img height='16 width='16' class='sabnzbd-button' src='images/download-small.png'></a></td>";
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
						<td colspan="4" style='text-align: left;'><?php if ($prevPage >= 0) { ?> <a href="?direction=prev&amp;page=<?php echo $prevPage . $getUrl;?>">< Vorige</a><?php }?></td>
						<td colspan="4" style='text-align: right;'><?php if ($nextPage > 0) { ?> <a href="?direction=next&amp;page=<?php echo $nextPage . $getUrl;?>">Volgende ></a><?php }?></td>
					</tr>

				</tbody>
			</table>
			
		</div>

		<div class="clear"></div>
		