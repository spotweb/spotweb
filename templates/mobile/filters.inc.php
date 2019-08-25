<?php
$setpath = $tplHelper->makeBaseUrl("path");
$sortType = $currentSession['user']['prefs']['defaultsortfield'];

function page_header($page='search')
{
  $session = $GLOBALS['currentSession'];
  $settings = $GLOBALS['settings'];

  $tabs = array(
    'spots' => array('icon' => 'ui-icon-grid', 'title' => 'Spots'),
    'search' => array('icon' => 'ui-icon-search', 'title' => 'Search'),
    'filters' => array('icon' => 'ui-icon-star', 'title' => 'Filters')
  );

?>
<div data-role="toolbar" data-type="header" data-theme="b">
<h1>Spotweb</h1>
<?php
  $clss = array(
    'ui-toolbar-header-button-right',
    'ui-button-inline',
    'ui-mini',
    'ui-corner-all'
  );

  $fmt = '<div data-role="controlgroup" data-type="horizontal" class="%s">';
  printf("$fmt\n", join(' ', $clss));

  if ($page === 'spots') {
    $fmt = '<a href="%s" data-theme="b" rel="external">Reset filter <span class="ui-icon ui-icon-refresh" /></a>';
    printf("$fmt\n", '.');
  }

  if ($session['user']['userid'] !== $settings->get('nonauthenticated_userid')) {
    $fmt = '<a href="#" data-theme="b" id="anchorLoginControl">Log out <span class="ui-icon ui-icon-user"></span></a>';
    printf("$fmt\n");
  }

  printf("</div>\n");

?>
  <div data-role="navbar">
    <ul>
<?php
  $fmt = '<li><a href="#%s" class="%s" data-icon="%s" data-theme="b">%s</a></li>';
  foreach($tabs as $key => $tab) {
    $class = $key == $page ? 'ui-button-active' : '';
    printf("      $fmt\n", $key, $class, $tab['icon'], $tab['title']);
  }
?>
    </ul>
  </div>
</div>
<?php
}

?>
<div data-role="page" id="search">
<?php page_header('search'); ?>
<div data-role="main" class="ui-content">
  <form id="filterform" class="ui-grid-b" action="?page=search#spots" method="get" data-ajax="false">
    <div data-role="controlgroup" class="ui-block-a">
      <fieldset data-role="controlgroup" data-type="horizontal">
        <input type="radio" id="radio-choice-1a" name="sortby" value="" <?php echo $sortType == "" ? 'checked="checked"' : "" ?>>
        <label for="radio-choice-1a"><?php echo _('Relevance'); ?></label>
        <input type="radio" id="radio-choice-1b" name="sortby" value="stamp" <?php echo $sortType == "stamp" ? 'checked="checked"' : "" ?>>
        <label for="radio-choice-1b"><?php echo _('Date');?></label>
      </fieldset>
    </div>
    <div data-role="controlgroup" class="ui-block-a">
      <fieldset data-role="controlgroup" data-type="horizontal">
        <input type="radio" name="search[type]" value="Titel" id="radio-choice-2a" checked="checked" />
        <label for="radio-choice-2a">Titel</label>
        <input type="radio" name="search[type]" value="Poster" id="radio-choice-2b" />
        <label for="radio-choice-2b">Poster</label>
        <input type="radio" name="search[type]" value="Tag" id="radio-choice-2c" />
        <label for="radio-choice-2c">Tag</label>
      </fieldset>
    </div>
    <div class="ui-block-a">
      <input type="search" type="text" name="search[text]" value="" />
    </div>
  </form>
</div>
</div>
<div data-role="page" id="filters">
<?php page_header('filters'); ?>
<div data-role="main" class="ui-content">
  <ul data-role="listview">
<?php
function print_filters($tplHelper, $filterList, $defaultSortField) {
  $selfUrl = $tplHelper->makeSelfUrl("path");

  foreach($filterList as $filter) {
    $strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $filter['tree'];
    if (!empty($filter['valuelist'])) {
      foreach($filter['valuelist'] as $value) {
        $strFilter .= '&search[value][]=' . $value;
      }
    }

    if (!empty($filter['sorton'])) {
      $strFilter .= '&sortby=' . $filter['sorton'] . '&sortdir=' . $filter['sortorder'];
    } else {
      $sortType = $defaultSortField;
    }

    /* escape filter values */
    $filter['title'] = htmlentities($filter['title'], ENT_NOQUOTES, 'UTF-8');
    $filter['icon'] = htmlentities($filter['icon'], ENT_NOQUOTES, 'UTF-8');
    $img = "templates/mobile/icons/" . $filter['icon'] . ".png";
    $style = "margin: 1px 1em 0 0";

    if(empty($filter['children'])) {
      $fmt = '<li><a href="%s" rel="external"><img src="%s" class="ui-li-icon" style="%s" />%s</a>';
      $args = array($strFilter . '#spots', $img, $style, $filter['title']);
    } else {
      $fmt = '<li><img src="%s" class="ui-li-icon" style="%s" />%s';
      $args = array($img, $style, $filter['title']);
    }

    vprintf("$fmt\n", $args);

    /* Nested listviews have been deprecated in jQuery 1.3 and removed in
     * jQuery 1.4. A plugin can be used to restore the functionality.
     * See: https://demos.jquerymobile.com/1.4.0/listview-nested-lists/
     */
    if (!empty($filter['children'])) {
      $fmt = '<ul data-role="listview">';
      printf("$fmt\n");
      print_filters($tplHelper, $filter['children'], $defaultSortField);
      /* An icon is not automatically appended to <ul> elements. */
      $fmt = '</ul><span class="%s"></span>';
      $clss = array(
        'ui-listview-item-icon',
        'ui-icon',
        'ui-icon-caret-r',
        'ui-widget-icon-floatend'
      );
      printf("$fmt\n", join(' ', $clss));
    }
    print("</li>\n");
  }
}

print_filters($tplHelper, $filters, $currentSession['user']['prefs']['defaultsortfield']);
?>
  </ul>
</div>
</div>
