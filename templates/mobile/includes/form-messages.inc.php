<?php
	echo "<ul class='formerrors'>";
	if (isset($formmessages['errors'])) {
		foreach($formmessages['errors'] as $formError) {
			echo "<li>" . $formError . "</li>";
		} # foreach
	} # if
	echo "</ul>";

	echo "<ul class='forminformation'>";
	if (isset($formmessages['info'])) {
		foreach($formmessages['info'] as $formInfo) {
			echo "<li>" . $formInfo . "</li>";
		} # foreach
	} # if
	echo "</ul>";
