<?php
$spot = $tplHelper->formatSpot($spot);
$comments = $tplHelper->getSpotComments($spot['messageid'], $spot['prevMsgids'], 0, 99999);
$comments = $tplHelper->formatComments($comments);

$setpath = $tplHelper->makeBaseUrl("path");

// fix the sabnzbdurl en searchurl
$spot['sabnzbdurl'] = $tplHelper->makeSabnzbdUrl($spot);
$spot['searchurl'] = $tplHelper->makeSearchUrl($spot);
?>
<div data-role="page" id="spots">
<div data-role="toolbar" data-type="header" data-theme="b">
  <h1>Spot info</h1>
<?php
  $fmt = '<a href="%s" data-transition="fade" data-rel="dialog" data-icon="plus" class="%s">%s</a>';
  $clss = array(
    'ui-toolbar-header-button-right',
    'ui-button',
    'ui-button-inline',
    'ui-mini',
    'ui-corner-all'
  );

  if(!empty($spot['sabnzbdurl'])) {
    $href = str_replace('getnzb', 'getnzbmobile', $spot['sabnzbdurl']);
    $href = preg_replace('/^[^\?]*/', '', $href);
    $label = 'SAVE';
  } else {
    $href = '?page=getnzbmobile&messageid=' . $spot['messageid'];
    $label = 'NZB';
  }

  printf($fmt, $href, join(' ', $clss), $label);
?>
</div>
<div data-role="main" class="ui-content">
  <img class="spotinfoimage" src="<?php echo $tplHelper->makeImageUrl($spot, 150, 150); ?>" height="150">
  <p><?php if(!$spot['verified']) print('* niet geverifieerd *'); ?></p>
  <h3><?php print($spot['title']); ?></h3>
  <table class="spotinfo">
    <tbody>
      <tr>
        <th>Categorie</th>
        <td><?php print($spot['catname']); ?></td>
      </tr>
<?php
  if (!empty($spot['subcatlist'])) {
    foreach($spot['subcatlist'] as $sub) {
      $fmt = '<tr><th>%s</th><td>%s</td></tr>';
      printf(
        "$fmt\n",
        SpotCategories::SubcatDescription($spot['category'], substr($sub,0,1)),
        SpotCategories::Cat2Desc($spot['category'], $sub)
      );
    }
  }
?>
      <tr>
        <th>Omvang</th>
        <td><?php echo $tplHelper->format_size($spot['filesize']); ?></td>
      </tr>
      <tr>
        <th>Website</th>
        <td><a href='<?php echo $spot['website']; ?>' target="_blank">BEKIJK</a></td>
      </tr>
      <tr>
        <th>Afzender</th>
        <td><?php echo $spot['poster']; ?> (<?php echo $spot['spotterid']; ?>)</td>
      </tr>
      <tr>
        <th>Tag</th>
        <td><?php echo $spot['tag']; ?></td>
      </tr>
      <tr>
        <th>Zoekmachine</th>
        <td><a href='<?php echo $spot['searchurl']; ?>'>Zoek</a></td>
      </tr>
      <tr>
        <th>NZB</th>
        <td><a href='<?php echo $setpath; ?>?page=getnzb&amp;messageid=<?php echo $spot['messageid']; ?>'>NZB</a></td>
      </tr>
    </tbody>
  </table>
  <h4>Omschrijving</h4>
  <p>
<?php
  $exprs = array(
    '#\[\/?(b|i|u|br|strong)\]#',
    '#\<b\>#',
    '#\&lt;\s*br\s*/?\&gt;#'
  );
  $repls = array('', '', '<br/>');

  $desc = preg_replace($exprs, $repls, $spot['description']);
  print($desc);
?>
  </p>
<?php if (sizeof($comments) > 0) { ?>
  <!-- comments -->
  <div data-role="collapsedset" data-inset="false">
    <div data-role="collapsible" data-collapsed="true">
      <h4>Comments</h4>
      <ul data-role="listview" data-inset="false">
<?php
  foreach($comments as $comment) {
    $fmt = '<li><h5>%s @ %s</h5><span>%s</span></li>';
    printf(
      "$fmt\n",
      $comment['fromhdr'],
      $tplHelper->formatDate($comment['stamp'], 'comment'),
      $comment['body']
    );
  }
?>
      </ul>
    </div>
  </div>
  <!-- /comments -->
<?php } ?>
</div>
</div>

