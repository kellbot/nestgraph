<?php

$pin = $_POST['pin'];
include('keys.php');

$access_token_url = 'https://api.home.nest.com/oauth2/access_token';

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $access_token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, "1");
curl_setopt($ch, CURLOPT_POSTFIELDS, 'client_id='.$client_id.'&code='.$pin.'&client_secret='.$client_secret.'&grant_type=authorization_code');

$server_output = curl_exec($ch);

$nest_token = array('key' => 'nest_token', 'value' => $server_output);

if(!is_array($nest_token)) {
	$connection = new MongoClient();
	$shorelogger = $connection->selectDB('thermostatdb');
	$settings = $shorelogger->settings;
	$settings->save($nest_token);
}

var_dump($nest_token);

echo 'done';
