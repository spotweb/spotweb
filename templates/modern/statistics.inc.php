<?php
    require_once __DIR__.'/includes/header.inc.php';
    require_once __DIR__.'/../we1rdo/includes/filters.inc.php';

    $validLimits = $tplHelper->getValidStatisticsLimits();
    $validGraphs = $tplHelper->getValidStatisticsGraphs();

    if (!array_key_exists($limit, $validLimits)) {
        $limitKeys = array_keys($validLimits);
        $limit = array_shift($limitKeys);
    }
?>
<style>
  .statistics { color: var(--color-text); text-align: center; }
  .statistics .responsive { max-width: 42%; height: auto; margin: 8px; border-radius: 8px; border: 1px solid var(--color-border); background: var(--color-surface); }
  .statistics form select { background: var(--color-surface); color: var(--color-text); border: 1px solid var(--color-border); border-radius: 6px; padding: 2px 6px; }
</style>
<div id="statistics" class="statistics">
<?php
    echo "<form><select name='limits' onchange=\"window.location.href=this.form.limits.options[this.form.limits.selectedIndex].value\">".PHP_EOL;
    foreach ($validLimits as $optionValue => $optionName) {
        echo "<option value='".$tplHelper->makeBaseUrl('path')."?page=statistics&amp;limit=".$optionValue."'";
        if ($optionValue == $limit) {
            echo ' selected';
        }
        echo '>'.$optionName.'</option>'.PHP_EOL;
    }
    echo '</select></form><br /><br />'.PHP_EOL.PHP_EOL;

    foreach ($validGraphs as $graphValue => $graphName) {
        $imgUrl = $tplHelper->makeBaseUrl('path').'?page=getimage&amp;image[type]=statistics&amp;image[graph]='.$graphValue.'&amp;image[limit]='.$limit;
        echo "<a class='image' href='".$imgUrl."' target='_blank'><img class='responsive' src='".$imgUrl."' alt='".htmlspecialchars($graphName)."'></a>".PHP_EOL;
    }
?>
</div>
<?php
    require_once __DIR__.'/../we1rdo/includes/footer.inc.php';

