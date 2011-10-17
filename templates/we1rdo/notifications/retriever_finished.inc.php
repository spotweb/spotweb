Nieuwe spots opgehaald

Er <?php
echo ($newSpotCount == 1) ? "is " . $newSpotCount . " spot" : "zijn " . $newSpotCount . " spots";
if ($newCommentCount > 0) {
	echo ($newReportCount > 0) ? ", " : " en ";
	echo $newCommentCount;
	echo ($newCommentCount == 1) ? " reactie" : " reacties";
} # if
if ($newReportCount > 0) {
	echo " en " . $newReportCount;
	echo ($newCommentCount == 1) ? " reports" : " reports";
} # if
?> opgehaald.