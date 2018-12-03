<?php 

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

// $pulledClipsDB = getPulledClipsDB();
// foreach ($pulledClipsDB as $key => $clipData) {
// 	if ($clipData['score'] != '0' && $clipData['votecount'] == 0) {
// 		basicPrint($key);
// 		$clipData['votecount'] = 1;
// 		editPulledClip($clipData);
// 	}
// }

function nukeSlug($slug) {
	$slugToNuke = getSlugInPulledClipsDB($slug);
	if ($slugToNuke === null) {
		$slugData = array(
			'slug' => $slug,
			'nuked' => 1,
		);
		addSlugToDB($slugData);
	} else {
		$slugToNuke['nuked'] = 1;
		editPulledClip($slugToNuke);
	}
	deleteAllVotesForSlug($slug);
	return $slug;

	// $time = time() * 1000;
	// $slugObj = array(
	// 	'slug' => $slug,
	// 	'createdAt' => $time,
	// 	'cutBoolean' => true,
	// 	'VODBase' => "null",
	// 	'VOD' => "null",
	// );
	// $gardenPostObject = get_page_by_path('secret-garden');
	// $gardenID = $gardenPostObject->ID;
	// $globalSlugList = get_post_meta($gardenID, 'slugList', true);
	// $newGlobalSlugList = $globalSlugList;
	// $newGlobalSlugList[$slug] = $slugObj;
	// update_post_meta($gardenID, 'slugList', $newGlobalSlugList );
}

add_action( 'wp_ajax_nuke_slug', 'nuke_slug_handler' );
function nuke_slug_handler() {
	$slugToNuke = $_POST['slug'];
	nukeSlug($slugToNuke);
	// nukeAllDupeSlugs($slug);
	killAjaxFunction($slugToNuke);
}

function store_slug_judgment($person, $slug, $judgment, $vodlink) {
	$vote = 0;
	if ($judgment === 'strongNo') {
		$vote = -2;
	} elseif ($judgment === 'weakNo') {
		$vote = -1;
	} elseif ($judgment === 'weakYes') {
		$vote = 1;
	} elseif ($judgment === 'strongYes') {
		$vote = 2;
	}
	if ($vote === 0) {return;}

	$seenClipArray = array(
		'hash' => getPersonsHash($person),
		'slug' => $slug,
		'vote' => $vote,
		'vodlink' => $vodlink,
		'time' => time(),
	);

	$previousJudgment = get_slug_judgment($person, $slug);
	global $wpdb;
	$table_name = $wpdb->prefix . 'seen_slugs_db';
	if ($previousJudgment === null) {
		$wpdb->insert($table_name, $seenClipArray);
	} else {
		$where = array(
			'hash' => getPersonsHash($person),
			'slug' => $slug,
		);
		$wpdb->update($table_name, $seenClipArray, $where);
	}
	return $wpdb->last_error;
}

function get_slug_judgment($person, $slug) {
	$hash = getPersonsHash($person);

	global $wpdb;
	$table_name = $wpdb->prefix . 'seen_slugs_db';

	$slugJudgment = $wpdb->get_row(
		"SELECT *
		FROM $table_name
		WHERE hash = '$hash' AND slug = '$slug'
		",
		ARRAY_A
	);
	return $slugJudgment;
}

function get_dupe_clips($string) {
	if (!strpos($string, '/videos/')) {
		$slugData = getSlugInPulledClipsDB($string);
		if ($slugData === null) {
			return "Slug not found";
		}
		$string = $slugData['vodlink'];
	}

	if ($string === "none") {
		return "That slug doesn't have a vodlink";
	}
	
	$slugMoment = convertVodlinkToMomentObject($string);

	global $wpdb;
	$table_name = $wpdb->prefix . "pulled_clips_db";
	$vodlinkQuery = "https://www.twitch.tv/videos/" . $slugMoment['vodID'] . '%';

	$sameVodSlugs = $wpdb->get_results(
		"
		SELECT slug, vodlink
		FROM $table_name
		WHERE vodlink LIKE '$vodlinkQuery'
		"
	);

	$dupeSlugs = [];
	foreach ($sameVodSlugs as $key => $slugAndLink) {
		$thisMoment = convertVodlinkToMomentObject($slugAndLink->vodlink);
		$thisTime = $thisMoment['vodTime'];
		if ((int)$thisTime + 25 >= (int)$slugMoment['vodTime'] && (int)$thisTime - 25 <= (int)$slugMoment['vodTime']) {
			$dupeSlugs[] = $slugAndLink->slug;
		}
	}

	return $dupeSlugs;
}

function nukeAllDupeSlugs($slug) {
	$dupes = get_dupe_clips($slug);
	if (is_array($dupes)) {
		foreach ($dupes as $key => $dupeSlug) {
			if ($dupeSlug !== $slug) {
				nukeSlug($dupeSlug);
			}
		}
	} else {
		return $dupes;
	}
}

?>