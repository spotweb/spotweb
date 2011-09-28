<html>
<head>
<link rel="stylesheet" type="text/css" href="mod/style.css" />
</head>
<body>
<h3>Welcome at The Moderator Page</h3>
<table class="quickfunc">
<tr><th colspan="3"><h3>Quick Functions</h3></th></tr>
<tr><td><input type="button" class="quickbutton" value="Get External Reports"></td><td><input type="button" class="quickbutton" value="Ban User"></td></tr>
<tr><td><input type="button" class="quickbutton" value="View External Reports"></td><td><input type="button" class="quickbutton" value="View Local Reports"></td></tr>
<tr><td><a href="templates/we1rdo/mod/rem1.html"><input type="button" class="quickbutton" value="Remove Spot"></td><td><a href="templates/we1rdo/mod/rem2.html"><input type="button" class="quickbutton" value="Remove User"></td><td></td></tr>
</table>

<table class="lastrep" cellspacing="5">
<tr><th span="5">Last five reports</th></tr>
<tr><th>Title</th><th>Spot ID</th><th>Report ID</th><th>NNTP Address</th></tr>
<?php
include_once "mod/last5.php";

?>
</table>
</body>
</html>
