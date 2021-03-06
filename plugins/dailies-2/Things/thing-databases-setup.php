<?php 
global $seen_slugs_db_version;
global $pulled_clips_db_version;
global $cip_comments_db_version;
global $known_moments_db_version;
global $submissions_db_version;
global $nuke_records_db_version;
$seen_slugs_db_version = '0.2';
$pulled_clips_db_version = '0.7';
$clip_comments_db_version = '0.1';
$known_moments_db_version = '0.1';
$submissions_db_version = '0.2';
$nuke_records_db_version = '0.2';

function createSeenSlugsDB() {
	global $wpdb;
	global $seen_slugs_db_version;
	$installed_version = get_option("seen_slugs_db_version");

	if ($installed_version != $seen_slugs_db_version) {
		$table_name = $wpdb->prefix . "seen_slugs_db";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = 
			"CREATE TABLE " . $table_name . " (
			id INT NOT NULL AUTO_INCREMENT,
			hash VARCHAR(255) NOT NULL,
			slug NVARCHAR(255) NOT NULL,
			vodlink VARCHAR(255) DEFAULT 'none',
			vote SMALLINT NOT NULL,
			time VARCHAR(64) NOT NULL,
			PRIMARY KEY  (id)
		) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		basicPrint($wpdb->last_error);

		update_option('seen_slugs_db_version', $seen_slugs_db_version);
	}
}

add_action('plugins_loaded', 'update_seen_slugs_db_check');
function update_seen_slugs_db_check() {
	global $seen_slugs_db_version;
	if (get_site_option("seen_slugs_db_version") != $seen_slugs_db_version) {
		createSeenSlugsDB();
	}
}

function createPulledClipsDB() {
	global $wpdb;
	global $pulled_clips_db_version;
	$installed_version = get_option("pulled_clips_db_version");

	if ($installed_version != $pulled_clips_db_version) {
		$table_name = $wpdb->prefix . "pulled_clips_db";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = 
			"CREATE TABLE " . $table_name . " (
			id INT NOT NULL AUTO_INCREMENT,
			slug NVARCHAR(255) NOT NULL,
			title NVARCHAR(1024) NOT NULL,
			score SMALLINT NOT NULL,
			votecount SMALLINT DEFAULT 0 NOT NULL,
			views INT NOT NULL,
			age VARCHAR(64) NOT NULL,
			source VARCHAR(64),
			sourcepic VARCHAR(255) DEFAULT 'unknown',
			type VARCHAR(64) NOT NULL,
			vodlink VARCHAR(255) DEFAULT 'none',
			thumb VARCHAR(255) DEFAULT 'none',
			clipper NVARCHAR(255) DEFAULT 'unknown',
			nuked BOOLEAN DEFAULT false,
			PRIMARY KEY  (id)
		) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		basicPrint($wpdb->last_error);

		update_option('pulled_clips_db_version', $pulled_clips_db_version);
	}
}

add_action('plugins_loaded', 'update_pulled_clips_db_check');
function update_pulled_clips_db_check() {
	global $pulled_clips_db_version;
	if (get_site_option("pulled_clips_db_version") != $pulled_clips_db_version) {
		createPulledClipsDB();
	}
}

function createClipCommentsDB() {
	global $wpdb;
	global $clip_comments_db_version;
	$installed_version = get_option("clip_comments_db_version");

	if ($installed_version != $clip_comments_db_version) {
		$table_name = $wpdb->prefix . "clip_comments_db";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = 
			"CREATE TABLE " . $table_name . " (
			id INT NOT NULL AUTO_INCREMENT,
			slug NVARCHAR(255) NOT NULL,
			commenter NVARCHAR(255) NOT NULL,
			comment NVARCHAR(2200) NOT NULL,
			score SMALLINT NOT NULL,
			time VARCHAR(64) NOT NULL,
			replytoid INT DEFAULT 0,
			PRIMARY KEY  (id)
		) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		basicPrint($wpdb->last_error);

		update_option('clip_comments_db_version', $clip_comments_db_version);
	}
}

add_action('plugins_loaded', 'update_clip_comments_db_check');
function update_clip_comments_db_check() {
	global $clip_comments_db_version;
	if (get_site_option("clip_comments_db_version") != $clip_comments_db_version) {
		createClipCommentsDB();
	}
}

function createKnownMomentsDB() {
	global $wpdb;
	global $known_moments_db_version;
	$installed_version = get_option("known_moments_db_version");

	if ($installed_version != $known_moments_db_version) {
		$table_name = $wpdb->prefix . "known_moments_db";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = 
			"CREATE TABLE " . $table_name . " (
			id INT NOT NULL AUTO_INCREMENT,
			moment NVARCHAR(255) NOT NULL,
			type VARCHAR(64) NOT NULL,
			time INT(8) NOT NULL,
			PRIMARY KEY  (id)
		) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		basicPrint($wpdb->last_error);

		update_option('known_moments_db_version', $known_moments_db_version);
	}
}

add_action('plugins_loaded', 'update_known_moments_db_check');
function update_known_moments_db_check() {
	global $known_moments_db_version;
	if (get_site_option("known_moments_db_version") != $known_moments_db_version) {
		createKnownMomentsDB();
	}
}

function createSubmissionsDB() {
	global $wpdb;
	global $submissions_db_version;
	$installed_version = get_option("submissions_db_version");

	if ($installed_version != $submissions_db_version) {
		$table_name = $wpdb->prefix . "submissions_db";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = 
			"CREATE TABLE " . $table_name . " (
			id INT NOT NULL AUTO_INCREMENT,
			slug NVARCHAR(255) NOT NULL,
			submitter NVARCHAR(64) NOT NULL,
			type VARCHAR(64) NOT NULL,
			title NVARCHAR(1024) NOT NULL,
			PRIMARY KEY  (id)
		) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		basicPrint($wpdb->last_error);

		update_option('submissions_db_version', $submissions_db_version);
	}
}

add_action('plugins_loaded', 'update_submissions_db_check');
function update_submissions_db_check() {
	global $submissions_db_version;
	if (get_site_option("submissions_db_version") != $submissions_db_version) {
		createSubmissionsDB();
	}
}

function createNukeRecordsDB() {
	global $wpdb;
	global $nuke_records_db_version;
	$installed_version = get_option("nuke_records_db_version");

	if ($installed_version != $nuke_records_db_version) {
		$table_name = $wpdb->prefix . "nuke_records_db";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = 
			"CREATE TABLE " . $table_name . " (
			id INT NOT NULL AUTO_INCREMENT,
			slug NVARCHAR(255) NOT NULL,
			nuker NVARCHAR(255) NOT NULL,
			time INT NOT NULL,
			PRIMARY KEY  (id)
		) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		basicPrint($wpdb->last_error);

		update_option('nuke_records_db_version', $nuke_records_db_version);
	}
}

add_action('plugins_loaded', 'update_nuke_records_db_check');
function update_nuke_records_db_check() {
	global $nuke_records_db_version;
	if (get_site_option("nuke_records_db_version") != $nuke_records_db_version) {
		createNukeRecordsDB();
	}
}

?>