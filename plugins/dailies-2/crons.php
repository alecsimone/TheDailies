<?php

function add_custom_cron_schedules($schedules) {
	$schedules['twiceHourly'] = array(
		'interval' => 1800,
		'display' => __("Twice Hourly"),
	);

	$schedules['minute'] = array(
		'interval' => 60,
		'display' => __("Every Minute"),
	);

	$schedules['tenMinutes'] = array(
		'interval' => 600,
		'display' => __("Every Ten Minutes"),
	);

	return $schedules;
}
add_filter( 'cron_schedules', 'add_custom_cron_schedules' );

if (wp_get_schedule('pull_clips') === "twiceHourly") {
	wp_clear_scheduled_hook('pull_clips');
}
if( !wp_next_scheduled( 'pull_clips' ) ) {
   wp_schedule_event( time(), 'tenMinutes', 'pull_clips' );
}

add_action( 'pull_clips', 'pull_clips_cron_handler' );
function pull_clips_cron_handler() {
	pull_all_clips();
}

wp_clear_scheduled_hook('populate_vote_db');

if( !wp_next_scheduled( 'clean_pulled_clips_db' ) ) {
   wp_schedule_event( time(), 'daily', 'clean_pulled_clips_db' );
}
add_action( 'clean_pulled_clips_db', 'clean_pulled_clips_db_cron_handler' );
function clean_pulled_clips_db_cron_handler() {
	$clipTimestamp = convertTwitchTimeToTimestamp($clipData['age']);
	if ($clipTimestamp < time() - 14 * 24 * 60 * 60) {
		deleteSlugFromPulledClipsDB($clipData['slug']);
		continue;
	}
}

if( !wp_next_scheduled( 'clean_known_moments_db' ) ) {
   wp_schedule_event( time(), 'daily', 'clean_known_moments_db' );
}
add_action( 'clean_known_moments_db', 'clean_known_moments_db_cron_handler' );
function clean_known_moments_db_cron_handler() {
	global $wpdb;
	$table_name = $wpdb->prefix . "known_moments_db";
	$query = "SELECT * FROM $table_name";
	$knownMoments = $wpdb->get_results($query, ARRAY_A);
	$oneMonthAgo = time() - 30 * 24 * 60 * 60;
	foreach ($knownMoments as $moment) {
		if ($moment['time'] < $oneMonthAgo) {
			$wpdb->delete($table_name, array("id" => $moment['id']));
		}
	}
}

if( !wp_next_scheduled( 'repponing' ) ) {
   wp_schedule_event( time(), 'daily', 'repponing' );
}
add_action( 'repponing', 'repponing' );
function repponing() {
	$currentMonth = date('n');
	$lastRepponingMonth = get_option("lastRepponingMonth", 0);
	if ($lastRepponingMonth !== $currentMonth) {
		update_option("lastRepponingMonth", $currentMonth);
		global $wpdb;
		$table = $wpdb->prefix . "people_db";
		$people = $wpdb->get_results("
			SELECT hash, rep, giveableRep, lastRepTime
			FROM $table
			WHERE rep > 4 
			", ARRAY_A
		);

		$activityThreshold = time() - 60 * 60 * 24 * 14;
		foreach ($people as $person) {
			$oldRep = (int)$person['rep'];
			if ($oldRep <= 20 && (int)$person['lastRepTime'] > $activityThreshold) {continue;}
			$newRep = round($oldRep * .9);
			$oldGiveable = (int)$person['giveableRep'];
			$newGiveable = $oldGiveable + $oldRep - $newRep;
			if ($newGiveable > 200) {$newGiveable = 200;}
			$personArray = array(
				'hash' => $person['hash'], 
				'rep' => $newRep,
				'giveableRep' => $newGiveable,
			);
			editPersonInDB($personArray);
		}
	}
}

?>