<?php
ini_set("display_errors", "on");
require "class.nma.php";
define("DEMO_EOL",isset($_SERVER['HTTP_USER_AGENT']) ? "<br />" : "\n");

$nma = new NotifyMyAndroid(); // Default creator
// For a more detailed creator, use the signature NotifyMyAndroid($apikey=null, $verify=false, $devkey=null, $proxy=null, $userpwd=null)

$nma_params = array(
			'apikey' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',				// User API Key. CHANGE THIS TO YOUR API KEY
			//'developerkey' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',		// Developer key. If you have one.
			'priority' => 0,																// Range from -2 to 2.
			'application' => 'Application name here',    								// Name of the app.
			'event' => 'Some event title.',											// Name of the event.
			'description' => 'Some description text of the notification.'				// Description of the event.
);

//Verify the APIKEY, this step is not necessary to send a notification
if( !$nma->verify('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa') ) { // CHANGE TO YOUR API KEY
	echo $nma->getError() . "\n";
} else {
	echo "APIKEY is valid!\n";
}

//Send the notification
if( !$nma->push( $nma_params ) ) {
	echo $nma->getError() . "\n";
} else {
	echo "Notification sent!\n";
}

?>
