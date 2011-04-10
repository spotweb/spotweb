<?php 
// Set $_GET['page'] if it's not set
if(empty($_GET['page'])) $_GET['page'] = '';

// check if it's a ajax call
if(empty($_GET['ajax'])) { ?>
				<div style="float: left">
				<div class="filter" id="menu">
					<h4>Zoeken</h4>

					<form id="filterform" action="" onsubmit="return submitSearchForm(this)">
						<?php $search = array_merge(array('type' => 'Titel', 'text' => '', 'tree' => '', 'unfiltered' => ''), $search); ?>
						<input type="hidden" id="search-tree" class="" value="<?php echo $search['tree']; ?>">
						<table border="0" cellpadding="0" cellspacing="0" style="width: 214px;margin-left: 20px">
						  <tr> 
						    <td><input type="radio" name="search[type]" class="radio_type" value="Titel"<?php echo $search['type'] == "Titel" ? ' checked="checked"' : "" ?>>Titel</td>
						    <td><input type="radio" name="search[type]" class="radio_type" value="Poster"<?php echo $search['type'] == "Poster" ? ' checked="checked"' : "" ?>>Afzender</td>
							<td><input type="radio" name="search[type]" class="radio_type" value="Tag"<?php echo $search['type'] == "Tag" ? ' checked="checked"' : "" ?>>Tag</td>
						  </tr>
						  <tr>
						    <td colspan="3" id="search_box">
						      <input class="search_text" type="text" name="search[text]" value="<?php echo htmlspecialchars($search['text']); ?>">
						      <input type="submit" value="Zoek" class="submit">
						    </td>
						  </tr>
						  <tr>
						    <td colspan='3'><a onclick="clearTree()">Reset selectie</a></td>
						  </tr>
						</table>
						
						
						<div id="tree"> 
						  <ul>
						  </ul>
						</div>
						
						
					</form><br />
					
					<h4>QuickLinks</h4><br />
					
					<ul id="quicklinksmenu">
<?php
	foreach($quicklinks as $quicklink) {
		$strFilter = $tplHelper->getPageUrl('index') . $quicklink[2];
?>
						<li><div><a class="quicklink <?php echo $quicklink[3]; ?>" onclick="$('#spots').load('<?php echo $strFilter;?>&amp;ajax=1');clearTree();">
						<img src='<?php echo $quicklink[1]; ?>'><?php echo $quicklink[0]; if (stripos($quicklink[2], 'New:0')) { echo "(".$tplHelper->getNewCountForFilter($quicklink[2]).")"; } ?></a></div></li>
<?php
	}
?>
                    </ul><br /><br />

					<h4>Filters</h4><br />
                    
                    <ul id="filtermenu">
<?php
    foreach($filters as $filter) {
		$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $filter[2];
?>
						<li<?php if($filter[2]) { echo " class='". $tplHelper->filter2cat($filter[2]) ."'"; } ?>><div><a class="filter <?php echo $filter[3]; ?>" onclick="$('#spots').load('?search[tree]=<?php echo $strFilter;?>&amp;ajax=1');clearTree();">
						<img src='<?php echo $filter[1]; ?>'><?php echo $filter[0]; if ($tplHelper->getNewCountForFilter($strFilter)) { echo "(".$tplHelper->getNewCountForFilter($strFilter).")"; } ?></a></div></li>
<?php
        if (!empty($filter[4])) {
			
            //echo "\t\t\t\t\t\t\t<ul class=''>\r\n";
			foreach($filter[4] as $subFilter) {
				$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $subFilter[2];
?>
            			<li><div><a class="subfilter <?php echo $subFilter[3];?>" onclick="$('#spots').load('<?php echo $strFilter; ?>&amp;ajax=1');clearTree();">
						<img src='<?php echo $subFilter[1]; ?>'><?php echo $subFilter[0]; if ($tplHelper->getNewCountForFilter($strFilter)) { echo "(".$tplHelper->getNewCountForFilter($strFilter).")"; } ?></a></div></li>
            			
<?php
				if (!empty($subFilter[4])) {
					//echo "\t\t\t\t\t\t\t<ul class=''>\r\n";
					foreach($subFilter[4] as $sub2Filter) {
						$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $sub2Filter[2];
		?>
							<li><div><a class="subsubfilter <?php echo $sub2Filter[3];?>" onclick="$('#spots').load('<?php echo $strFilter; ?>&amp;ajax=1');clearTree();">
							<img src='<?php echo $sub2Filter[1]; ?>'><?php echo $sub2Filter[0]; if ($tplHelper->getNewCountForFilter($strFilter)) { echo "(".$tplHelper->getNewCountForFilter($strFilter).")"; } ?></a></div></li>
		<?php
					} # foreach 
					//echo "\t\t\t\t\t\t\t</ul>\r\n";
				} # is_array
			
			} # foreach 
            //echo "\t\t\t\t\t\t\t</ul>\r\n";
        } # is_array
    } # foreach
?>
                    </ul><br /><br />

					<h4>Maintenance</h4>
					<ul class="maintenancebox">
						<li class="info"> Laatste update: <?php echo $tplHelper->formatDate($lastupdate, 'lastupdate'); ?> </li>
<?php
	if ($settings['show_updatebutton']) {
?>
						<li> <a href="retrieve.php?output=xml" id="updatespotsbtn" class="big_button updatespotsbtn"><div>Update Spots</div></a></li>
<?php
	}
?>
<?php
	if ($settings['keep_downloadlist']) {
?>
						<li> <a href="?page=erasedls" id="removedllistbtn" class="big_button erasedlsbtn"><div>Reset download geschiedenis</div></a></li>
<?php
	}
?>
						<li> <a href="?page=markallasread" id="markallasreadbtn" class="big_button markallasreadbtn"><div>Markeer als gelezen</div></a></li>
					</ul>

				</div><br style="clear: both" />
				<img src="templates/splendid/img/menu_end.jpg" width="255" height="24" border="0" />
			</div>

<?php } ?>
