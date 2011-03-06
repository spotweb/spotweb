			<div class="filtercontainer">
			
				<div class="filter shadow">
					<h4>Zoeken</h4>

					<form id="filterform" action="">
<?php
	$search = array_merge(array('type' => 'Titel', 'text' => '', 'tree' => ''), $search);
?>
					<input type="hidden" id="search-tree" name="search[tree]" value="<?php echo $search['tree']; ?>"></input>
						<table class="filters">
						<tr> 
							<td> <input type="radio" name="search[type]" value="Titel"  <?php echo $search['type'] == "Titel" ? 'checked="checked"' : "" ?>>Titel</input> </td>
							<td> <input type="radio" name="search[type]" value="Poster" <?php echo $search['type'] == "Poster" ? 'checked="checked"' : "" ?>>Afzender</input> </td>
							<td> <input type="radio" name="search[type]" value="Tag"	<?php echo $search['type'] == "Tag" ? 'checked="checked"' : "" ?>>Tag</input> </td>
						</tr>
						
						<tr>
							<td colspan="3"><input class='searchbox' type="text" name="search[text]" value="<?php echo htmlspecialchars($search['text']); ?>"></input></td>
						</tr>
						</table>
					
						<div id="tree"> 
							<ul>
							</ul>
						</div>
						
						<br>
						<input type='submit' class="filtersubmit" value='Zoek en filter'></input>
					</form>
				</div>

				<div class="filter shadow">
					<h4>Filters</h4>
					
					<ul class="filterlist">
<?php
	foreach($filters as $filter) {
?>
						<li> <a class="filter <?php echo $filter[3]; ?>" href="?search[tree]=<?php echo $filter[2];?>"><img src='<?php echo $filter[1]; ?>'><?php echo $filter[0]; ?></a>
<?php
		if (!empty($filter[4])) {
			echo "\t\t\t\t\t\t\t<ul class='filterlist subfilterlist'>\r\n";
			foreach($filter[4] as $subFilter) {
?>
								<li> <a class="filter <?php echo $subFilter[3];?>" href="?search[tree]=<?php echo $subFilter[2];?>"><img src='<?php echo $subFilter[1]; ?>'><?php echo $subFilter[0]; ?></a>
<?php
			} # foreach 
			echo "\t\t\t\t\t\t\t</ul>\r\n";
		} # is_array
	} # foreach
?>
					</ul>
				</div>
				<div class="filter shadow">
					<h4>Maintenance</h4>
					<ul class="filterlist maintenancebox">
<?php
	if ($settings['show_updatebutton']) {
?>
						<li> <a href="retrieve.php?output=xml" id="updatespotsbtn" class="updatespotsbtn">Update Spots <img id="updatespotimg" src="images/gobutton.png"></img></a></li>
<?php
	}
?>
						<li> <a href="?page=erasedls" id="removedllistbtn" class="erasedlsbtn">Remove history of downloads <img id="erasedlsimg" src="images/gobutton.png"></img></a></li>
					</ul>
                </div>

			</div>
