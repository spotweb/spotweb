<?php
/* Render de header en filter templates */
require_once __DIR__ . '/header.inc.php';
require_once __DIR__ . '/filters.inc.php';

$setpath = $tplHelper->makeBaseUrl("path");
?>

<div data-role="page" id="spots">
<?php page_header('spots'); ?>
<div data-role="main" class="ui-content">

<ul data-role="listview">
<?php
  foreach($spots as $spot) {
    $spot = $tplHelper->formatSpotHeader($spot);
    $fmt = '<li><a href="%s" data-rel="dialog" data-transition="slideshow"><h3>[%s] %s</h3><p>%s</p></a></li>';
    printf(
      "$fmt\n",
      '?page=getspot&messageid=' . $spot['messageid'],
      SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']),
      $spot['title'],
      strtoupper($tplHelper->formatDate($spot['stamp'], 'spotlist'))
    );
  }
?>
</ul>

  <fieldset class="ui-grid-a">
    <div class="ui-block-a">
<?php
  $fmt = '<a href="%s" class="%s" data-role="button" data-icon="ui-icon-arrow-l" rel="external">%s</a>';
  if($prevPage >= 0) {
    $href = sprintf(
      '?direction=prev&pagenr=%u%s%s#spots',
      $prevPage,
      $tplHelper->convertSortToQueryParams(),
      $tplHelper->convertFilterToQueryParams()
    );
    $args = array($href, '', 'Vorige');
  } else {
    $args = array('.', 'ui-disabled', 'Vorige');
  }

  vprintf($fmt, $args);
?>
    </div>
    <div class="ui-block-b">
<?php
  $fmt = '<a href="%s" class="%s" data-role="button" data-icon="ui-icon-arrow-r" rel="external">%s</a>';
  if ($nextPage > 0) {
    $href = sprintf(
      '?direction?next&pagenr=%u%s%s#spots',
      $nextPage,
      $tplHelper->convertSortToQueryParams(),
      $tplHelper->convertFilterToQueryParams()
    );
    $args = array($href, '', 'Volgende');
  } else {
    $args = array('.', 'ui-disabled', 'Volgende');
  }

  vprintf($fmt, $args);
?>
    </div>
  </fieldset>
</div>
<?php
/* Render de footer template */
require_once __DIR__ . '/footer.inc.php';
