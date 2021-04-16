<?php
    $spot = $tplHelper->formatSpot($spot);
    $comments = $tplHelper->getSpotComments($spot['messageid'], $spot['prevMsgids'], 0, 99999);
    $comments = $tplHelper->formatComments($comments);

    $setpath = $tplHelper->makeBaseUrl('path');

    // fix the sabnzbdurl and searchurl
    $spot['sabnzbdurl'] = $tplHelper->makeSabnzbdUrl($spot);
    $spot['searchurl'] = $tplHelper->makeSearchUrl($spot);
?>
<div data-role="page" id="spots"> 
	<div data-role="header" data-backbtn="false">
	<h1>Spot info</h1>
        <?php if (!empty($spot['sabnzbdurl']) and $spot['nzbhandlertype'] != 'save') { ?>
        <script type="text/javascript">
            $("#loadnzb").on( "click", function(e) {
                                                    $("#loadnzb") .html("Wait..")
                                                    e.preventDefault()
                                                    e.stopPropagation()
                                                    $.ajax({ 
                                                                type: "GET",
                                                                url: $(this).attr('href'),
                                                                dataType: "json",
                                                                success: function (data)    {
                                                                                                if (data.result == "success") {
                                                                                                    $("#loadnzb").html("Done!")
                                                                                                } else {
                                                                                                    $("#loadnzb").html("Failure!")
                                                                                                }
                                                                                            }
                                                           });
                                                   })
        </script>
        <a href="<?php echo $spot['sabnzbdurl']; ?>" id="loadnzb"   data-transition='fade' data-rel="dialog" data-icon="plus" class="ui-btn-right">Push NZB</a></th>
<?php } else { ?>
        <a href="<?php echo $setpath ?>index.php?page=getnzb&amp;messageid=<?php echo $spot['messageid']; ?>" data-ajax="false" data-transition='fade' data-icon="arrow-d" data-rel="dialog" class="ui-btn-right">NZB</a>
<?php } ?>
	
	</div>
	<div data-role="content">
   	<img class="spotinfoimage" src="<?php echo $tplHelper->makeImageUrl($spot, 150, 150); ?>" height="150">
<p>
<?php
    if (!$spot['verified']) {
        ?>
	* not verified *
<?php
    }
?>
  </p>                     	
                            <h3><?php echo $spot['title']; ?></h3>
                
                
				<table class="spotinfo">
                	<tbody>
                        <tr><th> Category </th> <td> <?php echo $spot['catname']; ?> </td> </tr>
<?php
    if (!empty($spot['subcatlist'])) {
        foreach ($spot['subcatlist'] as $sub) {
            $subcatType = substr($sub, 0, 1);
            echo "\t\t\t\t\t\t<tr><th> ".SpotCategories::SubcatDescription($spot['category'], $subcatType).'</th> <td> '.SpotCategories::Cat2Desc($spot['category'], $sub)." </td> </tr>\r\n";
        } // foreach
    } // if
?>
                        <tr><th> Size: </th> <td> <?php echo $tplHelper->format_size($spot['filesize']); ?> </td> </tr>
                        <tr><th> Website: </th> <td> <a href='<?php echo $spot['website']; ?>' target="_blank">View spot</a> </td> </tr>
                        <tr><th> Poster: </th> <td> <?php echo $spot['poster']; ?> (<?php echo $spot['spotterid']; ?>) </td> </tr>
                        <tr><th> Tag: </th> <td> <?php echo $spot['tag']; ?> </td> </tr>
                        <tr><th> Searchengine: </th> <td> <a href='<?php echo $spot['searchurl']; ?>'>Search</a> </td> </tr>
                        <tr><th> NZB </th> <td> <a href='<?php echo $setpath; ?>?page=getnzb&amp;messageid=<?php echo $spot['messageid']; ?>' data-ajax="false">NZB</a> </td> </tr>
                    </tbody>
				</table>
            	<h4>Description</h4>
            	
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
if (count($comments) > 0) { ?>
<div data-role="collapsible" data-collapsed="true">
            	<h4>Comments</h4>
					<ul data-role="listview" data-inset="true">
<?php

        foreach ($comments as $comment) {
            ?>
					<li><?php echo $comment['fromhdr']; ?> @ <?php echo $tplHelper->formatDate($comment['stamp'], 'comment'); ?> <br>
					<?php echo $comment['body']; ?>
					<br>
					</li>
<?php
        } // foreach
?>
				</ul>
            </div>
        <?php
        } // comments
?>    
            </div>
		</div>

