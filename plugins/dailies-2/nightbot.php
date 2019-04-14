<?php

global $privateData;
$nightbotNewCodeURL = "https://api.nightbot.tv/oauth2/authorize?response_type=code&client_id=" . $privateData['nightbotClientID'] . "&redirect_uri=https%3A%2F%2FDailies.gg&scope=channel_send";

function get_nightbot_token() {
	global $privateData;

	$url = "https://api.nightbot.tv/oauth2/token";

	$args = array(
		"body" => array(
			"client_id" => $privateData['nightbotClientID'],
			"client_secret" => $privateData['nightbotClientSecret'],
			"code" => $privateData['nightbotAuthorizationCode'],
			"grant_type" => "authorization_code",
			"redirect_uri" => "https://Dailies.gg",
		),
	);

	$response = wp_remote_post($url, $args);
	basicPrint($response['body']);
}

function refresh_nightbot_token() {
	global $privateData;

	$url = "https://api.nightbot.tv/oauth2/token";

	$args = array(
		"body" => array(
			"client_id" => $privateData['nightbotClientID'],
			"client_secret" => $privateData['nightbotClientSecret'],
			"refresh_token" => $privateData['nightbotRefreshToken'],
			"grant_type" => "refresh_token",
			"redirect_uri" => "https://Dailies.gg",
		),
	);

	$response = wp_remote_post($url, $args);
	basicPrint($response['body']);
}

function send_nightbot_message($messageText) {
	global $privateData;

	$url = "https://api.nightbot.tv/1/channel/send";

	$args = array(
		"headers" => array(
			"Authorization" => "Bearer " . $privateData['nightbotAccessToken'],
		),
		"body" => array(
			"message" => $messageText,
		),
	);

	$response = wp_remote_post($url, $args);
}

?>