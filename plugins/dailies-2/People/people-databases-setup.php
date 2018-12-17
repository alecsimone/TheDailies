<?php

global $people_db_version;
$people_db_version = '0.4';

function createPeopleDB() {
	global $wpdb;
	global $people_db_version;
	$installed_version = get_option("people_db_version");

	if ($installed_version != $people_db_version) {
		$table_name = $wpdb->prefix . "people_db";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = 
			"CREATE TABLE " . $table_name . " (
			id INT NOT NULL AUTO_INCREMENT,
			hash VARCHAR(255) NOT NULL,
			picture VARCHAR(1028) DEFAULT 'none',
			dailiesID INT DEFAULT '-1',
			dailiesDisplayName NVARCHAR(255) DEFAULT '--',
			twitchName NVARCHAR(64) DEFAULT '--',
			rep TINYINT DEFAULT 1,
			giveableRep SMALLINT DEFAULT 0,
			lastRepTime VARCHAR(64) DEFAULT '--',
			lastScoutRepTime VARCHAR(64) DEFAULT '--',
			email NVARCHAR(320) DEFAULT '--',
			provider VARCHAR(64) DEFAULT '--',
			role VARCHAR(64) DEFAULT '--',
			starID INT DEFAULT '-1',
			special TINYINT DEFAULT false,
			PRIMARY KEY  (id)
		) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		basicPrint($wpdb->last_error);

		update_option('people_db_version', $people_db_version);
	}
}

add_action('plugins_loaded', 'update_people_db_check');
function update_people_db_check() {
	global $people_db_version;
	if (get_site_option("people_db_version") != $people_db_version) {
		createPeopleDB();
	}
}

global $rep_transfers_db_version;
$rep_transfers_db_version = '0.1';

function createRepTransfersDB() {
	global $wpdb;
	global $rep_transfers_db_version;
	$installed_version = get_option("rep_transfers_db_version");

	if ($installed_version != $rep_transfers_db_version) {
		$table_name = $wpdb->prefix . "rep_transfers_db";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = 
			"CREATE TABLE " . $table_name . " (
			id INT NOT NULL AUTO_INCREMENT,
			giver VARCHAR(255) NOT NULL,
			receiver VARCHAR(255) NOT NULL,
			rep TINYINT NOT NULL,
			time VARCHAR(64) NOT NULL,
			PRIMARY KEY  (id)
		) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		basicPrint($wpdb->last_error);

		update_option('rep_transfers_db_version', $rep_transfers_db_version);
	}
}

add_action('plugins_loaded', 'update_rep_transfers_db_check');
function update_rep_transfers_db_check() {
	global $rep_transfers_db_version;
	if (get_site_option("rep_transfers_db_version") != $rep_transfers_db_version) {
		createRepTransfersDB();
	}
}

// add_action('init', 'populateGiveableRepColumns');
function populateGiveableRepColumns() {
	$lastProcessedPeopleRow = get_option("lastProcessedPeopleRow");
	if (!$lastProcessedPeopleRow) {$lastProcessedPeopleRow = 0;}
	if ($lastProcessedPeopleRow === "done") {return;}
	
	global $wpdb;
	$table = $wpdb->prefix . "people_db";
	$lastRowToProcess = (int)$lastProcessedPeopleRow + 500;
	$rowsToProcess = $wpdb->get_results("
	SELECT id, rep
	FROM $table
	WHERE id > $lastProcessedPeopleRow 
		AND id <= $lastRowToProcess
	", ARRAY_A);

	foreach ($rowsToProcess as $row) {
		$newRep = (int)$row['rep'] * .25;
		if ($newRep < 1) {$newRep = 1;}
		$wpdb->update(
			$table,
			array(
				// 'giveableRep' => $row['rep'],
				'rep' => $newRep,
			),
			array(
				'id' => $row['id'],
			)
		);
	}

	if (count($rowsToProcess) === 0) {
		$lastRowToProcess = "done";
	}

	update_option("lastProcessedPeopleRow", $lastRowToProcess);

}

?>