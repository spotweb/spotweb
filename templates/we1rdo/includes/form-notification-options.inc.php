<?php
	function showNotificationOptions($provider, $edituserprefsform, $tplHelper) {
		echo "<fieldset>" . PHP_EOL;

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'watchlist_handled')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][watchlist_handled]\">Bericht versturen wanneer een spot is toegevoegd aan of verwijderd van de watchlist?</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][watchlist_handled]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['watchlist_handled']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'nzb_handled')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][nzb_handled]\">Bericht versturen wanneer een NZB is verzonden? Werkt niet voor client-sabnzbd.</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][nzb_handled]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['nzb_handled']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'retriever_finished')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][retriever_finished]\">Bericht versturen wanneer Spots Updaten klaar is?</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][retriever_finished]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['retriever_finished']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		if ($tplHelper->allowed(SpotSecurity::spotsec_send_notifications_types, 'user_added')) {
			echo "<dt><label for=\"edituserprefsform[notifications][" . $provider . "][events][user_added]\">Bericht versturen wanneer een gebruiker is toegevoegd?</label></dt>" . PHP_EOL;
			echo "<dd><input type=\"checkbox\" name=\"edituserprefsform[notifications][" . $provider . "][events][user_added]\"";
			if ($edituserprefsform['notifications'][$provider]['events']['user_added']) {
				echo "checked=\"checked\"";
			} # if
			echo "></dd>" . PHP_EOL . PHP_EOL;
		} # if

		echo "</fieldset>" . PHP_EOL;
	} # notificationOptions