<?php

function addVoteToDB($voteArray) {
	global $wpdb;
	$table_name = $wpdb->prefix . "vote_db";
	
	$existingVote = $wpdb->get_row(
		"SELECT *
		FROM $table_name
		WHERE hash = '{$voteArray['hash']}' AND slug = '{$voteArray['slug']}'",
		'ARRAY_A'
	);
	if ($existingVote) {
		if ($existingVote['weight'] == $voteArray['weight']) {
			$where = array(
				"hash" => $voteArray['hash'],
				"slug" => $voteArray['slug'],
			);
			$wpdb->delete($table_name, $where);
			return "That vote already exists!";
		}
		$where = array(
			"hash" => $voteArray['hash'],
			"slug" => $voteArray['slug'],
		);
		$wpdb->delete($table_name, $where);
	}

	$voteArray['time'] = time();

	$insertionSuccess = $wpdb->insert(
		$table_name,
		$voteArray
	);

	return $insertionSuccess;
}

add_action( 'wp_ajax_slug_vote', 'slug_vote' );
function slug_vote() {
	$person = getPersonInDB(get_current_user_id());

	if ($_POST['direction'] === "yea") {
		$weight = (int)$person['rep'];
	} elseif ($_POST['direction'] === "nay") {
		$weight = floatval(get_option("nayCoefficient")) * (int)$person['rep'] * -1;
	}

	$voterArray = array(
		"hash" => $person['hash'],
		"weight" => $weight,
		"slug" => $_POST['slug'],
	);
	$addVoteResult = addVoteToDB($voterArray);

	killAjaxFunction($addVoteResult);
}

function deleteSlugVote($slug, $hash) {
	global $wpdb;
	$table_name = $wpdb->prefix . "vote_db";

	$where = array(
		"hash" => $hash,
		"slug" => $slug,
	);

	$wpdb->delete($table_name, $where);
}

add_action( 'wp_ajax_chat_vote', 'chat_vote' );
function chat_vote() {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}
	$person = getPersonInDB($_POST['voter']);
	if ($person == null) {
		$person = array(
			"hash" => "norep" . $_POST['voter'],
			"rep" => 1,
		);
	}
	if ($_POST['direction'] === "yea") {
		$weight = (int)$person['rep'];
	} elseif ($_POST['direction'] === "nay") {
		$weight = floatval(get_option("nayCoefficient")) * (int)$person['rep'] * -1;
	}

	$voteArray = array(
		"hash" => $person['hash'],
		"weight" => $weight,
		"slug" => "live",
	);

	$addVoteResult = addVoteToDB($voteArray);
	
	killAjaxFunction($_POST['voter'] . ' voted ' . $_POST['direction']);
}

// function applyChatVote($voter, $direction) {
// 	$currentVotersList = getCurrentVotersList();
// 	$otherDirection = getOtherDirection($direction);
// 	if (!in_array($voter, $currentVotersList[$direction])) { 
// 		$currentVotersList[$direction][] = $voter;
// 	}
// 	if (in_array($voter, $currentVotersList[$otherDirection])) {
// 		$ourVoterKey = array_search($voter, $currentVotersList[$otherDirection]);
// 		array_splice($currentVotersList[$otherDirection], $ourVoterKey, 1);
// 	}
// 	updateCurrentVotersList($currentVotersList);
// }

// function getCurrentVotersList() {
// 	$liveID = getPageIDBySlug('live');
// 	$currentVotersList = get_post_meta($liveID, 'currentVoters', true);
// 	if ($currentVotersList === '') {
// 		$currentVotersList = [];
// 	}
// 	return $currentVotersList;
// }

// function updateCurrentVotersList($newList) {
// 	$liveID = getPageIDBySlug('live');
// 	update_post_meta($liveID, 'currentVoters', $newList);
// }

// function getOtherDirection($direction) {
// 	if ($direction === 'yea') {
// 		$otherDirection = 'nay';
// 	} elseif ($direction === 'nay') {
// 		$otherDirection = 'yea';
// 	}
// 	return $otherDirection;
// }

add_action( 'wp_ajax_chat_contender_vote', 'chat_contender_vote' );
function chat_contender_vote() {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}	
	$person = getPersonInDB($_POST['voter']);
	$postIDToVoteOn = getPostIDForVoteNumber($_POST['voteNumber']);
	if (!$postIDToVoteOn) {
		killAjaxFunction("You've picked an invalid number");
	}
	$slug = getSlugByPostID($postIDToVoteOn);

	if ($_POST['direction'] === "yea") {
		$weight = (int)$person['rep'];
	} elseif ($_POST['direction'] === "nay") {
		$weight = floatval(get_option("nayCoefficient")) * (int)$person['rep'] * -1;
	}

	$voteArray = array(
		"hash" => $person['hash'],
		"slug" => $slug,
		"weight" => $weight,
	);
	$addVoteResult = addVoteToDB($voteArray);
	
	killAjaxFunction($voter. " voted " . $_POST['direction'] . " on slug " . $slug);
}

function getPersonVoteIDs($person) {
	$personRow = getPersonInDB($person);
	$hash = $personRow['hash'];
	$voteIDs = array();
	global $wpdb;
	$table_name = $wpdb->prefix . 'vote_history_db';
	$allVoteRows = $wpdb->get_results(
		"SELECT postid
		FROM $table_name
		WHERE hash = '$hash'
		",
		ARRAY_A
	);
	foreach ($allVoteRows as $voteID) {
		$voteIDs[] = $voteID['postid'];
	}
	return $voteIDs;
}

add_action( 'wp_ajax_judge_slug', 'judge_slug' );
function judge_slug() {
	$slug = $_POST['slug'];
	$judgment = $_POST['judgment'];
	$vodlink = $_POST['vodlink'];
	if ($judgment === "pass") {
		killAjaxFunction("Dummy just passed");
		wp_die();
		return;
	}

	$userID = get_current_user_id();

	// Update the score in the PulledClipsDB
	$clipArray = getSlugInPulledClipsDB($slug);

	if ($clipArray === null) {
		killAjaxFunction("Unknown Clip: " . $slug);
	}

	if ($judgment === 'strongNo') {
		$clipArray['score'] = $clipArray['score'] - getValidRep($userID) * .2;
	} elseif ($judgment === 'weakNo') {
		// $clipArray['score'] = $clipArray['score'] - 1;
	} elseif ($judgment === 'weakYes') {
		$clipArray['score'] = $clipArray['score'] + 1;
	} elseif ($judgment === 'strongYes') {
		$clipArray['score'] = $clipArray['score'] + getValidRep($userID);
	}
	if (!is_int($clipArray['votecount'])) {
		$clipArray['votecount'] = 0;
	}
	$clipArray['votecount'] = (int)$clipArray['votecount'] + 1;
	editPulledClip($clipArray);

	// Add the vote to Seen_Clips_DB
	$error = store_slug_judgment($userID, $slug, $judgment, $vodlink);
	global $wpdb;
	$clipArray['storeError'] = $wpdb->last_error;

	nukeAllDupeSlugs($slug);
	checkForRepIncrease($userID);

	killAjaxFunction($clipArray);
}

function getVotersForSlug($slug) {
	global $wpdb;
	// $table_name = $wpdb->prefix . "seen_slugs_db";
	$table_name = $wpdb->prefix . "vote_db";

	$voterData = $wpdb->get_results(
		"SELECT *
		FROM $table_name
		WHERE slug = '$slug'
		", ARRAY_A
	);
	
	return $voterData;
}

function deleteAllVotesForSlug($slug) {
	$slugVotes = getVotersForSlug($slug);
	foreach ($slugVotes as $vote) {
		deleteSlugVote($vote['slug'], $vote['hash']);
	}
}

add_action( 'wp_ajax_reset_chat_votes', 'reset_chat_votes' );
function reset_chat_votes() {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}

	deleteAllVotesForSlug("live");

	// $currentVotersList['yea'] = [];
	// $currentVotersList['nay'] = [];

	// updateCurrentVotersList($currentVotersList);
	killAjaxFunction("we resettin the votes!");
}

function getVoterDisplayInfoForSlug($slug) {
	$voters = getVotersForSlug($slug);
	$theseVoters = array();
	foreach ($voters as $voter) {
		if ($voter['hash'] === "twitter") {
			$voterData = array(
				"name" => "Twitter",
				"picture" => get_site_url() . "/wp-content/uploads/2018/08/twitter-logo.png",
				"weight" => $voter['weight'],
			);
		} else if (strpos($voter['hash'], "norep") === 0) {
			$voterData = array(
				"name" => substr($voter['hash'], 5),
				"picture" => get_site_url() . "/wp-content/uploads/2017/03/default_pic.jpg",
				"weight" => $voter['weight'],
			);
		} else {
			$person = getPersonInDB($voter['hash']);
			if (!$person) {
				$voterData = array(
					"name" => "Deleted Person",
					"picture" => get_site_url() . "/wp-content/uploads/2017/03/default_pic.jpg",
					"weight" => $voter['weight'],
				);
			} else {
				$voterData = array(
					"name" => $person['dailiesDisplayName'],
					"picture" => $person['picture'],
					"weight" => $voter['weight'],
				);
			}
		}
		$theseVoters[] = $voterData;
	}
	return $theseVoters;
}

add_action( 'wp_ajax_add_twitter_votes', 'add_twitter_votes' );
function add_twitter_votes() {
	$postID = $_POST['id'];
	if (is_numeric($_POST['addedPoints'])) {
		$addedPoints = (int)$_POST['addedPoints'];
	} else {
		killAjaxFunction("That's not a number!");		
	}

	$voteArray = array(
		"slug" => getSlugByPostID($postID),
		"hash" => "twitter",
		"weight" => $addedPoints,
	);
	addVoteToDB($voteArray);

	killAjaxFunction("You added " . $addedPoints . " points to post " . $postID);
}


?>