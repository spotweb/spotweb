<?php
	list($spot, $comments) = $tplHelper->formatSpot($spot, $comments);
?>
    	<div class="details <?php echo $tplHelper->cat2color($spot) ?>">
            <a class="postimage" rel="noreferrer" href="<?php echo $spot['image']; ?>">
                <img class="spotinfoimage" src="?page=getimage&amp;messageid=<?php echo $spot['messageid']; ?>&amp;image[height]=300&amp;image[width]=300">
            </a>
			<div class="spotinfo">
<?php
	if (!$spot['verified']) {
?>
				<div class="warning">Deze Spot is niet geverifi&euml;erd, de naam van de poster is niet bevestigd!</div>
<?php
	}
?>
				<table class="spotheader">
					<tbody>
                    	<tr>						
                        	<th class="category"><?php echo $spot['formatname'];?></th>
                            <th class="title"><?php echo $spot['title'];?></th>
                            <th class="nzb">
                            	<a class="search" href="<?php echo $spot['searchurl'];?>" title="NZB zoeken">Zoeken</a>
                                |
<?php if (!empty($spot['nzb'])) { ?>
                            	<a class="nzb" href="?page=getnzb&amp;messageid=<?php echo $spot['messageid']; ?>" title="NZB downloaden">NZB</a>
<?php } ?>								
                            </th>
<?php if ((!empty($spot['nzb'])) && (!empty($spot['sabnzbdurl']))) { ?>
                            <th class="sabnzbd"><a href="<?php echo $spot['sabnzbdurl'];?>" title="Add NZB to SabNZBd queue"><img height="16" width="16" src="images/download-small.png" class="sabnzbd-button"></a></th>
<?php } ?>								
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
                        <tr><th> Omvang </th> <td> <?php echo $tplHelper->format_size($spot['size']); ?> </td> </tr>
                        <tr><td class="break" colspan="2">&nbsp;   </td> </tr>
                        <tr><th> Website </th> <td> <a href='<?php echo $spot['website']; ?>' target="_blank"><?php echo $spot['website'];?></a> </td> </tr>
                        <tr> <td class="break" colspan="2">&nbsp;   </td> </tr>
                        <tr> <th> Afzender </th> <td> <?php echo $spot['poster']; ?> (<?php echo $spot['userid']; ?>) </td> </tr>
                        <tr> <th> Tag </th> <td> <?php echo $spot['tag']; ?> </td> </tr>
                        <tr> <td class="break" colspan="2">&nbsp;   </td> </tr>
                        <tr> <th> Zoekmachine </th> <td> <a href='<?php echo $spot['searchurl']; ?>'>Zoek</a> </td> </tr>
                        <tr> <th> NZB </th> <td> <a href='?page=getnzb&amp;messageid=<?php echo $spot['messageid']; ?>'>NZB</a> </td> </tr>
                    </tbody>
				</table>
      		</div>
            <div class="description">
            	<h4>Post Description</h4>
                <pre><?php echo $spot['description']; ?></pre>
            </div>
            <div class="comments" id="comments">
            	<h4>Comments</h4>
					<ul>
<?php
		$count = 0;
		foreach($comments as $comment) {
?>
					<li class="<?php $count++; echo ($count % 2 ? "odd" : "even"); ?>"> <strong> Gepost door <span class="user"><?php echo $comment['from']; ?></span> @ <?php echo $tplHelper->formatDate($comment['date'], 'comment'); ?> </strong> <br>
						<?php echo join("<br>", $comment['body']); ?>
					</li>
<?php	
		} # foreach
?>
				</ul>
            </div>
		</div>

