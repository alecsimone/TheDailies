<?php

function getPulledClipsDB() {
	global $wpdb;
	$table_name = $wpdb->prefix . "pulled_clips_db";

	$pulledClipsDB = $wpdb->get_results(
		"
		SELECT *
		FROM $table_name
		",
		ARRAY_A
	);

	return $pulledClipsDB;
}

function getCleanPulledClipsDB() {
	$pulledClipsDBRaw = getPulledClipsDB();
	$ourCutoff = clipCutoffTimestamp();
	foreach ($pulledClipsDBRaw as $key => $clipData) {
		$clipTimestamp = convertTwitchTimeToTimestamp($clipData['age']);
		if ($clipTimestamp < $ourCutoff && (intval($clipData['score']) < -51 || $clipData['nuked'] == 2)) {
			deleteSlugFromPulledClipsDB($clipData['slug']);
			// deleteAllVotesForSlug($clipData['slug']);
			continue;
		}
		if ($clipTimestamp < $ourCutoff - 24 * 60 * 60 && (intval($clipData['score']) < -1 || $clipData['nuked'] == 2)) {
			deleteSlugFromPulledClipsDB($clipData['slug']);
			// deleteAllVotesForSlug($clipData['slug']);
			continue;
		}
		if ($clipTimestamp < $ourCutoff - 72 * 60 * 60) {
			deleteSlugFromPulledClipsDB($clipData['slug']);
			// deleteAllVotesForSlug($clipData['slug']);
			continue;
		}
		$pulledClipsDB[$clipData['slug']] = $clipData;
	}
	return $pulledClipsDB;
}

function editPulledClip($clipArray) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'pulled_clips_db';

	$where = array(
		'slug' => $clipArray['slug'],
	);

	$wpdb->update(
		$table_name,
		$clipArray,
		$where
	);
}

function clipCutoffTimestamp() {
	$lastNomTime = getLastNomTimestamp();
	$eightHoursBeforeLastNom = $lastNomTime - 8 * 60 * 60;
	$twentyFourHoursAgo = time() - 24 * 60 * 60;
	return $eightHoursBeforeLastNom < $twentyFourHoursAgo ? $eightHoursBeforeLastNom : $twentyFourHoursAgo;
}

function nukeSlug($slug) {
	$person = getPersonInDB(get_current_user_id());
	$slugToNuke = getSlugInPulledClipsDB($slug);
	if ($slugToNuke === null) {
		$slugData = array(
			'slug' => $slug,
			'nuked' => 1,
		);
		addSlugToDB($slugData);
	} else {
		if (currentUserIsAdmin()) {
			$newNukedValue = 2;
		} else if (currentUserIsEditorOrAdmin()) {
			$newNukedValue = (int)$slugToNuke['nuked'] + 1;
			if ($newNukedValue > 2) {$newNukedValue = 2;}
		} else if ((int)$person['rep'] >= 5 && (int)$slugToNuke['nuked'] < 2) {
			$newNukedValue = 1;
		}
		$slugToNuke['nuked'] = $newNukedValue;
		editPulledClip($slugToNuke);
	}
	deleteAllVotesForSlug($slug);
	return $slug;
}

add_action( 'wp_ajax_nuke_slug', 'nuke_slug_handler' );
function nuke_slug_handler() {
	$person = getPersonInDB(get_current_user_id());
	if (!currentUserIsEditorOrAdmin()) {
		if ((int)$person["rep"] < 5) {
			killAjaxFunction("You can't nuke things!");
		}
	}
	$slugToNuke = $_POST['slug'];
	nukeSlug($slugToNuke);
	$nukeArray = array(
		"slug" => $slugToNuke,
		"nuker" => $person['hash'],
		"time" => time(),
	);
	storeNukeRecord($nukeArray);
	killAjaxFunction($slugToNuke);
}

function storeNukeRecord($nukeArray) {
	global $wpdb;
	$table_name = $wpdb->prefix . "nuke_records_db";

	$existingNukeRecord = $wpdb->get_row(
		"SELECT * 
		FROM $table_name
		WHERE nuker = '{$nukeArray['nuker']}' AND slug = '{$nukeArray['slug']}'
		",
		'ARRAY_A'
	);
	if ($existingNukeRecord) {
		return "That person already nuked that clip";
	}

	$insertionSuccess = $wpdb->insert(
		$table_name,
		$nukeArray
	);

	return $insertionSuccess;
}

// add_action('init', 'populateKnownMoments');
// function populateKnownMoments() {
// 	$momentsAreKnown = get_option("momentsAreKnown");
// 	if ($momentsAreKnown) {return;}

// 	$pulledClips = getPulledClipsDB();
// 	foreach ($pulledClips as $clip) {
// 		$momentArray = array(
// 			"time" => date("U", strtotime($clip['age'])),
// 			"type" => $clip['type'],
// 		);
// 		if ($clip['type'] == "twitch") {
// 			$moment = $clip['vodlink'] == "none" ? $clip['slug'] : $clip['vodlink'];
// 		} else {
// 			$moment = $clip['slug'];
// 		}
// 		$momentArray['moment'] = $moment;
// 		$addedMoment = addKnownMoment($momentArray);
// 		basicPrint($addedMoment);
// 	}
// 	update_option( "momentsAreKnown", true );
// }

function convertPostDataObjectToClipdata($postDataObject) {
	$slug = getSlugByPostID($postDataObject['id']);
	$clipdata = array(
		'slug' => $slug,
		'type' => getClipTypeByPostID($postDataObject['id']),
		'postID' => $postDataObject['id'],
		'title' => $postDataObject['title'],
		'skills' => $postDataObject['taxonomies']['skills'],
		'source' => $postDataObject['taxonomies']['source'],
		'stars' => $postDataObject['taxonomies']['stars'],
		'tags' => $postDataObject['taxonomies']['tags'],
		'categories' => $postDataObject['categories'],
		'voters' => getVoterDisplayInfoForSlug($slug),
		'thumb' => $postDataObject['thumbs']['medium'][0],
		'pdo' => $postDataObject,
	);
	if ($clipdata['thumb'] == null) {
		$clipdata['thumb'] = get_post_meta($postDataObject['id'], 'defaultThumb', true);
	}

	$vodlink = get_post_meta($postDataObject['id'], 'vodlink', true);
	if ($vodlink !== '') {
		$clipdata['vodlink'] = $vodlink;
	}
	return $clipdata;
}

function deleteSlugFromPulledClipsDB($slug) {
	global $wpdb;
	$table_name = $wpdb->prefix . "pulled_clips_db";

	$where = array(
		'slug' => $slug,
	);

	$wpdb->delete($table_name, $where);
}
function deleteJudgmentFromSeenSlugsDB($id) {
	global $wpdb;
	$table_name = $wpdb->prefix . "seen_slugs_db";

	$where = array(
		'id' => $id,
	);

	$wpdb->delete($table_name, $where);
}

add_action( 'wp_ajax_blacklist_vod', 'blacklist_vod_handler' );
function blacklist_vod_handler() {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}
	blacklist_vod($_POST['vodID']);
	killAjaxFunction("Vod blacklisted!");
}

function blacklist_vod($vodID) {
	$rawVodlink = "https://www.twitch.tv/videos/" . $vodID;
	$moment = $rawVodlink . "?t=all";
	$momentArray = array(
		"moment" => $moment,
		'type' => "twitch", 
	);
	addKnownMoment($momentArray);

	global $wpdb;
	$pulled_clips_table_name = $wpdb->prefix . "pulled_clips_db";

	$query = "SELECT slug FROM $pulled_clips_table_name WHERE vodlink LIKE '{$rawVodlink}%'";
	$sameVodSlugs = $wpdb->get_results($query, ARRAY_A);

	foreach ($sameVodSlugs as $slugArray) {
		nukeSlug($slugArray["slug"]);
	}
}

add_action('publish_post', 'set_default_custom_fields');
function set_default_custom_fields($ID){
	global $wpdb;
    if( !wp_is_post_revision($ID) ) {add_post_meta($ID, 'votecount', 0, true);};
};

add_action( 'wp_ajax_declare_winner', 'declare_winner' );
function declare_winner() {
	$nonce = $_POST['vote_nonce'];
	if (!wp_verify_nonce($nonce, 'vote_nonce')) {
		die("Busted!");
	}
	$postID = $_POST['id'];
	if (!current_user_can('edit_others_posts', $postID)) {
		die("You can't do that!");
	}
	wp_set_post_tags($postID, 'Winners', true);
	buildPostDataObject($postID);
	wp_die();
}

?>
