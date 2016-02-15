<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);


require 'lib/firebaseInterface.php';
require 'lib/firebaseLib.php';
require 'lib/firebaseStub.php';

const NEST_URL = 'https://developer-api.nest.com';
const NEST_PATH = '/';

$connection = new MongoClient();
$shorelogger = $connection->selectDB('thermostatdb');
$nest_data = $shorelogger->nestdata;
$wunderground_data = $shorelogger->wunderground_data;
$settings = $shorelogger->settings;

$nest_token = $settings->findOne(array('key' => 'nest_token'));

$atfull = json_decode($nest_token["value"]);
$nest_token = $atfull->access_token;

if(is_null($nest_token)) : ?>
	<form method="POST" action="./auth.php">
		Enter PIN: <input name="pin">
	</form>
<?php
endif;

$nest_api = new \Firebase\FirebaseLib(NEST_URL, $nest_token);

$nest_response_json = $nest_api->get(NEST_PATH);
$nest_response = json_decode($nest_response_json, true);

var_dump($nest_response);
if($nest_response) {

	$nest_data->save($nest_response);
}

$wunderground_api_url = 'http://api.wunderground.com/api/dd3736b699396568/conditions/q/NJ/Brigantine.json';
  // create curl resource 
        $ch = curl_init(); 

        // set url 
        curl_setopt($ch, CURLOPT_URL, $wunderground_api_url); 
        //return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        // $output contains the output string 
        $output = curl_exec($ch); 
        // close curl resource to free up system resources 
        curl_close($ch);     


 if ($output) {
 	$wunderground_data->save(json_decode($output, true));
 }       

var_dump($output);
?>
