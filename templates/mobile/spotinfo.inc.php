<?php
	$spot = $tplHelper->formatSpot($spot);
	$comments = $tplHelper->getSpotComments($spot['messageid'], 0, 0);
	$comments = $tplHelper->formatComments($comments);

	$setpath = $tplHelper->makeBaseUrl("path");
	
	// fix the sabnzbdurl en searchurl
	$spot['sabnzbdurl'] = $tplHelper->makeSabnzbdUrl($spot);
	$spot['searchurl'] = $tplHelper->makeSearchUrl($spot);
?>
<div data-role="page" id="spots"> 
	<div data-role="header" data-backbtn="false">
	<h1>Spot info</h1>
<?php if (!empty($spot['sabnzbdurl'])) { ?>
                  <a href="<?php echo str_replace('getnzb','getnzbmobile',$spot['sabnzbdurl']);?>"  data-transition='fade' data-rel="dialog" data-icon="plus" class="ui-btn-right">SAVE</a></th>
<?php } else { ?>	
  <a class="nzb" href="<?php echo $setpath ?>index.php?page=getnzbmobile&amp;messageid=<?php echo $spot['messageid']; ?>"  data-transition='fade' data-icon="plus" data-rel="dialog" class="ui-btn-right">NZB</a>
<?php } ?>		
	
	</div>
	<div data-role="content">
   	<img class="spotinfoimage" src="<?php echo $spot['image']; ?>" height="150">
<p>
<?php
	if (!$spot['verified']) {
?>
	* niet geverifieerd *
<?php
	}
?>
  </p>                     	
                            <h3><?php echo $spot['title'];?></h3>
                
                
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
                        <tr><th> Website </th> <td> <a href='<?php echo $spot['website']; ?>' target="_blank">BEKIJK</a> </td> </tr>
                        <tr><th> Afzender </th> <td> <?php echo $spot['poster']; ?> (<?php echo $spot['userid']; ?>) </td> </tr>
                        <tr><th> Tag </th> <td> <?php echo $spot['tag']; ?> </td> </tr>
                        <tr><th> Zoekmachine </th> <td> <a href='<?php echo $setpath . $spot['searchurl']; ?>'>Zoek</a> </td> </tr>
                        <tr><th> NZB </th> <td> <a href='<?php echo $setpath; ?>?page=getnzb&amp;messageid=<?php echo $spot['messageid']; ?>'>NZB</a> </td> </tr>
                    </tbody>
				</table>
            	<h4>Omschrijving</h4>
            	
            	<?php
            	$tmp = $spot['description'];
	
	$tmp = str_replace('[b]', '', $tmp);
	$tmp = str_replace('[/b]', '', $tmp);
		$tmp = str_replace('<b>', '', $tmp);
	$tmp = str_replace('[i]', '', $tmp);
	$tmp = str_replace('[/i]', '', $tmp);
	$tmp = str_replace('[br]', '', $tmp);
	$tmp = str_replace('[u]', '', $tmp);
	$tmp = str_replace('[/u]', '', $tmp);
	$tmp = str_replace('[strong]', '', $tmp);	
	$tmp = str_replace('[/u]', '', $tmp);
	$tmp = str_replace('&lt;br&gt;', '<br>', $tmp);
	$tmp = str_replace('&lt;br /&gt;', '<br>', $tmp);
	echo "<p>$tmp</p>";
            ?>	
            	

<?php 
if(sizeof($comments)>0){ ?>
<div data-role="collapsible" data-collapsed="true">
            	<h4>Comments</h4>
					<ul data-role="listview" data-inset="true">
<?php

		foreach($comments as $comment) {
?>
					<li><?php echo $comment['fromhdr']; ?> @ <?php echo $tplHelper->formatDate($comment['stamp'], 'comment'); ?> <br>
					<?php echo join("<br>", $comment['body']); ?>
					<br>
					</li>
<?php	
		} # foreach
?>
				</ul>
            </div>
        <?php	
		} # comments
?>    
            </div>
		</div>

