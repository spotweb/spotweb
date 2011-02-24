			<div class="spotinfocontainer">
				<br>
				<br>
<?php
	# Function from http://www.php.net/manual/en/function.filesize.php#99333
	function format_size($size) {
		  $sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
		  if ($size == 0) { return('n/a'); } else {
		  return (round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i]); }
	} # format_size
	
	# fix up the category number
	$hcat = ((int) $spot['category']) - 1;
	
	# and display the image and website
	if (!empty($spot['website'])) {
		echo "\t\t\t\t" . '<a href="' . $spot['website'] . '"><img class="spotinfoimage" src="' . $spot['image'] . '"></a>';
	} # if

	# display the download button
	echo "\t\t\t\t<a href='?page=getnzb&amp;messageid=" . $spot['messageid'] . "'><img style='float: right;' src='images/download3.png'></a>";

	# and fixup the descrition text
	$tmp = $spot['description'];
	
	$tmp = str_replace('[b]', '<b>', $tmp);
	$tmp = str_replace('[/b]', '</b>', $tmp);
	$tmp = str_replace('[i]', '<i>', $tmp);
	$tmp = str_replace('[/i]', '</i>', $tmp);
	$tmp = str_replace('[br]', '<br>', $tmp);
	$tmp = str_replace('[u]', '<u>', $tmp);
	$tmp = str_replace('[/u]', '</u>', $tmp);
	$tmp = str_replace('&lt;br&gt;', '<br>', $tmp);
	$tmp = str_replace('&lt;br /&gt;', '<br>', $tmp);
	echo $tmp;
?>
	
				<br> 
				<br>
				<br class="spotinfoclear">
				<br> 
				<br> 
					
				<table class="spotinfotable">
					<tr> <th> Categorie </th> <td> <?php echo SpotCategories::HeadCat2Desc($hcat); ?> </td> </tr>
		
<?php
	if (isset($spot['sub'])) {
		foreach($spot['sub'] as $sub) {
			$tmp = array();
			$tmp[0] = substr($sub, 2, 1);
			$tmp[1] = $tmp[0] . (int) substr($sub, 3) . '|';
			
			echo "\t\t\t\t\t<tr> <th> " . SpotCategories::SubcatDescription($hcat, $tmp[0]) .  "</th> <td> " . SpotCategories::Cat2Desc($hcat, $tmp[1]) . " </td> </tr>\r\n";
		} # foreach
	} # if
?>	
					<tr> <th> Omvang </th> <td> <?php echo format_size($spot['size']); ?> </td> </tr>
	
					<tr> <td colspan="2"> &nbsp;  </td> </tr>
		
<?php
	if (!empty($spot['website'])) {
		echo "\t\t\t\t<tr> <th> Website </th> <td> <a href='" . $spot['website'] . "'>" . $spot['website'] . "</a> </td> </tr>";
	}
?>
					<tr> <td colspan="2"> &nbsp;  </td> </tr>
					<tr> <th> Afzender </th> <td> <?php echo $spot['poster']; ?> </td> </tr>
					<tr> <th> Tag </th> <td> <?php if (isset($spot['tag'])) { echo $spot['tag']; } ?> </td> </tr>
					<tr> <th colspan="2"> <a href='?page=getnzb&amp;messageid=<?php echo $spot['messageid'] ?>'>Download</a> </th> </tr>

				</table>
			
				<br class="spotinfoclear">
				<br> 
				<br>
				<br>
	
			</div>	
