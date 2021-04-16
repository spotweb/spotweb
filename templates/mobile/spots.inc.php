<?php
/* Render de header template */
require_once __DIR__.'/header.inc.php';

$setpath = $tplHelper->makeBaseUrl('path');
?>

<div data-role="page" id="spots"> 
	<div data-role="header" data-backbtn="false">
	    <h1>Spots<?php require __DIR__.'/getusername.inc.php'; ?></h1>
		<a href='<?php echo $setpath; ?>index.php' data-transition='fade' rel="external" data-icon="refresh" class="ui-btn-right">Reset filter</a>
        <?php $active = 'spots'; require __DIR__.'/includes/navbar.inc.php'; ?>
	</div>
<div data-role="content">
	<fieldset class="ui-grid-a">
		<?php if ($prevPage >= 0) { ?> 
		<div class="ui-block-a"><a href="<?php echo $setpath; ?>index.php?direction=prev&amp;pagenr=<?php echo $prevPage.$tplHelper->convertSortToQueryParams().$tplHelper->convertFilterToQueryParams(); ?>#spots" data-theme="a" rel="external" data-role="button" data-icon="arrow-l" >Previous</a></div>
				<?php } else { ?>
		<div class="ui-block-a"><a href="<?php echo $setpath; ?>#" data-theme="c" rel="external" data-role="button" data-icon="minus">&nbsp;</a></div>
				<?php } ?> 
				<?php if ($nextPage > 0) { ?>
		<div class="ui-block-b"><a href="<?php echo $setpath; ?>index.php?direction=next&amp;pagenr=<?php echo $nextPage.$tplHelper->convertSortToQueryParams().$tplHelper->convertFilterToQueryParams(); ?>#spots" data-theme="a" rel="external" data-role="button" data-icon="arrow-r">Next</a></div>	
		<?php } ?>
	</fieldset>
	<br>
	<ul data-role="listview">
		<?php
        $count = 0;

            foreach ($spots as $spot) {
                // Format the spot header
                $spot = $tplHelper->formatSpotHeader($spot);
                echo "<li><a href='".$setpath.'index.php?page=getspot&amp;messageid='.$spot['messageid']."' data-rel='dialog' data-transition='slidedown'><h3>[".SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']).'] '.$spot['title'].'</h3><p>'.strtoupper($tplHelper->formatDate($spot['stamp'], 'spotlist')).'';
                if ($settings->get('imageover_subcats') > 0) {
                    echo "<center><img src='".$tplHelper->makeImageUrl($spot, 150, 150)."' height='100' /></center></p></a></li>\n";
                } else {
                    echo "</p></a></li>\n";
                }
            }

        ?>
	</ul>
	<br>
	<fieldset class="ui-grid-a">
		<?php if ($prevPage >= 0) { ?> 
		<div class="ui-block-a"><a href="<?php echo $setpath; ?>index.php?direction=prev&amp;pagenr=<?php echo $prevPage.$tplHelper->convertSortToQueryParams().$tplHelper->convertFilterToQueryParams(); ?>#spots" data-theme="a" rel="external" data-role="button" data-icon="arrow-l" >Previous</a></div>
				<?php } else { ?>
		<div class="ui-block-a"><a href="<?php echo $setpath; ?>#" data-theme="c" rel="external" data-role="button" data-icon="minus">&nbsp;</a></div>
				<?php } ?> 
				<?php if ($nextPage > 0) { ?>
		<div class="ui-block-b"><a href="<?php echo $setpath; ?>index.php?direction=next&amp;pagenr=<?php echo $nextPage.$tplHelper->convertSortToQueryParams().$tplHelper->convertFilterToQueryParams(); ?>#spots" data-theme="a" rel="external" data-role="button" data-icon="arrow-r">Next</a></div>	
		<?php } ?>   
	</fieldset>
	</div><!-- Content end -->
</div> <!-- Page end -->

<?php
/* Render de search and filter template */
require_once __DIR__.'/filters.inc.php';
/* Render de footer template */
echo '</div>';
require_once __DIR__.'/footer.inc.php';
?>
