<?php $getUrl = $tplHelper->getQueryParams(); ?>			
			
			<div class="" id="spots">
			
<?php if(!empty($_GET['ajax'])) { ?>

        <script type='text/javascript'>
		$(function(){
			$("a.spotlink").fancybox({
				'width'			: '80%',
				'height' 		: '94%',
				'autoScale' 	: false,
				'transitionIn'	: 'none',
				'transitionOut'	: 'none',
				'type'			: 'iframe'
			})
			
			$('#spot_table').width($('html').width()-min_width);
			
			$("a.sabnzbd-button").click(function(e) {
				e.preventDefault();

				var surl = this.href.split("?");
				var temp = $(this);
			
				$.ajax({
					url: surl[0],
					data: surl[1],
					context: $(temp),
					error: function(jqXHR, textStatus, errorThrown) {
						// zie bij success(): alert(textStatus);
					},
					success: function(data, textStatus, jqXHR) {
						// We kunnen de returncode niet checken want cross-site
						// scripting is niet toegestaan, dus krijgen we de inhoud 
						// niet te zien
					},
					beforeSend: function(jqXHR, settings) {
						$(temp).html("<img class='sabnzbd-button' src='templates_splendid/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						setTimeout( function() { $(temp).html("<img class='sabnzbd-button' src='templates_splendid/img/succes.png' />") }, 1000);
					}, // # complete
					dataType: "text"
				});
			});
		});
		
		$(window).resize(function() {
			$('#spot_table').width($('html').width()-min_width);
		});
		</script>

				<table class="spots" id="spot_table" border="0" cellpadding="0">
					<tbody>
					<tr class="head"> 
						<th class='category'> <a href="?page=index&sortby=category<?php echo $getUrl;?>" title="Sorteren op Categorie">Cat.</a> </th> 
						<th class='title'> <a href="?page=index&sortby=title<?php echo $getUrl;?>" title="Sorteren op Titel">Titel</a> </th> 
<?php if($settings['retrieve_comments']) { ?>
                        <th class='comments'> </th>
<?php } ?>
						<th class='genre'> Genre </th> 
						<th class='poster'> <a href="?page=index&sortby=poster<?php echo $getUrl;?>" title="Sorteren op Afzender">Afzender</a> </th> 
						<th class='date'> <a href="?page=index&sortby=stamp<?php echo $getUrl;?>" title="Sorteren op Datum">Datum</a> </th> 
<?php if ($settings['show_nzbbutton']) { ?>
						<th class='nzb'> NZB </th> 
<?php } ?>						
<?php if ($settings['nzbhandling']['action'] != 'disable') { ?>
						<th class='sabnzbd'> SAB </th> 
<?php } ?>						
						<th class='watch'></th>
					</tr>

<?php
	$count = 0;
	foreach($spots as $spot) {
		# Format the spot header
		$spot = $tplHelper->formatSpotHeader($spot);
		if ($tplHelper->newSinceLastVisit($spot)) {
			$newSpotClass = 'new';
		} else {
			$newSpotClass = '';
		} # else

		$subcatFilter =  SpotCategories::SubcatToFilter($spot['category'], $spot['subcata']);
		
		$count++;

		echo "\t\t\t\t\t\t\t";
		echo "<tr class='" . $tplHelper->cat2color($spot) . ' ' . ($count % 2 ? "even" : "odd") . $spot['subcata'].$spot['subcatb'].$spot['subcatc'].$spot['subcatd']."'>" . 
			 "<td class='category'><a href='?search[tree]=" . $subcatFilter . "' title='Ga naar de categorie \"" . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']) . "\"'>" . SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']) . "</a></td>" .
			 "<td class='title " . $newSpotClass . "'><a href='?page=getspot&amp;messageid=" . $spot['messageid'] . "' title='" . utf8_encode($spot['title']) . "' class='spotlink'>" . utf8_encode($spot['title']) . "</a></td>";
        
        if($settings['retrieve_comments']) echo "<td class='comments'><a href='?page=getspot&amp;messageid=" . $spot['messageid'] . "#comments' title='" . $tplHelper->getCommentCount($spot) . " comments bij \"" . $spot['title'] . "\"' class='spotlink'>" . $tplHelper->getCommentCount($spot) . "</a></td>";
        
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
			} # if

			# display the sabnzbd button
			if (!empty($spot['sabnzbdurl'])) {
				//echo "<td><a target='_blank' href='" . $spot['sabnzbdurl'] . "' title='Voeg spot toe aan SabNZBd+ queue'><img height='16' width='16' class='sabnzbd-button' src='images/download-small.png'></a></td>";
				echo "<td><a class='sabnzbd-button' target='_blank' href='" . $spot['sabnzbdurl'] . "' title='Add NZB to SabNZBd queue'><img height='16' width='16' class='sabnzbd-button' src='images/download-small.png'></a></td>";
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
		
		echo "<td>\n";
		
		if($tplHelper->isBeingWatched($spot)) { ?>
			<a onclick="removeWatchSpot('<?php echo $spot['messageid'].'\','.$spot['id'] ?>)" id="watched_<?php echo $spot['id'] ?>"><img src="templates_splendid/img/watch_active.png" alt="Verwijder uit watchlist" title="Verwijder uit watchlist" border="0" /></a>
			<a onclick="addWatchSpot('<?php echo $spot['messageid'].'\','.$spot['id'] ?>);" style="display: none" id="watch_<?php echo $spot['id'] ?>"><img src="templates_splendid/img/watch.png" alt="Plaats in watchlist" title="Plaats in watchlist" border="0" /></a>
		<?php } else { ?>
			<a onclick="removeWatchSpot('<?php echo $spot['messageid'].'\','.$spot['id'] ?>)" style="display: none" id="watched_<?php echo $spot['id'] ?>"><img src="templates_splendid/img/watch_active.png" alt="Verwijder uit watchlist" title="Verwijder uit watchlist" border="0" /></a>
			<a onclick="addWatchSpot('<?php echo $spot['messageid'].'\','.$spot['id'] ?>)" id="watch_<?php echo $spot['id'] ?>"><img src="templates_splendid/img/watch.png" alt="Plaats in watchlist" title="Plaats in watchlist" border="0" /></a>
		<?php }
		echo "  </td>\n";
		echo "</tr>\r\n";
	}
?>
					<tr>
					  <td colspan="8" class="shadow"><img src="templates_splendid/img/shadow.gif" width="100%" height="7" border="0" alt="" /></td>
					</tr>
					
					<tr>
						<td colspan="4" style='text-align: left;padding: 0'><?php if ($prevPage >= 0) { ?> <a onclick="$('#spots').load('?direction=prev&amp;pagenr=<?php echo $prevPage . $getUrl;?>&ajax=1');scrollToTop()" class="vorige"></a><?php }?></td>
						<td colspan="4" style='text-align: right;'><?php if ($nextPage > 0) { ?> <a onclick="$('#spots').load('?direction=next&amp;pagenr=<?php echo $nextPage . $getUrl;?>&ajax=1');scrollToTop()" class="volgende"></a><?php }?></td>
					</tr>

				</tbody>
			</table>
		</div>

		<div class="clear" id="ajax_calls"></div>
		<br /><br />
		
<?php } ?>