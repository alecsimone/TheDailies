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

// add_action('init', 'populateKnownMoments');
function populateKnownMoments() {
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
}

?>
