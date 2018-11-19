<?php

add_action( 'wp_ajax_reset_live', 'reset_live' );
function reset_live() {
	$reset_time_to = $_POST['timestamp'];
	$livePageObject = get_page_by_path('live');
	$liveID = $livePageObject->ID;
	update_post_meta($liveID, 'liveResetTime', $reset_time_to);
	echo json_encode($reset_time_to);
	wp_die();
}

add_action( 'wp_ajax_share_twitch_user_db', 'share_twitch_user_db' );
function share_twitch_user_db() {
	// $twitchUserDB = buildFreshTwitchUserDB();
	$twitchDB = buildFreshTwitchDB();
	killAjaxFunction($twitchDB);
}

add_action( 'wp_ajax_notify_of_participation', 'notify_of_participation' );
function notify_of_participation() {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}

	$participant = $_POST['messageSender'];

	$person = getPersonInDB($participant);
	if (!$person) {
		killAjaxFunction("You don't have rep yet.");
		return;
	}

	$userArray = array(
		'twitchName' => 'Rocket_Dailies', 
		'lastRepTime' => 0,
	);
	editPersonInDB($userArray);
	$getsRep = checkForRepIncrease($participant);

	if ($getsRep) {
		$returnText = $getsRep;
	} else {
		$returnText = $participant . " didn't get any rep.";
	}

	killAjaxFunction($returnText);
}

add_action( 'wp_ajax_markTwitchBot', 'markTwitchBot' );
function markTwitchBot() {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}
	$twitchName = $_POST['twitchName'];
	$botlist = getBotlist();
	if (in_array($twitchName, $botlist)) {
		$botIndex = array_search($twitchName, $botlist);
		array_splice($botlist, $botIndex, 1);
	} else {
		$botlist[] = $twitchName;
	}
	$viewersPageID = getPageIDBySlug('viewers');
	update_post_meta($viewersPageID, 'bots', $botlist);
	killAjaxFunction($twitchName);
}

function getBotlist() {
	$viewersPageID = getPageIDBySlug('viewers');
	$botlist = get_post_meta($viewersPageID, 'bots', true);
	if ($botlist === '') {$botlist = [];}
	return $botlist;
}

add_action( 'wp_ajax_specialButtonHandler', 'specialButtonHandler' );
function specialButtonHandler() {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}
	$twitchName = $_POST['twitchName'];
	togglePersonSpecialness($twitchName);
	killAjaxFunction($twitchName);
}

?>