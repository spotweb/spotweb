<?php
	require_once "includes/header.inc.php";	
	require_once "includes/filters.inc.php";
?>
<div id="statistics" class="statistics">
<?php
	echo "<form><select name='limits' onchange=\"window.location.href=this.form.limits.options[this.form.limits.selectedIndex].value\">" . PHP_EOL;
	foreach ($tplHelper->getValidStatisticsLimits() as $optionValue => $optionName) {
		echo "<option value='?page=statistics&amp;limit=" . $optionValue . "'";
		if ($optionValue == $limit) {
			echo " selected";
		} # if
		echo ">" . $optionName . "</option>" . PHP_EOL;
	} # foreach
	echo "</select></form><br /><br />" . PHP_EOL . PHP_EOL;

	foreach ($tplHelper->getValidStatisticsGraphs() as $graphValue => $graphName) {
		echo "<img src='?page=getimage&amp;image[type]=statistics&amp;image[graph]=" . $graphValue . "&amp;image[limit]=" . $limit . "'> " . PHP_EOL;
	} # foreach
?>
</div>
<?
	require_once "includes/footer.inc.php";