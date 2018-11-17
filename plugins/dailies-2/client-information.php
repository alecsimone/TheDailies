<?php

add_action("wp_enqueue_scripts", "client_information");
function client_information() {
	$version = '-v1.931';
	if (is_page('weed') || is_page('1r') || is_page('scout')) {
		wp_register_script( 'weedScripts', get_template_directory_uri() . '/Bundles/weed-bundle' . $version . '.js', ['jquery'], '', true );
		$weedData = generateWeedData();
		wp_localize_script('weedScripts', 'weedData', $weedData);
		wp_enqueue_script('weedScripts');
	} else if (is_page('hopefuls')) {
		wp_register_script( 'hopefulsScripts', get_template_directory_uri() . '/Bundles/hopefuls-bundle' . $version . '.js', ['jquery'], '', true );
		// $hopefulsData = generateHopefulsData();
		// wp_localize_script('hopefulsScripts', 'hopefulsData', $hopefulsData);
		wp_enqueue_script('hopefulsScripts');
	}
}

function generateWeedData() {
	$weedDataArray = array();
	$weedDataArray['streamList'] = generateTodaysStreamlist();
	$lastUpdateTime = get_option("lastClipUpdateTime");
	if (!$lastUpdateTime) {
		$weedPageID = getPageIDBySlug('weed');
		$lastUpdateTime = get_post_meta($weedPageID, 'lastClipTime', true);
	}
	$weedDataArray['lastUpdate'] = $lastUpdateTime;
	$weedDataArray['cutoffTimestamp'] = clipCutoffTimestamp();
	$weedDataArray['clips'] = getCleanPulledClipsDB();
	$weedDataArray['seenSlugs'] = getCurrentUsersSeenSlugs();
	
	return $weedDataArray;
}

function generateHopefulsData() {
	$hopefulsData = getHopefuls();
	return $hopefulsData;
}

?>