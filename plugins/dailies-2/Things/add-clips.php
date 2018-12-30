<?php 
	
function addSlugToDB($slugData) {
	// $slugIsFresh = checkSlugFreshness($slugData);
	// if (!$slugIsFresh) {return false;}

	$clipArray = array(
		'slug' => $slugData['slug'],
		'title' => $slugData['title'] ? remove_emoji($slugData['title']) : "No Title",
		'views' => $slugData['views'] ? $slugData['views'] : 0,
		'age' => $slugData['age'] ? $slugData['age'] : 0,
		'source' => $slugData['source'] ? $slugData['source'] : 'unknown',
		'sourcepic' => $slugData['sourcepic'] ? $slugData['sourcepic'] : 'unknown',
		'vodlink' => $slugData['vodlink'] ? $slugData['vodlink'] : 'none',
		'thumb' => $slugData['thumb'],
		'clipper' => $slugData['clipper'] ? $slugData['clipper'] : 'unknown',
		'votecount' => $slugData['votecount'] ? $slugData['votecount'] : 0,
		'score' => $slugData['score'] ? $slugData['score'] : 0,
		'nuked' => $slugData['nuked'] ? $slugData['nuked'] : 0,
		'type' => $slugData['type'],
	);


	global $wpdb;
	$table_name = $wpdb->prefix . "pulled_clips_db";

	$insertionSuccess = $wpdb->insert(
		$table_name,
		$clipArray
	);
	if ($insertionSuccess) {
		$momentArray = array(
			"type" => $slugData['type'],
			"time" => date("U", strtotime($slugData['age'])),
		);
		if ($slugData['type'] == "twitch") {
			$momentArray['moment'] = $slugData['vodlink'] ? $slugData['vodlink'] : $slugData['slug'];
			if ($momentArray['moment'] == "none") {$momentArray['moment'] = $slugData['slug'];}
		} else {
			$momentArray['moment'] = $slugData['slug'];
		}
		addKnownMoment($momentArray);
		return $insertionSuccess;
	} else {
		return $wpdb->last_error;
	}
}

function checkSlugFreshness($slugData) {
	global $wpdb;
	$pulled_clips_table_name = $wpdb->prefix . "pulled_clips_db";

	$slug = $slugData['slug'];
	$type = $slugData['type'];
	$query = "SELECT * FROM $pulled_clips_table_name WHERE slug = '$slug' AND type = '$type'";
	$existingClip = $wpdb->get_row($query, ARRAY_A);
	if ($existingClip !== null) {
		return false;
	}

	if ($slugData['type'] == "twitch" && $slugData['vodlink'] && $slugData['vodlink'] !== "none") {
		$sameVodMoments = getAllKnownMomentsForVOD($slugData['vodlink']);

		$ourVodMomentArray = convertVodlinkToMomentObject($slugData['vodlink']);
		$ourVodTime = (int)$ourVodMomentArray['vodTime'];
		
		foreach ($sameVodMoments as $vodMomentArray) {
			if (strpos($vodMomentArray['moment'], "?t=all")) {
				return false;
			}
			$thisVodMomentArray = convertVodlinkToMomentObject($vodMomentArray['moment']);
			$thisVodTime = (int)$thisVodMomentArray['vodTime'];
			if ($ourVodTime + 20 > $thisVodTime && $ourVodTime - 20 < $thisVodTime) {
				return false;
			}
		}
	} elseif ($slugData['type'] == "twitch") {
		$known_moments_table_name = $wpdb->prefix . "known_moments_db";
		$query = "SELECT * FROM $known_moments_table_name WHERE moment = '$slug' AND type = '$type'";
		$existingMoment = $wpdb->get_row($query, ARRAY_A);
		if ($existingMoment !== null) {
			return false;
		}
	}
	return true;
}

function getAllKnownMomentsForVOD($vodlink) {
	if ($vodlink === "none") {
		return array();
	}
	global $wpdb;
	$table_name = $wpdb->prefix . "known_moments_db";

	$endOfVodID = strpos($vodlink, "?t=");
	$moment = substr($vodlink, 0, $endOfVodID);
	$query = "SELECT moment FROM $table_name WHERE moment LIKE '{$moment}%'";
	$sameVodMoments = $wpdb->get_results($query, ARRAY_A);
	return $sameVodMoments;
}

function addKnownMoment($momentArray) {
	global $wpdb;
	$table_name = $wpdb->prefix . "known_moments_db";

	$moment = $momentArray['moment'];
	$type = $momentArray['type'];

	$query = "SELECT * FROM $table_name WHERE moment = '$moment' AND type = '$type'";
	$existingRow = $wpdb->get_row($query, ARRAY_A);

	if ($existingRow !== null) {
		return false;
	}

	if (!array_key_exists("time", $momentArray)) {
		$momentArray["time"] = time();
	}

	$insertionSuccess = $wpdb->insert(
		$table_name,
		$momentArray
	);
	if ($insertionSuccess) {
		return $insertionSuccess;
	} else {
		return $wpdb->last_error;
	}
}

// add_action( 'wp_ajax_store_pulled_clips', 'store_pulled_clips' );
// function store_pulled_clips() {
// 	$clipsArray = $_POST['clips'];
// 	if (count($clipsArray) === 0) {
// 		killAjaxFunction("No clips from this stream");
// 	}

// 	foreach ($clipsArray as $slug => $slugData) {
// 		$existingSlug = getSlugInPulledClipsDB($slug);
// 		if ($existingSlug !== null) {
// 			$slugData['score'] = $existingSlug['score'];
// 			$slugData['nuked'] = $existingSlug['nuked'];
// 			$slugData['votecount'] = $existingSlug['votecount'];
// 			editPulledClip($slugData);
// 			continue;
// 		} else {
// 			$slugData['score'] = 0;
// 			$slugData['nuked'] = 0;
// 			$slugData['votecount'] = 0;
// 			$addSlugSuccess = addSlugToDB($slugData);
// 		}
// 	}

// 	update_option("lastClipUpdateTime", time());

// 	global $wpdb;
// 	killAjaxFunction($clipsArray);
// }

function getSlugInPulledClipsDB($slug) {
	global $wpdb;
	$table_name = $wpdb->prefix . "pulled_clips_db";

	$slugData = $wpdb->get_row(
		"SELECT *
		FROM $table_name
		WHERE slug = '$slug'
		", ARRAY_A
	);
	return $slugData;
}

?>