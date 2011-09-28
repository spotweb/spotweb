<?php
$n = 1;
$i = 1234;

while($n <= 5)
{
echo "<tr>";
echo "<form action=\"lrac.php\" method=\"post\">";
echo "<td name=\"title\">";
echo "<input type=\"hidden\" name=\"title\" value=\"Test #" . $n . ", wordt een script\">Test #" . $n . ", wordt een script</td>";
echo "<td name=\"spotid\">" . $i . "</td>";
echo "<td name=\"repid\">" . $n . "</td>";
echo "<td name=\"spotloc\">" . $i . "@spotnet.nl</td>";
echo "<td><select name=\"opt\"><option value=\"rems\">Remove Spot</option><option value=\"remr\">Ignore Report</option></select></td><td><input type=\"submit\" value=\"Ok\"></td>";
echo "</form>";
echo "</tr>";
$n++;
$i += 1111;
}
?>
