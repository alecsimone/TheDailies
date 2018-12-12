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
	$promotedAHopeful = false;
	$liveSlug = get_option("liveSlug");
	$liveSlugIsHopeful = false;
	foreach ($allClips as $key => $clipData) {
	// foreach ($hopefuls as $key => $clipData) {
		// $hopefuls[$key]['voters'] = [];
		$theseVoters = [];
		$score = 0;
		$voters = getVotersForSlug($clipData['slug']);
		foreach ($voters as $voter) {
			$score = $score + (int)$voter['weight'];
		}
		if ($score > 0) {
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
				$theseVoters[] = $voterData;
			}
			$clipData['voters'] = $theseVoters;
			$clipData['score'] = $score;
			if ($clipData['slug'] == $liveSlug) {
				$liveSlugIsHopeful = true;
			}
			$hopefuls[] = $clipData;
			// $hopefuls[$key] = $clipData;
			// $hopefuls[$key]['voters'] = $theseVoters;
		}
	}

	if ($liveSlug !== "false") {	
		$lastViewerCountUpdateTime = get_option("lastViewerCountUpdateTime");
		if ($lastViewerCountUpdateTime > time() - 30) {
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
			if ( $liveSlugYeaCount >= round($viewerCount * $magicNumberConstant, 0, PHP_ROUND_HALF_UP) && $liveSlugYeaCount >= 2 ) {
				addPostForSlug($liveSlug);
				nukeSlug($liveSlug);
				deleteAllVotesForSlug($liveSlug);
				deleteAllVotesForSlug("live");
				update_option("liveSlug", "false");
				$promotedAHopeful = true;
			}
		}
	}


	if (!$liveSlugIsHopeful) {update_option("liveSlug", "false");}

	$hopefulsData = array(
		"clips" => $hopefuls,
		"liveSlug" => get_option("liveSlug"),
		"promotedAHopeful" => $promotedAHopeful,
	);

	// basicPrint($hopefuls);

	return $hopefulsData;
}

add_action( 'wp_ajax_keepSlug', 'keepSlug' );
function keepSlug() {
	$slug = $_POST['slug'];
	$postTitle = $_POST['newThingName'];
	addPostForSlug($slug, $postTitle);
	nukeSlug($slug);
	deleteAllVotesForSlug($slug);
	deleteAllVotesForSlug("live");
	killAjaxFunction("Post added for " . $slug);
}

function addPostForSlug($slug, $title = false) {
	$slugData = getSlugInPulledClipsDB($slug);
	if (!$title) {$title = $slugData['title'];}

	if ($slugData['source'] === "User Submit") {
		$postSource = 632; //This is the source ID for user submits
	} else {
		$postSource = sourceFinder($slugData['source']);
	}

	$postStar = starChecker($title);

	// $slugVoters = getVotersForSlug($slugData['slug']);
	// $voteledger = array();
	// foreach ($slugVoters as $voter) {
	// 	$voteledger[$voter['hash']] = getValidRep($voter['hash']);
	// }

	$thingArray = array(
		'post_title' => $title,
		'post_author' => 1,
		'post_content' => '',
		'post_excerpt' => '',
		'post_status' => 'publish',
		'post_category' => [1125],
		'tax_input' => array(
			'source' => $postSource,
			'stars' => $postStar,
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
}

add_action( 'wp_ajax_hopefuls_cutter', 'hopefuls_cutter' );
function hopefuls_cutter() {
	$slugToNuke = $_POST['slug'];
	nukeSlug($slugToNuke);
	deleteVotesIfSlugIsLive($_POST['slug']);
	killAjaxFunction($slugToNuke);
}

add_action( 'wp_ajax_choose_live_slug', 'choose_live_slug' );
function choose_live_slug() {
	update_option( "liveSlug", $_POST['slug'] );
	// if ($_POST['slug']) {
	// 	$liveVoters = getVotersForSlug("live");
	// 	global $wpdb;
	// 	$table = $wpdb->prefix . "vote_db";
	// 	$data = array(
	// 		"slug" => $_POST['slug'],
	// 	);
	// 	foreach ($liveVoters as $vote) {
	// 		$existingVote = $wpdb->get_row(
	// 			"SELECT *
	// 			FROM $table
	// 			WHERE hash = '{$vote['hash']}' AND slug = '{$_POST['slug']}'",
	// 			'ARRAY_A'
	// 		);
	// 		if ($existingVote) {continue;}
	// 		$where = array(
	// 			"id" => $vote["id"],
	// 			"hash" => $vote["hash"],
	// 		);
	// 		$wpdb->update($table, $data, $where);
	// 	}
	// }
	killAjaxFunction($liveVoters);
	// killAjaxFunction($_POST['slug'] . " is now selected!");
}

function deleteVotesIfSlugIsLive($slug) {
	$liveSlug = get_option("liveSlug");
	if ($liveSlug === $slug) {
		deleteAllVotesForSlug($slug);
		update_option( "liveSlug", "false" );
	}
}

add_action( 'wp_ajax_update_viewer_count', 'update_viewer_count' );
function update_viewer_count() {
	$viewerCount = $_POST['viewerCount'];
	update_option("viewerCount", $viewerCount);
	update_option("lastViewerCountUpdateTime", time());
	killAjaxFunction("Viewer count updated!");
}

?>