<?php add_action("wp_enqueue_scripts", "script_setup", 1);
function script_setup() {
	$version = '-v2.102c';
	wp_register_script('globalScripts', get_template_directory_uri() . '/Bundles/global-bundle' . $version . '.js', ['jquery'], '', true );
	$thisDomain = get_site_url();
	$global_data = array(
		'thisDomain' => $thisDomain,
		'userData' => generateUserData(),
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'logoutURL' => wp_logout_url(),
		'userRow' => getPersonInDB(get_current_user_id()),
	);
	wp_localize_script( 'globalScripts', 'dailiesGlobalData', $global_data );
	wp_enqueue_script( 'globalScripts' );
	wp_enqueue_style( 'globalStyles', get_template_directory_uri() . '/style' . $version . '.css');
	if (is_page('Secret Garden')) {
		wp_register_script('secretGardenScripts', get_template_directory_uri() . '/Bundles/secretGarden-bundle' . $version . '.js', ['jquery'], '', true);
		include( locate_template('schedule.php') );
		$gardenPostObject = get_page_by_path('secret-garden');
		$gardenID = $gardenPostObject->ID;
		$gardenQueryHours = getGardenQueryHours();
		$secretGardenData = array(
			'streamList' => generateStreamList(),
			'cutSlugs' => generateCutSlugs(),
			'pulledClips' => getCleanPulledClipsDB(),
			'submissionSeedlings' => generateSubmissionSeedlingsData(),
			'currentDay' => $todaysSchedule,
			'queryHours' => $gardenQueryHours,
		);
		wp_localize_script('secretGardenScripts', 'gardenData', $secretGardenData);
		wp_enqueue_script('secretGardenScripts');
	} else if (is_page('Submit')) {
		wp_enqueue_script( 'scheduleScripts', get_template_directory_uri() . '/Bundles/submit-bundle' . $version . '.js', ['jquery'], '', true );
	} else if (is_page('your-votes')) {
		wp_register_script( 'mainScripts', get_template_directory_uri() . '/Bundles/main-bundle' . $version . '.js', ['jquery'], '', true );
		$nonce = wp_create_nonce('vote_nonce');
		$main_script_data = array(
			'nonce' => $nonce,
		);
		$main_script_data['headerData'] = generateYourVotesHeaderData();
		$main_script_data['initialArchiveData'] = generateYourVotesPostData();
		wp_localize_script('mainScripts', 'dailiesMainData', $main_script_data);
		wp_enqueue_script( 'mainScripts' );
	} else if (is_page('voteboard')) {
		wp_register_script( 'voteboardScripts', get_template_directory_uri() . '/Bundles/voteboard-bundle' . $version . '.js', ['jquery'], '', true );
		$livePageObject = get_page_by_path('live');
		$liveID = $livePageObject->ID;
		$currentVotersList = get_post_meta($liveID, 'currentVoters', true);
		$voteboardData = array(
			'currentVotersList' => $currentVotersList,
			'twitchUserDB' => buildFreshTwitchUserDB(),
		);
		wp_localize_script('voteboardScripts', 'voteboardData', $voteboardData);
		wp_enqueue_script( 'voteboardScripts');
	} else if (is_page('user-management')) {
		wp_register_script('tablesorter', get_template_directory_uri() . '/Scripts/jquery.tablesorter.min.js', ['jquery'], '', false );
		wp_register_script( 'userManagementScripts', get_template_directory_uri() . '/Bundles/usermanagement-bundle' . $version . '.js', ['jquery'], '', true );
		$userManagementData = getPeopleDB();
		wp_localize_script('userManagementScripts', 'userManagementData', $userManagementData);
		wp_enqueue_script('userManagementScripts');
		wp_enqueue_script('tablesorter');
	}


	/*else if (is_page('Schedule')) {
		wp_enqueue_script( 'scheduleScripts', get_template_directory_uri() . '/Bundles/schedule-bundle.js', ['jquery'], '', true );
	} */
} 

function getGardenQueryHours() {
	$lastNomTimestamp = getLastNomTimestamp();
	$currentTimestamp = time();
	$lastNomSecondsAgo = $currentTimestamp - $lastNomTimestamp;
	$lastNomHoursAgo = $lastNomSecondsAgo / 60 / 60;
	if ($lastNomHoursAgo > 168) {
		$gardenQueryHours = 168;
	} elseif ($lastNomHoursAgo > 28) {
		$gardenQueryHours = floor($lastNomHoursAgo);
	} else {
		$gardenQueryHours = 24;
	}
	return $gardenQueryHours;
}

?>