<?php

function getLive() {
	$resetTime = getResetTime();
	$livePostArgs = array(
		'category_name' => 'contenders',
		'posts_per_page' => 50,
		'order' => 'asc',
		'orderby' => 'ID',
		'date_query' => array(
			array(
			//	'after' => '240 hours ago',
				'after' => $resetTime,
			)
		)
	);
	$livePosts = get_posts($livePostArgs);
	$liveData = [];
	foreach ($livePosts as $post) {
		setup_postdata($post);
		$postData = buildPostDataObject($post->ID);
		$clipdata = convertPostDataObjectToClipdata($postData);
		$clipdata['eliminated'] = get_post_meta($post->ID, "eliminated", true);
		if ($clipdata['eliminated'] === "") {$clipdata['eliminated'] = "false";}
		$liveData[] = $clipdata;
	}
	return $liveData;
}

function getLiveContenders() {
	$resetTime = getResetTime();
	$livePostArgs = array(
		'category_name' => 'contenders',
		'posts_per_page' => 50,
		'order' => 'asc',
		'orderby' => 'ID',
		'date_query' => array(
			array(
			//	'after' => '240 hours ago',
				'after' => $resetTime,
			)
		)
	);
	return get_posts($livePostArgs);
}

add_action( 'wp_ajax_reset_live', 'reset_live' );
function reset_live() {
	$reset_time_to = $_POST['timestamp'];
	$livePageObject = get_page_by_path('live');
	$liveID = $livePageObject->ID;
	update_post_meta($liveID, 'liveResetTime', $reset_time_to);
	echo json_encode($reset_time_to);
	wp_die();
}

function getResetTime() {
	$liveID = getPageIDBySlug('live');
	$resetTime = get_post_meta($liveID, 'liveResetTime', true);
	$resetTime = $resetTime / 1000;
	$wordpressUsableTime = date('c', $resetTime);
	return $wordpressUsableTime;
}

add_action( 'wp_ajax_post_promoter', 'post_promoter' );
function post_promoter() {
	$postID = $_POST['id'];
	if (current_user_can('edit_others_posts', $postID)) {
		$category_list = get_the_category($postID);
		$category_name = $category_list[0]->cat_name;
		// $authorID = get_post_field('post_author', $postID);
		if ($category_name === 'Prospects') {
			wp_remove_object_terms($postID, 'prospects', 'category');
			wp_add_object_terms( $postID, 'contenders', 'category' );
			absorb_votes($postID);
		} elseif ($category_name === 'Contenders') {
			wp_remove_object_terms($postID, 'contenders', 'category');
			wp_add_object_terms( $postID, 'noms', 'category' );
		}
	};
	echo json_encode($postID);
	wp_die();
}

add_action( 'wp_ajax_post_demoter', 'post_demoter' );
function post_demoter() {
	$postID = $_POST['id'];
	if (current_user_can('edit_others_posts', $postID)) {
		$category_list = get_the_category($postID);
		$category_name = $category_list[0]->cat_name;
		$authorID = get_post_field('post_author', $postID);
		if ($category_name === 'Nominees') {
			wp_remove_object_terms($postID, 'nominees', 'category');
			wp_add_object_terms( $postID, 'contenders', 'category' );
		} elseif ($category_name === 'Contenders') {
			// wp_remove_object_terms($postID, 'contenders', 'category');
			// wp_add_object_terms( $postID, 'prospects', 'category' );
			post_trasher($postID);
		} elseif ($category_name === 'Prospects') {
			post_trasher($postID);
		}
	};

	killAjaxFunction($postID);
}

add_action( 'wp_ajax_eliminate_post', 'eliminate_post' );
function eliminate_post() {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}
	update_post_meta($_POST['id'], 'eliminated', "true");
	killAjaxFunction("Eliminated post " . $_POST['id']);
}

function post_trasher($postID) {
	if (current_user_can('delete_published_posts', $postID)) {
		wp_trash_post($postID);
	};
	reset_chat_votes();
	return ($postID);
}

add_action( 'wp_ajax_edit_live_title', 'edit_live_title' );
function edit_live_title() {
	$newTitle = sanitize_text_field($_POST['newTitle']);
	$postID = $_POST['postID'];
	$postArray = array(
		"ID" => $postID,
		"post_title" => $newTitle,
	);
	wp_update_post($postArray);
	killAjaxFunction("You edited the title!");
}

function getLiveVoters() {
	// $liveSlug = get_option("liveSlug");
	// if ($liveSlug == "false") {$liveSlug = "live";}
	// $liveVoters = getVoterDisplayInfoForSlug($liveSlug);
	$liveVoters = getVoterDisplayInfoForSlug("live");
	return $liveVoters;
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

add_action( 'wp_ajax_updateVoteNumber', 'updateVoteNumber' );
function updateVoteNumber() {
	$votenumber = $_POST['contenderNumber'];
	$votenumberPageID = getPageIDBySlug('votenumber');
	update_post_meta($votenumberPageID, 'votenumber', $votenumber);
	killAjaxFunction($votenumber);
}

add_action( 'wp_ajax_returnVoteNumber', 'returnVoteNumber' );
add_action( 'wp_ajax_nopriv_returnVoteNumber', 'returnVoteNumber' );
function returnVoteNumber() {
	$votenumberPageID = getPageIDBySlug('votenumber');
	$votenumber = get_post_meta($votenumberPageID, 'votenumber', true);
	killAjaxFunction($votenumber);
}

function placeStreamMarker($label) {
	$url = "https://api.twitch.tv/helix/streams/markers";

	global $privateData;
	$args = array(
		"headers" => array(
			"Client-ID" => $privateData['twitchClientID'],
			"Authorization" => "Bearer " .  $privateData['twitchStreamMarkerToken'],
			'Accept' => 'application/vnd.twitchtv.v5+json',
		),
		"body" => array(
			"user_id" => "137604379",
			"description" => $label,
		),
	);

	$response = wp_remote_post($url, $args);
	basicPrint($response);
}

?>