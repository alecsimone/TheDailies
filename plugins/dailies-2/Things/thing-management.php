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
		if ($clipTimestamp < $ourCutoff && (intval($clipData['score']) < -21 || $clipData['nuked'] == 1)) {
			deleteSlugFromPulledClipsDB($clipData['slug']);
			// deleteAllVotesForSlug($clipData['slug']);
			continue;
		}
		if ($clipTimestamp < $ourCutoff - 24 * 60 * 60 && (intval($clipData['score']) < -1 || $clipData['nuked'] == 1)) {
			deleteSlugFromPulledClipsDB($clipData['slug']);
			// deleteAllVotesForSlug($clipData['slug']);
			continue;
		}
		$pulledClipsDB[$clipData['slug']] = $clipData;
	}
	return $pulledClipsDB;
}

function clipCutoffTimestamp() {
	$lastNomTime = getLastNomTimestamp();
	$eightHoursBeforeLastNom = $lastNomTime - 8 * 60 * 60;
	$twentyFourHoursAgo = time() - 24 * 60 * 60;
	return $eightHoursBeforeLastNom < $twentyFourHoursAgo ? $eightHoursBeforeLastNom : $twentyFourHoursAgo;
}

add_action('init', 'populateKnownMoments');
function populateKnownMoments() {
	$momentsAreKnown = get_option("momentsAreKnown");
	if ($momentsAreKnown) {return;}

	$pulledClips = getPulledClipsDB();
	foreach ($pulledClips as $clip) {
		$momentArray = array(
			"time" => date("U", strtotime($clip['age'])),
			"type" => $clip['type'],
		);
		if ($clip['type'] == "twitch") {
			$moment = $clip['vodlink'] == "none" ? $clip['slug'] : $clip['vodlink'];
		} else {
			$moment = $clip['slug'];
		}
		$momentArray['moment'] = $moment;
		$addedMoment = addKnownMoment($momentArray);
		basicPrint($addedMoment);
	}
	update_option( "momentsAreKnown", true );
}

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

?>
