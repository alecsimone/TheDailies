<?php

add_action("wp_enqueue_scripts", "client_information");
function client_information() {
	$version = '-v2.332';
	$styleVersion = '-v2.313';
	wp_register_script('globalScripts', plugins_url() . '/dailies-2/JS/Bundles/global-bundle' . $version . '.js', ['jquery'], '', true );
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
	wp_enqueue_style( 'globalStyles', get_template_directory_uri() . '/style' . $styleVersion . '.css');
	if ( !is_page() && !is_attachment() ) {
		wp_register_script( 'mainScripts', plugins_url() . '/dailies-2/JS/Bundles/main-bundle' . $version . '.js', ['jquery'], '', true );
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
	} else if (is_page('Submit')) {
		wp_enqueue_script( 'scheduleScripts', plugins_url() . '/dailies-2/JS/Bundles/submit-bundle' . $version . '.js', ['jquery'], '', true );
	} else if (is_page('your-votes')) {
		wp_register_script( 'mainScripts', plugins_url() . '/dailies-2/JS/Bundles/main-bundle' . $version . '.js', ['jquery'], '', true );
		$nonce = wp_create_nonce('vote_nonce');
		$main_script_data = array(
			'nonce' => $nonce,
		);
		$main_script_data['headerData'] = generateYourVotesHeaderData();
		$main_script_data['initialArchiveData'] = generateYourVotesPostData();
		wp_localize_script('mainScripts', 'dailiesMainData', $main_script_data);
		wp_enqueue_script( 'mainScripts' );
	} else if (is_page('weed') || is_page('1r') || is_page('scout')) {
		wp_register_script( 'weedScripts', plugins_url() . '/dailies-2/JS/Bundles/weed-bundle' . $version . '.js', ['jquery'], '', true );
		$weedData = generateWeedData();
		wp_localize_script('weedScripts', 'weedData', $weedData);
		wp_enqueue_script('weedScripts');
	} else if (is_page('hopefuls')) {
		wp_register_script( 'hopefulsScripts', plugins_url() . '/dailies-2/JS/Bundles/hopefuls-bundle' . $version . '.js', ['jquery'], '', true );
		// $hopefulsData = generateHopefulsData();
		// wp_localize_script('hopefulsScripts', 'selected', $selected);
		wp_enqueue_script('hopefulsScripts');
	} else if (is_page('Live')) {
		wp_register_script( 'liveScripts', plugins_url() . '/dailies-2/JS/Bundles/live-bundle' . $version . '.js', ['jquery'], '', true );
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
	} else if (is_page('live-voting-machine')) {
		wp_register_script( 'liveVotingMachineScripts', plugins_url() . '/dailies-2/JS/Bundles/livevotingmachine-bundle' . $version . '.js', ['jquery'], '', true );
		$liveSlug = get_option("liveSlug");
		if ($liveSlug == "false") {$liveSlug = "live";}
		$liveVoters = getVoterDisplayInfoForSlug($liveSlug);
		wp_localize_script('liveVotingMachineScripts', 'liveVoters', $liveVoters);
		wp_localize_script('liveVotingMachineScripts', 'liveSlug', $liveSlug);
		wp_enqueue_script( 'liveVotingMachineScripts');
	} else if (is_page('live-votebar')) {
		wp_register_script( 'liveVoteBarScripts', plugins_url() . '/dailies-2/JS/Bundles/livevotebar-bundle' . $version . '.js', ['jquery'], '', true );
		$liveData = getLive();
		wp_localize_script('liveVoteBarScripts', 'liveData', $liveData);
		wp_enqueue_script( 'liveVoteBarScripts');
	}
}

function generateUserData() {
	$userID = get_current_user_id();
	$personRow = getPersonInDB($userID);
	if ($userID === 0) {
		$userPic = get_site_url() . '/wp-content/uploads/2017/03/default_pic.jpg';
	} else {
		$userPic = $personRow['picture'];
	}
	$personData = array(
		'userID' => $userID,
		'userName' => $personRow['dailiesDisplayName'],
		'userRep' => $personRow['rep'],
		'userRepTime' => $personRow['lastRepTime'],
		'userRole' => $personRow['role'],
		'clientIP' => $_SERVER['REMOTE_ADDR'],
		'userPic' => $userPic,
		'hash' => $personRow['hash'],
		'giveableRep' => $personRow['giveableRep'],
	);
	return $personData;
}

function generateDayOneData() {
	date_default_timezone_set('UTC');
	$today = new DateTime();
	$year = $today->format('Y');
	$month = $today->format('n');
	$day = $today->format('j');

	$dayOneArgs = array(
		// 'category_name' => 'noms',
		'posts_per_page' => 20,
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
			'posts_per_page' => 20,
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
	// $weedDataArray['streamList'] = generateTodaysStreamlist();
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

function generateArchiveHeaderData() {
	$thisTerm = get_queried_object();
	$headerData = array(
		'thisTerm' => $thisTerm,
		'logo_url' => get_term_meta($thisTerm->term_id, 'logo', true),
		'twitter' => get_term_meta($thisTerm->term_id, 'twitter', true),
		'twitch' => get_term_meta($thisTerm->term_id, 'twitch', true),
		'youtube' => get_term_meta($thisTerm->term_id, 'youtube', true),
		'website' => get_term_meta($thisTerm->term_id, 'website', true),
		'discord' => get_term_meta($thisTerm->term_id, 'discord', true),
		'donate' => get_term_meta($thisTerm->term_id, 'donate', true),
	);
	return $headerData;
}

function generateYourVotesHeaderData() {
	$thisTerm = 'Your Votes';
	$userID = get_current_user_id();
	$personRow = getPersonInDB($userID);
	$headerData = array(
		'thisTerm' => $thisTerm,
		'logo_url' => $personRow['picture'],
	);
	return $headerData;
}

function generateSearchHeaderData() {
	$thisTerm = get_search_query();
	$headerData = array(
		'thisTerm' => $thisTerm,
	);
	return $headerData;
}

function generateInitialArchivePostData() {
	$thisTerm = get_queried_object();
	$orderby = get_query_var('orderby', 'date');
	$order = get_query_var('order', 'ASC');

	$archiveArgs = array(
		'posts_per_page' => 10,
		'category_name' => 'noms',
		'paged' => $paged,
		'orderby' => $orderby,
		'order' => $order,
		'meta_key' => 'votecount',
		'tax_query' => array(
			array(
				'taxonomy' => $thisTerm->taxonomy,
				'field' => 'slug',
				'terms' => $thisTerm->slug,
				)
			), 
		);
	if ($thisTerm->taxonomy === 'post_tag') {
		unset($archiveArgs['tax_query']);
		$archiveArgs['tag'] = $thisTerm->slug;
	}
	$archivePostDatas = get_posts($archiveArgs);
	$initialPostData = [];
	$initialVoteDataArray = [];
	foreach ($archivePostDatas as $post) {
		setup_postdata($post);
		$postData = buildPostDataObject($post->ID);
		$initialPostDatas[] = $postData;
		$initialVoteDataArray[$post->ID] = array(
			'voteledger' => get_post_meta($post->ID, 'voteledger', true),
			'guestlist' => get_post_meta($post->ID, 'guestlist', true),
			'votecount' => get_post_meta($post->ID, 'votecount', true),
		);
	}
	$initialVoteData = $initialVoteDataArray;
	$initialPostData = $initialPostDatas;
	$orderby = get_query_var('orderby', 'date');
	if ($orderby = 'meta_value_num') {
		$orderby = 'meta_value_num&filter[meta_key]=votecount';
	}
	$initialArchiveData = array(
		'voteData' => $initialVoteData,
		'postData' => $initialPostData,
		'orderby' => $orderby,
		'order' => get_query_var('order', 'ASC'),
	);
	return $initialArchiveData;
}

function generateYourVotesPostData() {
	$yourVotesIDs = getPersonVoteIDs(get_current_user_id());
	$yourVotesArgs = array(
		'posts_per_page' => 10,
		'paged' => $paged,
		'post__in' => $yourVotesIDs,
	);
	$archivePostDatas = get_posts($yourVotesArgs);
	$initialPostData = [];
	$initialVoteDataArray = [];
	foreach ($archivePostDatas as $post) {
		setup_postdata($post);
		$postData = buildPostDataObject($post->ID);
		$initialPostDatas[] = $postData;
		$initialVoteDataArray[$post->ID] = array(
			'voteledger' => get_post_meta($post->ID, 'voteledger', true),
			'guestlist' => get_post_meta($post->ID, 'guestlist', true),
			'votecount' => get_post_meta($post->ID, 'votecount', true),
		);
	}
	$initialVoteData = $initialVoteDataArray;
	$initialPostData = $initialPostDatas;
	$orderby = get_query_var('orderby', 'date');
	if ($orderby = 'meta_value_num') {
		$orderby = 'meta_value_num&filter[meta_key]=votecount';
	}
	$initialArchiveData = array(
		'voteData' => $initialVoteData,
		'postData' => $initialPostData,
		'orderby' => $orderby,
		'order' => get_query_var('order', 'ASC'),
	);
	return $initialArchiveData;
}

function generateSearchResultsData() {
	global $wp_query;
	$searchResultPostObjects = $wp_query->posts;
	$searchResultIDs = [];
	foreach ($searchResultPostObjects as $post) {
		$searchResultIDs[] = $post->ID;
	}
	$initialPostDatas = [];
	$initialVoteData = [];
	foreach ($searchResultIDs as $postID) {
		$postData = buildPostDataObject($postID);
		$initialPostDatas[] = $postData;
		$initialVoteData[$postID] = array(
			'voteledger' => get_post_meta($postID, 'voteledger', true),
			'guestlist' => get_post_meta($postID, 'guestlist', true),
			'votecount' => get_post_meta($postID, 'votecount', true),
		);
	}
	$initialSearchData = array(
		'voteData' => $initialVoteData,
		'postData' => $initialPostDatas,
	);
	return $initialSearchData;
}

function generateSingleData() {
	$post = get_post();
	$postData = buildPostDataObject($post->ID);
	$voteData[$post->ID] = array(
		'voteledger' => get_post_meta($post->ID, 'voteledger', true),
		'guestlist' => get_post_meta($post->ID, 'guestlist', true),
		'votecount' => get_post_meta($post->ID, 'votecount', true),
	);
	$singleData = array(
		'postData' => $postData,
		'voteData' => $voteData,
	);
	return $singleData;
}

?>