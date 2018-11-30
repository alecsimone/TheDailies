<?php

function getCurrentUsersSeenSlugs() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'vote_db';

	$hash = getPersonsHash(get_current_user_id());
	$cutoff = clipCutoffTimestamp() - 72 * 60 * 60;

	$seenSlugs = $wpdb->get_results(
		"
		SELECT *
		FROM $table_name
		WHERE hash = '$hash' AND time > '$cutoff'
		",
		ARRAY_A
	);

	return $seenSlugs;
}

?>