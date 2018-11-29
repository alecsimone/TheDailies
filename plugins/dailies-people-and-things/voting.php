<?php

//Top Level Functions
// function vote($person, $thing) {
// 	$hasVoted = checkIfPersonHasVoted($person, $thing);
// 	if (!$hasVoted) {
// 		$person = getPersonInDB($person);
// 		if ($person) {
// 			$rep = getValidRep($person['hash']);
// 			changeVotecount($rep, $thing);
// 			$voteledger = getValidVoteledger($thing);
// 			$voteledger[$person['hash']] = $rep;
// 			update_post_meta($thing, 'voteledger', $voteledger);
// 			addVoteToHistory($person, $thing);
// 			checkForRepIncrease($person);
// 		} else {
// 			changeVotecount(1, $thing);
// 			addCurrentGuestToGuestlist($thing);
// 		}
// 	} else {
// 		unvote($person, $thing);
// 	}
// 	buildPostDataObject($thing);
// }

//Getters and Setters


function getValidVoteledger($postID) {
	$voteledger = get_post_meta($postID, 'voteledger', true);
	if ($voteLedger === '') {
		$voteLedger = [];
	}
	return $voteledger;
}

function getValidGuestlist($postID) {
	$guestlist = get_post_meta($postID, 'guestlist', true);
	if ($voteLedger = '') {
		$guestlist = [];
	}
	return $guestlist;
}

function addVoteToHistory($person, $thing) {
	if ($person === false) {
		return;
	}
	$voteArray = prepareVoteHistoryArray($person, $thing);
	global $wpdb;
	$table_name = $wpdb->prefix . 'vote_history_db';
	$hash = $voteArray['hash'];
	$existingVote = $wpdb->get_row(
		"SELECT * 
		FROM $table_name 
		WHERE hash = '$hash' AND postid = '$thing'",
		OBJECT
	);
	if ($existingVote !== null) {
		return;
	}
	$wpdb->insert(
		$table_name,
		$voteArray
	);
}

function deleteVoteFromHistory($person, $thing) {
	if ($person === false) {
		return;
	}
	$voteArray = prepareVoteHistoryArray($person, $thing);
	global $wpdb;
	$table_name = $wpdb->prefix . 'vote_history_db';
	$wpdb->delete(
        $table_name,
        $voteArray
    );
}

//Accessory functions
function checkIfPersonHasVoted($person, $thing) {
	$hasVoted = false;
	$person = getPersonInDB($person);
	$ledger = getValidVoteledger($thing);
	$twitchVoters = getValidTwitchVoters($thing);
	$guestlist = get_post_meta($thing, 'guestlist', true);
	if (isset($person['hash'])) {
		if (array_key_exists($person['hash'], $ledger)) {
			$hasVoted = true;
		}
	}
	if (isset($person['dailiesID'])) {
		if (array_key_exists($person['dailiesID'], $ledger)) {
			$hasVoted = true;
		}
	}
	if (isset($person['twitchName'])) {
		if (array_key_exists($person['twitchName'], $twitchVoters)) {
			$hasVoted = true;
		}
	}
	$ip = $_SERVER['REMOTE_ADDR'];
	if (in_array($ip, $guestlist)) {
		$hasVoted = true;
	}
	return $hasVoted;
}

function unvote($person, $thing) {
	$person = getPersonInDB($person);

	$ledger = getValidVoteledger($thing);
	if (array_key_exists($person['hash'], $ledger)) {
		changeVotecount(-$ledger[$person['hash']], $thing);
		unset($ledger[$person['hash']]);
	}
	if (array_key_exists($person['dailiesID'], $ledger)) {
		changeVotecount(-$ledger[$person['dailiesID']], $thing);
		unset($ledger[$person['dailiesID']]);
	}
	update_post_meta($thing, 'voteLedger', $ledger);
	
	$twitchVoters = getValidTwitchVoters($thing);
	if (array_key_exists($person['twitchName'], $ledger)) {
		changeVotecount(-1, $thing);
		unset($twitchVoters[$person['twitchName']]);
	}
	update_post_meta($thing, 'twitchVoters', $twitchVoters);
	
	$guestlist = get_post_meta($thing, 'guestlist', true);
	$ip = $_SERVER['REMOTE_ADDR'];
	if (in_array($ip, $guestlist)) {
		$guestKey = array_search($ip, $guestlist);
		unset($guestlist[$guestKey]);
		changeVotecount(-1, $thing);
	}
	update_post_meta($thing, 'guestlist', $guestlist);

	deleteVoteFromHistory($person, $thing);
}

function checkForRepIncrease($person) {
	$person = getPersonInDB($person);
	$lastNomTime = ensureTimestampInSeconds(getLastNomTimestamp());
	$lastRepTime = ensureTimestampInSeconds($person['lastRepTime']);
	$deservesNewRep = false;
	if ($lastRepTime <= $lastNomTime) {
		$newRep = increase_rep($person['hash'], 1);
		updateRepTime($person['hash']);
		$deservesNewRep = true;
	}
	if ($deservesNewRep) {
		return $newRep;
	} else {
		return false;
	}
}

function absorb_votes($postID) {
	$currentVotersList = getCurrentVotersList();
	$twitchUserDB = getTwitchUserDB();

	foreach ($currentVotersList['yea'] as $index => $twitchName) {
		$voter = getPersonInDB($twitchName);
		if ($voter) {
			$hasVoted = checkIfPersonHasVoted($twitchName, $postID);
			if ($hasVoted) {
				continue;
			}
			vote($twitchName, $postID);
		} else {
			$userArray = array(
				'twitchName' => $twitchName,
			);
			addPersonToDB($userArray);
			vote($twitchName, $postID);	
		}
	}
	foreach ($currentVotersList['nay'] as $index => $twitchName) {
		$voter = getPersonInDB($twitchName);
		if ($voter) {
			$hasVoted = checkIfPersonHasVoted($twitchName, $postID);
			if (!$hasVoted) {
				continue;
			}
			vote($twitchName, $postID);
		}
	}
	internal_reset_chat_votes();
	return;
}



function prepareVoteHistoryArray($person, $thing) {
	$voteArray = array(
		'postid' => $thing,
	);
	if (checkIfStringIsHashOrTwitchName($person) === 'hash') {
		$voteArray['hash'] = $person;
	} else {
		$personRow = getPersonInDB($person);
		$voteArray['hash'] = $personRow['hash'];
	}
	return $voteArray;
}

function getPostIDForVoteNumber($voteNumber) {
	$postDataArray = getLiveContenders();
	$voteIndex = $voteNumber - 1;
	if (!voteChoiceIsValid($voteIndex, $postDataArray)) {
		return false;
	} else {
		return $postDataArray[$voteIndex]->ID;
	}
}

function voteChoiceIsValid($voteChoice, $postDataArray) {
	$postCount = count($postDataArray);
	if ($voteChoice > $postCount) {
		return false;
	} else {
		return true;
	}
}

function changeVotecount($amountToChange, $postID) {
	$currentScore = get_post_meta($postID, 'votecount', true);
	$newScore = $currentScore + $amountToChange;
	update_post_meta($postID, 'votecount', $newScore);
}

function addCurrentGuestToGuestlist($postID) {
	$clientIP = $_SERVER['REMOTE_ADDR'];
	$guestlist = get_post_meta($postID, 'guestlist', true);
	$guestlist[] = $clientIP;
	update_post_meta($postID, 'guestlist', $guestlist);
}

function removeCurrentGuestFromGuestlists($postID) {
	$clientIP = $_SERVER['REMOTE_ADDR'];
	$guestlist = get_post_meta($postID, 'guestlist', true);
	$guestKey = array_search($clientIP, $guestlist);
	unset($guestlist[$guestKey]);
	update_post_meta($postID, 'guestlist', $guestlist);
}

//AJAX Handlers




add_action( 'wp_ajax_handle_vote', 'handle_vote' );
add_action( 'wp_ajax_nopriv_handle_vote', 'handle_vote' );
function handle_vote() {
	// $nonce = $_POST['vote_nonce'];
	// if (!wp_verify_nonce($nonce, 'vote_nonce')) {
	// 	die("Busted!");
	// }
	$thing = $_POST['id'];

	if (is_user_logged_in()) {
		$person = get_current_user_id();
	} else {
		$person = false;
	}
	vote($person, $thing);

	echo json_encode('voted!');
	wp_die();
}

function internal_reset_chat_votes() {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}

	$currentVotersList['yea'] = [];
	$currentVotersList['nay'] = [];

	updateCurrentVotersList($currentVotersList);
}

add_action( 'wp_ajax_get_chat_votes', 'get_chat_votes' );
add_action( 'wp_ajax_nopriv_get_chat_votes', 'get_chat_votes' );
function get_chat_votes() {
	$currentVotersList = getCurrentVotersList();
	killAjaxFunction($currentVotersList);
}

?>