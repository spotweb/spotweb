<?php echo '<?xml version="1.0" ?>', "\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
 <title>PEAR::Net_NNTP Demo</title>
 <link rel="stylesheet" href="./style.css" type="text/css" />
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body id="<?php echo $bodyID; ?>">

<table id="page-header" width="100%" border="0" cellspacing="0" cellpadding="0">
 <tr class="header">
  <td align="left" valign="middle"><a href="./index.php"><img src="http://pear.php.net/gifs/pearsmall.gif" border="0" style="margin: 5px;" width="104" height="50" alt="PEAR" /></a></td>
  <td align="left" valign="middle"><h1>Net_NNTP Demo</h1></td>
  <td align="right" valign="bottom">powered by <a href="http://pear.php.net/">PEAR</a>::<a href="http://pear.php.net/package/Net_NNTP/">Net_NNTP</a> v<?php echo @Net_NNTP_Client::getPackageVersion(); ?></td>
 </tr>
 <tr class="subheader">
  <td colspan="3"><img src="" width="1" height="1" alt="" /></td>
 </tr>
</table>

<div id="page-breadcrumbs">
<?php

if (!empty($breadcrumbs)) {
    $crumbs = array();
    foreach ($breadcrumbs as $title => $link) {
        if (empty($link)) {
    	    $crumbs[] = "<span class=\"crumb\">$title</span>";
        } else {
            $crumbs[] = "<a class=\"crumb\" href=\"$link\">$title</a>";
        }
    }
    echo implode(" > \r\n", $crumbs), "\r\n";
}

?>
</div>

