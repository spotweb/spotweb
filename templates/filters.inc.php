			<div class="filtercontainer">
			
				<div class="filter shadow">
					<h4>Zoeken</h4>

					<form action="">
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
						<li> <a href="?search[cat][0][]=a!a5&amp;search[cat][0][]=a!d11&amp;search[cat][0][]=a!d23&amp;search[cat][0][]=a!d24&amp;search[cat][0][]=a!d25&amp;search[cat][0][]=a!d26"><img src='images/video2.png'>Films (geen erotiek)</a>
						<li> <a href="?search[cat][0][]=od11"><img src='images/series2.png'>Series</a>
						<li> <a href="?search[cat][0][]=oa5"><img src='images/books2.png'>Boeken</a>
						<li> <a href="?search[cat][1]=1"><img src='images/audio2.png'>Muziek</a>
						<li> <a href="?search[cat][2]=2"><img src='images/games2.png'>Spellen</a>
						<li> <a href="?search[cat][3]=3"><img src='images/applications2.png'>Applicaties</a>
						<li> <a href="?search[cat][0][]=od23&amp;search[cat][0][]=od24&amp;search[cat][0][]=od25&amp;search[cat][0][]=od26"><img src='images/x2.png'>Erotiek</a>
						<li> <a href="?search[cat]="><img src='images/custom2.png'>Reset filters</a>
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
