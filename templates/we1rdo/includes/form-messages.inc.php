<?php
	echo "<ul class='formerrors'>";
	foreach($formmessages['errors'] as $formError) {
		echo "<li>" . $tplHelper->formMessageToString($formError) . "</li>";
	} # foreach
	echo "</ul>";

	echo "<ul class='forminformation'>";
	foreach($formmessages['info'] as $formInfo) {
		echo "<li>" . $tplHelper->formMessageToString($formInfo) . "</li>";
	} # foreach
	echo "</ul>";
