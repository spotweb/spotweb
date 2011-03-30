<?php
	list($spot, $comments) = $tplHelper->formatSpot($spot, $comments);
?>
    	<div class="details <?php echo $tplHelper->cat2color($spot) ?>">
            <a class="postimage" rel="noreferrer" href="<?php echo $spot['image']; ?>">
                <img class="spotinfoimage" src="<?php echo $tplHelper->makeImageUrl($spot, 300, 300); ?>" alt="<?php echo $spot['title'];?>">
            </a>
			<div class="spotinfo">
<?php if (!$spot['verified'] || $tplHelper->isModerated($spot)) {
	echo "<div class='warning'>";
	if (!$spot['verified']) {
		echo "Deze spot is niet geverifi&euml;erd, de naam van de poster is niet bevestigd!<br>";
	}
	if ($tplHelper->isModerated($spot)) {
		echo "Deze spot is als mogelijk onwenselijk gemodereerd!";
	}
	echo "</div>";
} ?>

				<table class="spotheader">
					<tbody>
                    	<tr>						
                        	<th class="category"><span><?php echo $spot['formatname'];?></span></th>
                            <th class="title"><?php echo $spot['title'];?></th>
                            <th class="nzb">
                            	<a class="search" href="<?php echo $spot['searchurl'];?>" title="NZB zoeken">Zoeken</a>
<?php if (!empty($spot['nzb']) && $spot['stamp'] > 1290578400 && $settings['show_nzbbutton']) { ?>
								|
                            	<a class="nzb" href="<?php echo $tplHelper->makeNzbUrl($spot); ?>" title="Download NZB <?php if ($tplHelper->hasBeenDownloaded($spot)) {echo '(deze spot is al gedownload)';} ?>">NZB<?php if ($tplHelper->hasBeenDownloaded($spot)) {echo '*';} ?></a>
<?php } ?>								
                            </th>
<?php if ($settings['keep_watchlist']) {
	echo "<th class='watch'>";
	echo "<a onclick=\"toggleWatchSpot('".$spot['messageid']."','remove',".$spot['id'].")\""; if($tplHelper->isBeingWatched($spot) == false) { echo " style='display:none;'"; } echo " id='watchremove_".$spot['id']."'><img src='templates/we1rdo/img/fav.png' alt='Verwijder uit watchlist' title='Verwijder uit watchlist'/></a>";
	echo "<a onclick=\"toggleWatchSpot('".$spot['messageid']."','add',".$spot['id'].")\""; if($tplHelper->isBeingWatched($spot) == true) { echo " style='display:none;'"; } echo " id='watchadd_".$spot['id']."'><img src='templates/we1rdo/img/fav_light.png' alt='Plaats in watchlist' title='Plaats in watchlist' /></a>";
	echo "</th>";
} ?>                     
<?php if ((!empty($spot['nzb'])) && (!empty($spot['sabnzbdurl']))) { ?>
	<?php if ($tplHelper->hasBeenDownloaded($spot)) { ?>
                            <th class="sabnzbd"><a class="sabnzbd-button" href="<?php echo $spot['sabnzbdurl'];?>" title="Add NZB to SabNZBd queue (you already downloaded this spot)"><img width="20" height="17" src="templates/we1rdo/img/succes.png" class="sabnzbd-button" alt="Add NZB to SabNZBd queue (you already downloaded this spot)"></a></th>
	<?php } else { ?>
                            <th class="sabnzbd"><a class="sabnzbd-button" href="<?php echo $spot['sabnzbdurl'];?>" title="Add NZB to SabNZBd queue"><img width="20" height="17" src="templates/we1rdo/img/download.png" class="sabnzbd-button" alt="Add NZB to SabNZBd queue"></a></th>
<?php } } ?>
                        </tr>
                    </tbody>
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
                        <tr> <th> Afzender </th> <td> <?php echo $spot['poster']; ?> (<a target = "_parent" href="<?php echo $tplHelper->makeUserIdUrl($spot); ?>" title='Zoek naar spots van "<?php echo $spot['poster']; ?>"'><?php echo $spot['userid']; ?></a>) </td> </tr>
                        <tr> <th> Tag </th> <td> <?php echo $spot['tag']; ?> </td> </tr>
                        <tr> <td class="break" colspan="2">&nbsp;   </td> </tr>
                        <tr> <th> Zoekmachine </th> <td> <a href='<?php echo $spot['searchurl']; ?>'>Zoek</a> </td> </tr>
<?php
		if (!empty($spot['nzb'])) {
?>		
                        <tr> <th> NZB </th> <td> <a href='<?php echo $tplHelper->makeNzbUrl($spot); ?>'>NZB</a> </td> </tr>
<?php
		}
?>
						
                    </tbody>
				</table>
      		</div>
            <div class="description">
            	<h4>Post Description</h4>
                <pre><?php echo $spot['description']; ?></pre>
            </div>
            <div class="comments" id="comments">
            	<h4>Comments <span class="commentcount"># <?php echo count($comments); ?></span></h4>
					<ul>
<?php
		$count = 0;
		foreach($comments as $comment) {
			if ($comment['verified']) {
?>
					<li class="<?php $count++; echo ($count % 2 ? "odd" : "even"); ?>"> <strong> Gepost door <span class="user"><?php echo $comment['from']; ?></span> (<a class="userid" target = "_parent" href="<?php echo $tplHelper->makeUserIdUrl($comment); ?>" title='Zoek naar spots van "<?php echo $comment['from']; ?>"'><?php echo $comment['userid']; ?></a>) @ <?php echo $tplHelper->formatDate($comment['date'], 'comment'); ?> </strong> <br>
						<?php echo join("<br>", $comment['body']); ?>
					</li>
<?php	
			} # if
		} # foreach
		if ($count == 0) {
			echo "<li class='nocomments'>Geen (geverifieerde) comments gevonden.</li>";
		}
?>
				</ul>
            </div>
		</div>

