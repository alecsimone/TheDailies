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
			if ( $liveSlugYeaCount >= round($viewerCount * $magicNumberConstant, 0, PHP_ROUND_HALF_DOWN) && $liveSlugYeaCount >= 2 ) {
				$postWasAdded = addPostForSlug($liveSlug);
				// if ($postWasAdded) {
				// 	basicPrint("Success!");
				// } else {
				// 	basicPrint("Failure!");
				// }
				if ($postWasAdded) {
					nukeSlug($liveSlug);
					deleteAllVotesForSlug($liveSlug);
					deleteAllVotesForSlug("live");
					update_option("liveSlug", "false");
					update_option("lastAutoAction", "true");
				}
			}
		}
	}


	if (!$liveSlugIsHopeful) {update_option("liveSlug", "false");}

	$hopefulsData = array(
		"clips" => $hopefuls,
		"liveSlug" => get_option("liveSlug"),
		"promotedAHopeful" => get_option("lastAutoAction"),
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

	$currentHour = (int)date('H');
	if ($currentHour > 12) {
		$dateToPostOn = (int)date('d') + 1;
	} else {
		$dateToPostOn = (int)date('d');
	}
	$postDate = date('Y-m-') . $dateToPostOn . " 00:01:00";

	$thingArray = array(
		'post_title' => $title,
		'post_author' => 1,
		'post_date' => $postDate,
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

	$args = array();

	if ($slugData['type'] === "twitch") {
		$thingArray['meta_input']['TwitchCode'] = $slugData['slug'];
		$args['meta_query'] = array(
			array(
				"key" => "TwitchCode",
				"value" => $slugData['slug'],
				"compare" => "=",
			),
		);
	} elseif ($slugData['type'] === "gfycat") {
		$thingArray['meta_input']['GFYtitle'] = $slugData['slug'];
		$args['meta_query'] = array(
			array(
				"key" => "GFYtitle",
				"value" => $slugData['slug'],
				"compare" => "=",
			),
		);
	} elseif ($slugData['type'] === "youtube" || $slugData['type'] === "ytbe") {
		$thingArray['meta_input']['YouTubeCode'] = $slugData['slug'];
		$args['meta_query'] = array(
			array(
				"key" => "YouTubeCode",
				"value" => $slugData['slug'],
				"compare" => "=",
			),
		);
	} elseif ($slugData['type'] === "twitter") {
		$thingArray['meta_input']['TwitterCode'] = $slugData['slug'];
		$args['meta_query'] = array(
			array(
				"key" => "TwitterCode",
				"value" => $slugData['slug'],
				"compare" => "=",
			),
		);
	}

	$query = new WP_Query($args);

	if (!$query->have_posts()) {
		$didPost = wp_insert_post($thingArray, true);
		return true;
	} else {
		return false;
	}

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
	update_option("lastAutoAction", "false");
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
		deleteAllVotesForSlug("live");
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