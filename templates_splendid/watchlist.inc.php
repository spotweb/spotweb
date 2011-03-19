    <table id="watchlist_table">
		<tr>
        	<th>Title</th>
            <th>Date added</th>
            <th>Comment</th>
            <th>Remove</th>
            <th>Download</th>
		</tr>
		
<?php
	foreach($watchlist as $watch) {
		echo "<tr> <td> ";
		echo $watch['title'];
		echo "</td> <td>";
		echo $tplHelper->formatDate($watch['dateadded'], 'watchlist') . "</td><td>";
		echo $watch['comment'] . "</td><td>";
		echo "<a href='?page=watchlist&amp;action=remove&messageid=" . $watch['messageid'] . "'>X</a></td><td>";
		echo "<a href='" . $tplHelper->makeSabnzbdUrl($watch) . "'>SAB</td><td>";
	} # foreach
?>

	</table>