<?php
	$spot = $tplHelper->formatSpot($spot);
	$comments = $tplHelper->getSpotComments($spot['messageid'], 0, 0);
	$comments = $tplHelper->formatComments($comments);
	
	// fix the sabnzbdurl en searchurl
	$spot['sabnzbdurl'] = $tplHelper->makeSabnzbdUrl($spot);
	$spot['searchurl'] = $tplHelper->makeSearchUrl($spot);
?>
    	<div class="details <?php echo $tplHelper->cat2color($spot) ?>">
            <a class="postimage" rel="noreferrer" href="<?php echo $spot['image']; ?>"><img class="spotinfoimage" src="<?php echo $tplHelper->makeImageUrl($spot, 300, 300); ?>"></a>
			<div class="spotinfo">
<?php if (!$spot['verified']) { ?>
				<div class="warning">Deze spot is niet geverifi&euml;erd, de naam van de poster is niet bevestigd!</div>
<?php } 
	  if($tplHelper->isModerated($spot)) { ?>
				<div class="warning">Deze spot is als mogelijk onwenselijk gemodereerd!</div>
<?php } ?>

				<table class="spotheader">
					<tbody>
                    	<tr>						
                        	<th class="category"><?php echo $spot['formatname'];?></th>
                            <th class="title"><?php echo $spot['title'];?></th>
                            <th class="nzb">
                            	<a class="search" href="<?php echo $spot['searchurl'];?>" title="NZB zoeken">Zoeken</a>
<?php if (!empty($spot['nzb'])) { ?>
								|
                            	<a class="nzb" href="<?php echo $tplHelper->makeNzbUrl($spot); ?>" title="Download NZB <?php if ($tplHelper->hasBeenDownloaded($spot)) {echo '(deze spot is al gedownload)';} ?>">NZB<?php if ($tplHelper->hasBeenDownloaded($spot)) {echo '*';} ?></a>
<?php } ?>								
                            </th>
<?php if ((!empty($spot['nzb'])) && (!empty($spot['sabnzbdurl']))) { ?>
                            <th class="sabnzbd"><a class="sabnzbd-button" href="<?php echo $spot['sabnzbdurl'];?>" title="Add NZB to SabNZBd queue"><img height="16" width="16" src="images/download-small.png" class="sabnzbd-button"></a></th>
<?php } ?>								
                        	<th class="watch">
<?php
if($tplHelper->isBeingWatched($spot)) { ?>
								<a onclick="removeWatchSpot('<?php echo $spot['messageid'].'\','.$spot['id'] ?>)" id="watched_<?php echo $spot['id'] ?>"><img src="templates/splendid/img/watch_active.png" alt="Verwijder uit watchlist" title="Verwijder uit watchlist" border="0" /></a>
								<a onclick="addWatchSpot('<?php echo $spot['messageid'].'\','.$spot['id'] ?>);" style="display: none" id="watch_<?php echo $spot['id'] ?>"><img src="templates/splendid/img/watch.png" alt="Plaats in watchlist" title="Plaats in watchlist" border="0" /></a>
<?php } else { ?>
								<a onclick="removeWatchSpot('<?php echo $spot['messageid'].'\','.$spot['id'] ?>)" style="display: none" id="watched_<?php echo $spot['id'] ?>"><img src="templates/splendid/img/watch_active.png" alt="Verwijder uit watchlist" title="Verwijder uit watchlist" border="0" /></a>
								<a onclick="addWatchSpot('<?php echo $spot['messageid'].'\','.$spot['id'] ?>)" id="watch_<?php echo $spot['id'] ?>"><img src="templates/splendid/img/watch.png" alt="Plaats in watchlist" title="Plaats in watchlist" border="0" /></a>
<?php } ?>
                        	</th>
                        </tr>
                    </table>
                </table>
                
				<table class="spotinfo">
                	<tbody>
                        <tr><th> Categorie </th> <td> <?php echo $spot['catname']; ?> </td> </tr>
<?php
	if (!empty($spot['subcatlist'])) {
		foreach($spot['subcatlist'] as $sub) {
			$subcatType = substr($sub, 0, 1);
			echo "\t\t\t\t\t\t<tr><th> " . SpotCategories::SubcatDescription($spot['category'], $subcatType) .  "</th> <td> " . SpotCategories::Cat2Desc($spot['category'], $sub) . " </td> </tr>\r\n";
		} # foreach
	} # if
?>
                        <tr><th> Omvang </th> <td> <?php echo $tplHelper->format_size($spot['filesize']); ?> </td> </tr>
                        <tr><td class="break" colspan="2">&nbsp;   </td> </tr>
                        <tr><th> Website </th> <td> <a href='<?php echo $spot['website']; ?>' target="_blank"><?php echo $spot['website'];?></a> </td> </tr>
                        <tr> <td class="break" colspan="2">&nbsp;   </td> </tr>
                        <tr> <th> Afzender </th> <td> <?php echo $spot['poster']; ?> (<?php echo $spot['userid']; ?>) </td> </tr>
                        <tr> <th> Tag </th> <td> <?php echo $spot['tag']; ?> </td> </tr>
                        <tr> <td class="break" colspan="2">&nbsp;   </td> </tr>
                        <tr> <th> Zoekmachine </th> <td> <a href='<?php echo $spot['searchurl']; ?>'>Zoek</a> </td> </tr>
                        <tr> <th> NZB </th> <td> <a href='<?php echo $tplHelper->makeNzbUrl($spot); ?>'>NZB</a> </td> </tr>
                    </tbody>
				</table>
      		</div>
            <div class="description">
            	<h4>Post Description</h4>
                <pre><?php echo $spot['description']; ?></pre>
            </div>
            <div class="comments" id="comments">
            	<h4>Comments <span class="commentcount"># <?php echo $tplHelper->getCommentCount($spot); ?></span></h4>
					<ul>
<?php
		$count = 0;
		foreach($comments as $comment) {
			if ($comment['verified']) {
?>
					<li class="<?php $count++; echo ($count % 2 ? "odd" : "even"); ?>"> <strong> Gepost door <span class="user"><?php echo $comment['from']; ?></span> (<?php echo $comment['userid']; ?>) @ <?php echo $tplHelper->formatDate($comment['date'], 'comment'); ?> </strong> <br>
						<?php echo join("<br>", $comment['body']); ?>
					</li>
<?php	
			} # if
		} # foreach
?>
				</ul>
            </div>
		</div>

