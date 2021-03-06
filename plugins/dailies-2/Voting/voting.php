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

	checkForRepIncrease($person['hash']);

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
		$newHash = generateHash();
		$person = array(
			"hash" => $newHash,
			"rep" => 1,
		);
		$addPersonArray = array(
			"twitchName" => $_POST['voter'],
			"hash" => $newHash,
		);
		addPersonToDB($addPersonArray);
	}
	if ($_POST['direction'] === "yea") {
		$weight = (int)$person['rep'];
	} elseif ($_POST['direction'] === "nay") {
		$weight = floatval(get_option("nayCoefficient")) * (int)$person['rep'] * -1;
	}

	// $liveSlug = get_option("liveSlug");
	// if ($liveSlug == "false") {$liveSlug = "live";}
	$voteArray = array(
		"hash" => $person['hash'],
		"weight" => $weight,
		// "slug" => $liveSlug,
		"slug" => "live",
	);

	$addVoteResult = addVoteToDB($voteArray);

	$liveSlug = get_option("liveSlug");
	if ($liveSlug !== "false") {	
		$lastViewerCountUpdateTime = get_option("lastViewerCountUpdateTime");
		// if ($lastViewerCountUpdateTime > time() - 30) {
			$viewerCount = get_option("viewerCount");
			$magicNumberConstant = get_option("magicNumberConstant");
			$liveSlugYeaCount = 0;
			$liveSlugNayCount = 0;
			$liveVoters = getVotersForSlug("live");
			foreach ($liveVoters as $voter) {
				if ((int)$voter['weight'] > 0) {
					$liveSlugYeaCount++;
				} elseif ((int)$voter['weight'] < 0) {
					$liveSlugNayCount++;
				}
			}
			if ( $liveSlugYeaCount >= round($viewerCount * $magicNumberConstant, 0, PHP_ROUND_HALF_DOWN) && $liveSlugYeaCount >= 2 ) {
				$postWasAdded = addPostForSlug($liveSlug);
				if ($postWasAdded) {
					$liveSlugTitle = getTitleBySlug($liveSlug);
					send_nightbot_message($liveSlugTitle . " has moved on to the final round!");
					placeStreamMarker($liveSlug .  " promoted");
					nukeSlug($liveSlug);
					deleteAllVotesForSlug($liveSlug);
					deleteAllVotesForSlug("live");
					update_option("liveSlug", "false");
					update_option("lastPromotedSlug", $liveSlug);
				}
			}
		// }
	}
	
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

		$contendersArray = getLiveContenders();
		global $wpdb;
		$table_name = $wpdb->prefix . "vote_db";
		foreach ($contendersArray as $contender) {
			$thisSlug = getSlugByPostID($contender->ID);
			if ($thisSlug === $slug) {continue;}

			$where = array(
				"hash" => $person['hash'],
				"slug" => $thisSlug,
				"weight" => $weight,
			);
			$wpdb->delete($table_name, $where);
		}

	} elseif ($_POST['direction'] === "nay") {
		$weight = floatval(get_option("nayCoefficient")) * (int)$person['rep'] * -1;
	}

	$voteArray = array(
		"hash" => $person['hash'],
		"slug" => $slug,
		"weight" => $weight,
	);
	$addVoteResult = addVoteToDB($voteArray);

	$postTitle = get_the_title($postIDToVoteOn);
	$postTitleWordsArray = explode(" ", $postTitle);
	
	if ($addVoteResult === "That vote already exists!") {
		killAjaxFunction($_POST['voter'] . " removed their " . $_POST['direction'] . " vote  on " . $postTitleWordsArray[0]);
	}
	killAjaxFunction($_POST['voter'] . " voted " . $_POST['direction'] . " on " . $postTitleWordsArray[0]);
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

// add_action( 'init', 'addScoresToPulledClipsDB');
// function addScoresToPulledClipsDB() {
// 	$pulledClipsDB = getCleanPulledClipsDB();
// 	foreach ($pulledClipsDB as $slug => $clipArray) {
// 		if ($clipArray['score'] == 0) {
// 			$clipArray['score'] = getScoreForSlug($slug);
// 			$clipArray['votecount'] = count(getVotersForSlug($slug));
// 		}
// 		editPulledClip($clipArray);
// 	}
// }

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

function getNukersForSlug($slug) {
	global $wpdb;
	// $table_name = $wpdb->prefix . "seen_slugs_db";
	$table_name = $wpdb->prefix . "nuke_records_db";

	$nukerData = $wpdb->get_results(
		"SELECT *
		FROM $table_name
		WHERE slug = '$slug'
		", ARRAY_A
	);
	
	return $nukerData;
}

function getScoreForSlug($slug) {
	$votes = getVotersForSlug($slug);
	$score = 0;
	foreach ($votes as $vote) {
		$score += (int)$vote['weight'];
	}
	return $score;
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
				"contribution" => 0,
			);
		} else {
			$person = getPersonInDB($voter['hash']);
			if (!$person) {
				$voterData = array(
					"name" => "Deleted Person",
					"picture" => get_site_url() . "/wp-content/uploads/2017/03/default_pic.jpg",
					"weight" => $voter['weight'],
					"contribution" => 0,
				);
			} else {
				$voterData = array(
					"name" => $person['dailiesDisplayName'] == "--" ? $person['twitchName'] : $person['dailiesDisplayName'],
					"picture" => $person['manualPicture'] === "none" ? $person['picture'] : $person['manualPicture'],
					"weight" => $voter['weight'],
					"contribution" => $person['contribution'],
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

add_action( 'wp_ajax_get_chat_votes', 'get_chat_votes' );
add_action( 'wp_ajax_nopriv_get_chat_votes', 'get_chat_votes' );
function get_chat_votes() {
	$currentVotersList = getVotersForSlug("live");
	killAjaxFunction($currentVotersList);
}


?>