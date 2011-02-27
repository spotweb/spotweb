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
						<li> <a class="filter" onclick="matchTree('cat0_d,!cat0_d11,!cat0_d23,!cat0_d24,!cat0_d25,!cat0_d26', true);" ><img src='images/video2.png'>Films (geen erotiek)</a>
						<li> <a class="filter" onclick="matchTree('cat0_d11', true)"><img src='images/series2.png'>Series</a>
						<li> <a class="filter" onclick="matchTree('cat0_a5', true);"><img src='images/books2.png'>Boeken</a>
						<li> <a class="filter" onclick="matchTree('cat1', true);"><img src='images/audio2.png'>Muziek</a>
						<li> <a class="filter" onclick="matchTree('cat2', true);"><img src='images/games2.png'>Spellen</a>
						<li> <a class="filter" onclick="matchTree('cat3', true);"><img src='images/applications2.png'>Applicaties</a>
						<li> <a class="filter" onclick="matchTree('cat0_d23,cat0_d24,cat0_d25,cat0_26', true);"><img src='images/x2.png'>Erotiek</a>
						<li> <a class="filter" onclick="matchTree('', true);"><img src='images/custom2.png'>Reset filters</a>
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
