<?php

add_action('init', 'populate_vote_db');
function populate_vote_db() {
	$votePopulationOffset = get_option("votePopulationOffset");
	if (!$votePopulationOffset) {
		$votePopulationOffset = 0;
	}
	if ($votePopulationOffset === "done") {return;}

	$numberposts = 100;
	$args = array(
		"numberposts" => $numberposts,
		"offset" => $votePopulationOffset,
	);

	$posts = get_posts($args);
	if (is_array($posts)) {
		foreach ($posts as $key => $post) {
			$addedPosts = addPostVotesToNewDB($post->ID);
		}
		if ($addedPosts !== "done") {
			update_option("votePopulationOffset", $votePopulationOffset + $numberposts);
		}
	}
}

function addPostVotesToNewDB($postID) {
	$slugsArray = array(
		'twitch' => get_post_meta($postID, 'TwitchCode', true),
		'twitter' => get_post_meta($postID, 'TwitterCode', true),
		'gfy' => get_post_meta($postID, 'GFYtitle', true),
		'youtube' => get_post_meta($postID, 'YouTubeCode', true),
	);
	foreach ($slugsArray as $slugCheck) {
		$slugCheck != "" ? $slug = $slugCheck : $slug = $slug;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . "vote_db";
	
	$existingVote = $wpdb->get_row(
		"SELECT *
		FROM $table_name
		WHERE slug = '$slug'",
		'ARRAY_A'
	);
	if ($existingVote) {
		update_option("votePopulationOffset", "done");
		return "done";
	}


	$voteData = array(
		'voteledger' => get_post_meta($postID, 'voteledger', true),
		'guestlist' => get_post_meta($postID, 'guestlist', true),
		'twitchVoters' => get_post_meta($postID, 'twitchVoters', true),
		'addedScore' => get_post_meta($postID, 'addedScore', true),
	);
	
	if (is_array($voteData["voteledger"])) {
		foreach ($voteData["voteledger"] as $key => $value) {
			if (!is_numeric($key)) {
				$hash = $key;
			} else {
				$person = getPersonInDB($key);
				$hash = $person["hash"];
			}
			$siteVoteArray = array(
				"hash" => $hash,
				"weight" => $value,
				"slug" => $slug,
			);
			addVoteToDB($siteVoteArray);
		}
	}

	if (is_array($voteData["twitchVoters"])) {
		foreach ($voteData['twitchVoters'] as $voter => $data) {
			$person = getPersonInDB($voter);
			$twitchVoteArray = array(
				"hash" => $person['hash'],
				"weight" => 1,
				"slug" => $slug,
			);
			addVoteToDB($twitchVoteArray);
		}
	}
	
	if ($voteData['addedScore'] != "") {
		$twitterVoteArray = array(
			"hash" => "twitter",
			"weight" => $voteData['addedScore'],
			"slug" => $slug,
		);
		addVoteToDB($twitterVoteArray);
	}
	
}

add_action('init', 'convertSeenSlugsToVoteDB');
function convertSeenSlugsToVoteDB() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'seen_slugs_db';


	$seenSlugs = $wpdb->get_results(
		"
		SELECT *
		FROM $table_name
		",
		ARRAY_A
	);

	$cutoff = clipCutoffTimestamp();
	foreach ($seenSlugs as $key => $value) {
		if ((int)$value['time'] < $cutoff) {
			deleteJudgmentFromSeenSlugsDB($value['id']);
		} else {
			$voteArray = array(
				"hash" => $value['hash'],
				"weight" => $value['vote'] > 0 ? getValidRep($value['hash']) : getValidRep($value['hash']) * -1 * floatval(get_option("nayCoefficient")),
				"slug" => $value['slug'],
			);
			$voteAdded = addVoteToDB($voteArray);
			if ($voteAdded == 1) {deleteJudgmentFromSeenSlugsDB($value['id']);}
		}
	}
}

?>