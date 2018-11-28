<?php

add_action("wp_enqueue_scripts", "client_information");
function client_information() {
	$version = '-v1.931';
	if ( !is_page() && !is_attachment() ) {
		wp_register_script( 'mainScripts', get_template_directory_uri() . '/Bundles/main-bundle' . $version . '.js', ['jquery'], '', true );
		$nonce = wp_create_nonce('vote_nonce');
		$main_script_data = array(
			'nonce' => $nonce,
		);
		if (is_home()) {
			$main_script_data['dayOne'] = generateDayOneData();
			$main_script_data['firstWinner'] = generateFirstWinner();
		} elseif (is_single()) {
			$main_script_data['singleData'] = generateSingleData();
		} elseif (is_search()) {
			$main_script_data['headerData'] = generateSearchHeaderData();
			$main_script_data['initialArchiveData'] = generateSearchResultsData();
		} else {
			$main_script_data['headerData'] = generateArchiveHeaderData();
			$main_script_data['initialArchiveData'] = generateInitialArchivePostData();
		}
		wp_localize_script('mainScripts', 'dailiesMainData', $main_script_data);
		wp_enqueue_script( 'mainScripts' );
	} else if (is_page('weed') || is_page('1r') || is_page('scout')) {
		wp_register_script( 'weedScripts', get_template_directory_uri() . '/Bundles/weed-bundle' . $version . '.js', ['jquery'], '', true );
		$weedData = generateWeedData();
		wp_localize_script('weedScripts', 'weedData', $weedData);
		wp_enqueue_script('weedScripts');
	} else if (is_page('hopefuls')) {
		wp_register_script( 'hopefulsScripts', get_template_directory_uri() . '/Bundles/hopefuls-bundle' . $version . '.js', ['jquery'], '', true );
		// $hopefulsData = generateHopefulsData();
		// wp_localize_script('hopefulsScripts', 'hopefulsData', $hopefulsData);
		wp_enqueue_script('hopefulsScripts');
	} else if (is_page('Live')) {
		wp_register_script( 'liveScripts', get_template_directory_uri() . '/Bundles/live-bundle' . $version . '.js', ['jquery'], '', true );
		$nonce = wp_create_nonce('vote_nonce');
		$livePageObject = get_page_by_path('live');
		$liveID = $livePageObject->ID;
		$resetTime = get_post_meta($liveID, 'liveResetTime', true);
		$resetTime = $resetTime / 1000;
		$wordpressUsableTime = date('c', $resetTime);
		$liveData = array(
			'nonce' => $nonce,
			'postData' => generateLivePostsData(),
			'resetTime' => $resetTime,
			'wordpressUsableTime' => $wordpressUsableTime,
		);
		wp_localize_script('liveScripts', 'liveData', $liveData);
		wp_enqueue_script('liveScripts');
	} else if (is_page('contender-voteboard')) {
		wp_register_script( 'contenderVoteboardScripts', get_template_directory_uri() . '/Bundles/contendervoteboard-bundle' . $version . '.js', ['jquery'], '', true );
		$livePageObject = get_page_by_path('live');
		$liveID = $livePageObject->ID;
		$resetTime = get_post_meta($liveID, 'liveResetTime', true);
		$resetTime = $resetTime / 1000;
		$wordpressUsableTime = date('c', $resetTime);
		$contenderVoteboardData = array(
			'resetTime' => $wordpressUsableTime,
		);
		wp_localize_script('contenderVoteboardScripts', 'contenderVoteboardData', $contenderVoteboardData);
		wp_enqueue_script( 'contenderVoteboardScripts');
	} else if (is_page('live-voting-machine')) {
		wp_register_script( 'liveVotingMachineScripts', get_template_directory_uri() . '/Bundles/livevotingmachine-bundle' . $version . '.js', ['jquery'], '', true );
		$liveVoters = getVoterDisplayInfoForSlug("live");
		wp_localize_script('liveVotingMachineScripts', 'liveVoters', $liveVoters);
		wp_enqueue_script( 'liveVotingMachineScripts');
	} else if (is_page('live-votebar')) {
		wp_register_script( 'liveVoteBarScripts', get_template_directory_uri() . '/Bundles/livevotebar-bundle' . $version . '.js', ['jquery'], '', true );
		$liveData = getLive();
		wp_localize_script('liveVoteBarScripts', 'liveData', $liveData);
		wp_enqueue_script( 'liveVoteBarScripts');
	}
}

function generateDayOneData() {
	date_default_timezone_set('UTC');
	$today = new DateTime();
	$year = $today->format('Y');
	$month = $today->format('n');
	$day = $today->format('j');

	$dayOneArgs = array(
		'category_name' => 'noms',
		'posts_per_page' => 10,
		'orderby' => 'meta_value_num',
		'meta_key' => 'votecount',
		'date_query' => array(
			array(
				'year'  => $year,
				'month' => $month,
				'day'   => $day,
				),
			),
		);
	$postDataNoms = get_posts($dayOneArgs);

	$i = 0;
	while ( !$postDataNoms && $i < 14 ) :
		$today->add(DateInterval::createFromDateString('yesterday'));
		$year = $today->format('Y');
		$month = $today->format('n');
		$day = $today->format('j');
		$newNomArgs = array(
			'category_name' => 'noms',
			'posts_per_page' => 10,
			'orderby' => 'meta_value_num',
			'meta_key' => 'votecount',
			'date_query' => array(
				array(
					'year'  => $year,
					'month' => $month,
					'day'   => $day,
					),
				),
			);
		$postDataNoms = get_posts($newNomArgs);
		$i++;
	endwhile;
	$dayOnePostDatas = [];
	$dayOneVoteDataArray = [];
	foreach ($postDataNoms as $post) {
		setup_postdata($post);
		$postData = buildPostDataObject($post->ID);
		$dayOnePostDatas[] = convertPostDataObjectToClipdata($postData);
	}	
	$dayOnePostData = array(
		'date' => array(
			'year'  => $year,
			'month' => $month,
			'day'   => $day,
			),
		'postDatas' => $dayOnePostDatas,
		);
	return $dayOnePostData;
}

function generateFirstWinner() {
	$winnerArgs = array(
		'tag' => 'winners',
		'category_name' => 'noms',
		'posts_per_page' => 1,
		);
	$postDataWinners = get_posts($winnerArgs);
	$post = $postDataWinners[0];
	setup_postdata($post); 
	$winnerDataObject = buildPostDataObject($post->ID);
	$firstWinnerData = convertPostDataObjectToClipdata($winnerDataObject);
	return $firstWinnerData;
}

function generateWeedData() {
	$weedDataArray = array();
	$weedDataArray['streamList'] = generateTodaysStreamlist();
	// $lastUpdateTime = get_option("lastClipUpdateTime");
	// if (!$lastUpdateTime) {
	// 	$weedPageID = getPageIDBySlug('weed');
	// 	$lastUpdateTime = get_post_meta($weedPageID, 'lastClipTime', true);
	// }
	// $weedDataArray['lastUpdate'] = $lastUpdateTime;
	// $weedDataArray['cutoffTimestamp'] = clipCutoffTimestamp();
	$weedDataArray['clips'] = getCleanPulledClipsDB();
	$weedDataArray['seenSlugs'] = getCurrentUsersSeenSlugs();
	
	return $weedDataArray;
}

function generateHopefulsData() {
	$hopefulsData = getHopefuls();
	return $hopefulsData;
}

function generateLivePostsData() {
	$livePageObject = get_page_by_path('live');
	$liveID = $livePageObject->ID;
	$resetTime = get_post_meta($liveID, 'liveResetTime', true);
	$resetTime = $resetTime / 1000;
	$wordpressUsableTime = date('c', $resetTime);
	$livePostArgs = array(
		'category__not_in' => 4,
		'posts_per_page' => 50,
		'date_query' => array(
			array(
			//	'after' => '240 hours ago',
				'after' => $wordpressUsableTime,
			)
		)
	);
	$postDataLive = get_posts($livePostArgs);
	$postDatas = [];
	foreach ($postDataLive as $post) {
		$postID = $post->ID;
		$postDataObject =  buildPostDataObject($postID, 'postDataObj', true);
		$postDatas[$postID] = convertPostDataObjectToClipdata($postDataObject);
	}
	return $postDatas;
}

?>