<?php

function getCurrentUsersSeenSlugs() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'vote_db';

	$hash = getPersonsHash(get_current_user_id());

	$seenSlugs = $wpdb->get_results(
		"
		SELECT *
		FROM $table_name
		WHERE hash = '$hash'
		",
		ARRAY_A
	);

	return $seenSlugs;
}

?>