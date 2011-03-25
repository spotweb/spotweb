  <table cellpadding="0" cellspacing="0" id="watchlist_table">
    <tr>
      <th class="title">Titel</th>
      <th class="date">Datum toegevoegd</th>
      <!--<th>Comment</th>-->
<?php if($settings['show_nzbbutton']) { ?>
      <th>NZB</th>
<?php } ?>
      <th class="delete">Verwijder</th>
      <th class="sab">SAB</th>
    </tr>
<?php
foreach($watchlist as $watch) {
	echo "    <tr class=\"spot\">
      <td class=\"title\"><a href=\"?page=getspot&messageid=".$watch['messageid']."\">".$watch['title']."</a></td>
      <td>".$tplHelper->formatDate($watch['dateadded'], 'watchlist') . "</td>
      <!--<td>".$watch['comment'] . "</td>-->";
if($settings['show_nzbbutton']) { 
    echo "      <td><a class=\"nzb\" href=\"?page=getnzb&amp;messageid=".$watch['messageid']."\" title=\"Download NZB\">NZB</a></td>";  
}
    echo "      <td><a href='?page=watchlist&amp;action=remove&messageid=".$watch['messageid']."'><img src=\"templates/splendid/img/delete.png\" alt=\"Verwijder uit watchlist\" title=\"Verwijder uit watchlist\" border=\"0\" /></a></td>
      <td align=\"center\"><a class=\"sabnzbd-button\" href=\"".$tplHelper->makeSabnzbdUrl($watch)."\" title=\"Add NZB to SabNZBd queue\"><img height=\"16\" width=\"16\" src=\"images/download-small.png\" class=\"sabnzbd-button\" border=\"0\"></a></td>
    </tr>\n";
} # foreach
?>
    <tr>
      <td colspan="5" class="shadow"><img src="templates/splendid/img/shadow.gif" width="100%" height="7" border="0" alt="" /></td>
    </tr>
  </table>