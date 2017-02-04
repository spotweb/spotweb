<?php
ini_set("display_errors", "on");
require "class.nma.php";
define("DEMO_EOL",isset($_SERVER['HTTP_USER_AGENT']) ? "<br />" : "\n");

$nma = new NotifyMyAndroid(); // Default creator
// For a more detailed creator, use the signature NotifyMyAndroid($apikey=null, $verify=false, $devkey=null, $proxy=null, $userpwd=null)

$nma_params = array(
	'apikey' => 'YOUR_API_KEY_HERE', // User API Key. CHANGE THIS TO YOUR API KEY
	'priority' => 0,				// Range from -2 to 2.
	'application' => 'Application name here', // Name of the app.
	'event' => 'Some event title.',		// Name of the event.
	'description' => 'Some description text of the notification.' // Description of the event.
);

// Verify the APIKEY, this step is NOT necessary to send a notification
// Example code to verify if a key is valid
if( !$nma->verify($nma_params['apikey']) ) { 
	echo $nma->getError() . "\n";
} else {
	echo "APIKEY is valid!\n";
}

// Example code to send the notification
if( !$nma->push( $nma_params ) ) {
	echo $nma->getError() . "\n";
} else {
	echo "Notification sent!\n";
}

?>
