<?php

global $people_db_version;
$people_db_version = '0.5';

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
			manualPicture VARCHAR(1028) DEFAULT 'none',
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
			contribution TINYINT DEFAULT 0,
			starID INT DEFAULT '-1',
			special TINYINT DEFAULT 0,
			hasVeto TINYINT DEFAULT 0,
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
// function populateGiveableRepColumns() {
// 	$lastProcessedPeopleRow = get_option("lastProcessedPeopleRow");
// 	if (!$lastProcessedPeopleRow) {$lastProcessedPeopleRow = 0;}
// 	if ($lastProcessedPeopleRow === "done") {return;}
	
// 	global $wpdb;
// 	$table = $wpdb->prefix . "people_db";
// 	$lastRowToProcess = (int)$lastProcessedPeopleRow + 500;
// 	$rowsToProcess = $wpdb->get_results("
// 	SELECT id, rep
// 	FROM $table
// 	WHERE id > $lastProcessedPeopleRow 
// 		AND id <= $lastRowToProcess
// 	", ARRAY_A);

// 	foreach ($rowsToProcess as $row) {
// 		$newRep = (int)$row['rep'] * .25;
// 		if ($newRep < 1) {$newRep = 1;}
// 		$wpdb->update(
// 			$table,
// 			array(
// 				// 'giveableRep' => $row['rep'],
// 				'rep' => $newRep,
// 			),
// 			array(
// 				'id' => $row['id'],
// 			)
// 		);
// 	}

// 	if (count($rowsToProcess) === 0) {
// 		$lastRowToProcess = "done";
// 	}

// 	update_option("lastProcessedPeopleRow", $lastRowToProcess);

// }

add_action( 'show_user_profile', 'my_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'my_show_extra_profile_fields' );

function my_show_extra_profile_fields( $user ) { ?>
 
    <h3>Extra profile information</h3> 
    <table class="form-table">
        <tr>
            <th><label for="rep">Rep</label></th>
            <td>
                <input type="text" name="rep" id="rep" value="<?php echo esc_attr( get_the_author_meta( 'rep', $user->ID ) ); ?>" class="regular-text" /><br />
                <span class="description">Your Rep</span>
            </td>
        </tr>
    </table>
    <table class="form-table">
        <tr>
            <th><label for="customProfilePic">Custom Profile Picture</label></th>
            <td>
                <input type="text" name="customProfilePic" id="customProfilePic" value="<?php echo esc_attr( get_the_author_meta( 'customProfilePic', $user->ID ) ); ?>" class="regular-text" /><br />
                <span class="description">Add a profile picture</span>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <th><label for="customProfilePic">Here's what that looks like: </label></th>
            <td>
            	<img src="<?php echo esc_attr( get_the_author_meta( 'customProfilePic', $user->ID ) ); ?>" class="adminCustomProfilePicture">
            </td>
        </tr>
    </table>    
<?php }

add_action( 'personal_options_update', 'my_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'my_save_extra_profile_fields' );
add_action( 'admin_head', 'custom_profile_pic_css');
function custom_profile_pic_css() {
	echo '<style>img.adminCustomProfilePicture {max-width: 500px; height: auto;}</style>';
}


function my_save_extra_profile_fields( $user_id ) {
 
    if ( !current_user_can( 'edit_users', $user_id ) )
        return false;
    update_user_meta( absint( $user_id ), 'rep', wp_kses_post( $_POST['rep'] ) );
    update_user_meta( absint( $user_id ), 'customProfilePic', wp_kses_post( $_POST['customProfilePic'] ) );
}

?>