<?php

function getHopefuls() {
	global $wpdb;
	$table_name = $wpdb->prefix . "pulled_clips_db";

	$allClips = $wpdb->get_results(
		"
		SELECT *
		FROM $table_name
		WHERE nuked = 0
		",
		ARRAY_A
	);

	$hopefuls = [];
	foreach ($allClips as $key => $clipData) {
	// foreach ($hopefuls as $key => $clipData) {
		// $hopefuls[$key]['voters'] = [];
		$theseVoters = [];
		$score = 0;
		$voters = getVotersForSlug($clipData['slug']);
		foreach ($voters as $voter) {
			$person = getPersonInDB($voter['hash']);
			$voterData = array(
				"name" => $person['dailiesDisplayName'],
				"picture" => $person['picture'],
				"weight" => $voter['weight'],
			);
			if (!$person) {
				$voterData["name"] = "Deleted User";
				$voterData["picture"] = get_site_url() . "/wp-content/uploads/2017/03/default_pic.jpg";
			}
			$score = $score + (int)$voter['weight'];
			// $hopefuls[$key]['voters'][] = $voterData;
			$theseVoters[] = $voterData;
		}
		if ($score > 0) {
			$clipData['voters'] = $theseVoters;
			$hopefuls[] = $clipData;
			// $hopefuls[$key] = $clipData;
			// $hopefuls[$key]['voters'] = $theseVoters;
		}
	}

	// basicPrint($hopefuls);

	return $hopefuls;
}

add_action( 'wp_ajax_keepSlug', 'keepSlug' );
function keepSlug() {
	$slug = $_POST['slug'];
	$postTitle = $_POST['newThingName'];
	$slugData = getSlugInPulledClipsDB($slug);

	if ($slugData['source'] === "User Submit") {
		$postSource = 632; //This is the source ID for user submits
	} else {
		$postSource = sourceFinder($slugData['source']);
	}

	$postStar = starChecker($postTitle);

	// $slugVoters = getVotersForSlug($slugData['slug']);
	// $voteledger = array();
	// foreach ($slugVoters as $voter) {
	// 	$voteledger[$voter['hash']] = getValidRep($voter['hash']);
	// }

	$thingArray = array(
		'post_title' => $postTitle,
		'post_content' => '',
		'post_excerpt' => '',
		'post_status' => 'publish',
		'tax_input' => array(
			'source' => $postSource,
			'stars' => $postStar,
			'category' => 1125,
		),
		'meta_input' => array(
			'defaultThumb' => $slugData['thumb'],
		), 
	);

	if ($slugData['vodlink'] && $slugData['vodlink'] !== "none") {
		$thingArray['meta_input']['vodlink'] = $slugData['vodlink'];
	}

	if ($slugData['type'] === "twitch") {
		$thingArray['meta_input']['TwitchCode'] = $slugData['slug'];
	} elseif ($slugData['type'] === "gfycat") {
		$thingArray['meta_input']['GFYtitle'] = $slugData['slug'];
	} elseif ($slugData['type'] === "youtube" || $slugData['type'] === "ytbe") {
		$thingArray['meta_input']['YouTubeCode'] = $slugData['slug'];
	} elseif ($slugData['type'] === "twitter") {
		$thingArray['meta_input']['TwitterCode'] = $slugData['slug'];
	}

	$didPost = wp_insert_post($thingArray, true);
	// if ($didPost > 0) {
	// 	absorb_votes($didPost);
	// }

	// $dupes = get_dupe_clips($slugData['slug']);
	// if ($dupes) {
	// 	foreach ($dupes as $dupe) {
	// 		nukeSlug($dupe);
	// 	}
	// }
	nukeSlug($slugData['slug']);
	deleteAllVotesForSlug("live");

	killAjaxFunction("Post added for " . $slugData['slug']);
}

add_action( 'wp_ajax_hopefuls_cutter', 'hopefuls_cutter' );
function hopefuls_cutter() {
	$slugToNuke = $_POST['slug'];
	nukeSlug($slugToNuke);
	reset_chat_votes();
	killAjaxFunction($slugToNuke);
}

?>