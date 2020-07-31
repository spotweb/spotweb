<?php
    require_once __DIR__.'/includes/header.inc.php';
    require_once __DIR__.'/includes/filters.inc.php';
    $validLimits = $tplHelper->getValidStatisticsLimits();
    $validGraphs = $tplHelper->getValidStatisticsGraphs();

    if (!array_key_exists($limit, $validLimits)) {
        $limitKeys = array_keys($validLimits);
        $limit = array_shift($limitKeys);
    } // if
?>
<style>
.responsive {
  max-width: 42%;
  height: auto;
}
</style>
<div id="statistics" class="statistics">
<?php
    echo "<form><select name='limits' onchange=\"window.location.href=this.form.limits.options[this.form.limits.selectedIndex].value\">".PHP_EOL;
    foreach ($validLimits as $optionValue => $optionName) {
        echo "<option value='?page=statistics&amp;limit=".$optionValue."'";
        if ($optionValue == $limit) {
            echo ' selected';
        } // if
        echo '>'.$optionName.'</option>'.PHP_EOL;
    } // foreach
    echo '</select></form><br /><br />'.PHP_EOL.PHP_EOL;

    foreach ($validGraphs as $graphValue => $graphName) {
        echo "<a class='image' href='?page=getimage&amp;image[type]=statistics&amp;image[graph]=".$graphValue.'&amp;image[limit]='.$limit."' target='_blank'><img class='responsive' src='?page=getimage&amp;image[type]=statistics&amp;image[graph]=".$graphValue.'&amp;image[limit]='.$limit."'></a>".PHP_EOL;
    } // foreach
?>
</div>
<?php
    require_once __DIR__.'/includes/footer.inc.php';
