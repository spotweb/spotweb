<?php 
$getUrl = $tplHelper->getQueryParams();
$setpath = $tplHelper->makeBaseUrl("path");
?>

<div data-role="page" id="spots"> 
	<div data-role="header" data-backbtn="false">
	<h1>Spotweb</h1>
	<a href='<?php echo $setpath; ?>index.php' data-transition='fade' rel="external" data-icon="refresh" class="ui-btn-right">Reset filter</a>
	<div data-role="navbar">
		<ul>
			<li><a href="#spots" class="ui-btn-active" data-icon="grid" >Spots</a></li>
			<li><a href="#search"  data-icon="search">Zoek</a></li>
			<li><a href="#filters" data-icon="star">Filters</a></li>

		</ul>
	</div><!-- /navbar -->

</div>
<div data-role="content">
		

<ul data-role="listview">
<?php
$count = 0;

	foreach($spots as $spot) {
		# Format the spot header
		$spot = $tplHelper->formatSpotHeader($spot);
		
		echo "<li><a href='". $setpath . "index.php?page=getspotmobile&amp;messageid=" . $spot['messageid'] . "' data-rel='dialog' data-transition='slidedown'><h3>[". SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']) . "] ".$spot['title'] . "</h3><p>". strtoupper($tplHelper->formatDate($spot['stamp'], 'spotlist')) ."</p></a></li>\n";
	}
	
?>
</ul>

<fieldset class="ui-grid-a">
	<?php if ($prevPage >= 0) { ?> 
	<div class="ui-block-a"><a href="<?php echo $setpath;?>index.php?direction=prev&amp;pagenr=<?php echo $prevPage;?>#spots" disabled data-theme="a" rel=external data-role="button" data-icon="arrow-l" >Vorige</a></div>
			<?php }else{ ?>
	<div class="ui-block-a"><a href="<?php echo $setpath;?>#" disabled data-theme="c" rel=external data-role="button" data-icon="minus">&nbsp;</a></div>
			<?php } ?> 
			<?php if ($nextPage > 0) { ?>
	<div class="ui-block-b"><a href="<?php echo $setpath;?>index.php?pagenr=spots&amp;direction=next&amp;page=<?php echo $nextPage;?>#spots" data-theme="a" rel="external" data-role="button" data-icon="arrow-r">Volgende</a></div>	
	<?php } ?>   
</fieldset>


		

		