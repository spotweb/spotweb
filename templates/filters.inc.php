			<div class="filtercontainer">
			
				<div class="filter shadow">
					<h4>Zoeken</h4>

					<form id="filterform" action="">
					<input type="hidden" id="search-tree" name="search[tree]" value="<?php echo $search['tree']; ?>"></input>
						<table class="filters">
						<tr> 
<?php
	$search = array_merge(array('type' => 'Titel', 'text' => ''), $search);
?>
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

				<div class="filter">
					<h4>Filters</h4>
					
					<ul class="filterlist">
<?php
	foreach($filters as $filter) {
?>
						<li> <a class="filter <?php echo $filter[3]; ?>" onclick="matchTree('<?php echo $filter[2];?>', true);"><img src='<?php echo $filter[1]; ?>'><?php echo $filter[0]; ?></a>
<?php
		if (!empty($filter[4])) {
			echo "\t\t\t\t\t\t\t<ul class='filterlist subfilterlist'>\r\n";
			foreach($filter[4] as $subFilter) {
?>
								<li> <a class="filter <?php echo $subFilter[3];?>" onclick="matchTree('<?php echo $subFilter[2];?>', true);"><img src='<?php echo $subFilter[1]; ?>'><?php echo $subFilter[0]; ?></a>
<?php
			} # foreach 
			echo "\t\t\t\t\t\t\t</ul>\r\n";
		} # is_array
	} # foreach
?>
					</ul>
				</div>

<?php
	if ($settings['show_updatebutton']) {
?>
				<div class="filter shadow">
					<h4>Update</h4>
					<ul class="filterlist">
						<li> <a href="retrieve.php">Update Spots</a></li>
					</ul>
                </div>
<?php
	}
?>
			</div>
