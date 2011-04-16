<?php
	echo "<ul class='formerrors'>";
	foreach($formmessages['errors'] as $formError) {
		echo "<li>" . $formError . "</li>";
	} # foreach
	echo "</ul>";

	echo "<ul class='forminformation'>";
	foreach($formmessages['info'] as $formInfo) {
		echo "<li>" . $formInfo . "</li>";
	} # foreach
	echo "</ul>";
