<?php

function rest_get_post_meta_cb( $object, $field_name, $request ) {
    return get_post_meta( $object[ 'id' ], $field_name );
}
function rest_update_post_meta_cb( $value, $object, $field_name ) {
    return update_post_meta( $object[ 'id' ], $field_name, $value );
}

add_action( 'rest_api_init', function() {
	register_api_field( 'post',
		'postDataObj',
		array(
		   'get_callback'    => 'rest_get_post_meta_cb',
		   'update_callback' => 'rest_update_post_meta_cb',
		   'schema'          => null,
		)
	);
});
add_action( 'rest_api_init', function() {
	register_api_field( 'post',
		'votecount',
		array(
		   'get_callback'    => 'rest_get_post_meta_cb',
		   'update_callback' => 'rest_update_post_meta_cb',
		   'schema'          => null,
		)
	);
});
add_action( 'rest_api_init', function() {
	register_api_field( 'post',
		'voteledger',
		array(
		   'get_callback'    => 'rest_get_post_meta_cb',
		   'update_callback' => 'rest_update_post_meta_cb',
		   'schema'          => null,
		)
	);
});
add_action( 'rest_api_init', function() {
	register_api_field( 'post',
		'guestlist',
		array(
		   'get_callback'    => 'rest_get_post_meta_cb',
		   'update_callback' => 'rest_update_post_meta_cb',
		   'schema'          => null,
		)
	);
});

add_filter('rest_endpoints', 'my_modify_rest_routes');
function my_modify_rest_routes( $routes ) {
  array_push( $routes['/wp/v2/posts'][0]['args']['orderby']['enum'], 'meta_value_num' );
  return $routes;
}

// add custom fields query to WP REST API v2
// https://1fix.io/blog/2015/07/20/query-vars-wp-api/
function my_allow_meta_query( $valid_vars ) {
    $valid_vars = array_merge( $valid_vars, array( 'meta_key', 'meta_value' ) );
    return $valid_vars;
}
add_filter( 'rest_query_vars', 'my_allow_meta_query' ); 

add_action( 'rest_api_init', 'dailies_add_extra_data_to_rest' );
function dailies_add_extra_data_to_rest() {
	register_rest_field('post', 'postDataObj', array(
			'get_callback' => function($postData) {
				$thisID = $postData[id];
				$postDataObj = buildPostDataObject($thisID);
				return $postDataObj;
			},
		)
	);
	register_rest_field('post', 'clipdata', array(
			'get_callback' => function($postData) {
				$thisID = $postData[id];
				$postDataObj = buildPostDataObject($thisID);
				$clipdata = convertPostDataObjectToClipdata($postDataObj);
				return $clipdata;
			},
		)
	);
};

function get_voter_history_for_rest($data) {
	$yourVotesIDs = getPersonVoteIDs($data['id']);
	$args = array(
		'posts_per_page' => 10,
		'paged' => $paged,
		'offset' => $data['offset'],
		'post__in' => $yourVotesIDs,
	);
	$posts = get_posts($args);

	if (empty($posts)) {
		return null;
	}

	$allPostData = array();
	foreach ($posts as $key => $value) {
		$postDataObj = buildPostDataObject($value->ID);
		$allPostData[$key] = array(
			'postDataObj' => $postDataObj,
			'id' => $postDataObj['id'],
			'votecount' => array($postDataObj['votecount']),
			'voteledger' => array($postDataObj['voteledger']),
			'guestlist' => array($postDataObj['guestlist']),
		);
	}

	return $allPostData;
}

add_action( 'rest_api_init', 'dailies_add_your_votes_to_rest' );
function dailies_add_your_votes_to_rest() {
	register_rest_route('dailies-rest/v1', 'voter/id=(?P<id>\d+)&offset=(?P<offset>\d+)', array(
		'methods' => 'GET',
		'callback' => 'get_voter_history_for_rest',
	));
}

add_action( 'rest_api_init', 'dailies_add_clip_comments_to_rest' );
function dailies_add_clip_comments_to_rest() {
	register_rest_route('dailies-rest/v1', 'clipcomments/slug=(?P<slug>[\w\-]+)', array(
		'methods' => 'GET',
		'callback' => 'get_clip_comments_for_rest',
	));
}

function get_clip_comments_for_rest($data) {
	$comments = getCommentsForSlug($data['slug']);
	foreach ($comments as $key => $value) {
		if ($comments[$key]['commenter'] === "Anon") {
			$comments[$key]['pic'] = get_site_url() . '/wp-content/uploads/2017/03/default_pic.jpg';
		} else {
			$commenter = getPersonInDB($comments[$key]['commenter']);
			if ($commenter['dailiesDisplayName'] == '--') {
				$comments[$key]['commenter'] = $commenter['twitchName'];
			} else {
				$comments[$key]['commenter'] = $commenter['dailiesDisplayName'];
			}
			$comments[$key]['pic'] = getPicForPerson($commenter);
		}
	}
	$sortedComments = array();
	foreach ($comments as $key => $data) {
		$sortedComments[$key] = $data['time'];
	}
	array_multisort($sortedComments, SORT_ASC, $comments);
	return $comments;
}

add_action( 'rest_api_init', 'dailies_add_clip_voters_to_rest' );
function dailies_add_clip_voters_to_rest() {
	register_rest_route('dailies-rest/v1', 'clipvoters/slug=(?P<slug>[\w\-]+)', array(
		'methods' => 'GET',
		'callback' => 'get_clip_voters_for_rest',
	));
}

function get_clip_voters_for_rest($data) {
	$rawVoterData = getVotersForSlug($data['slug']);
	$voters = [];
	foreach ($rawVoterData as $key => $data) {
		$voters[$key]['hash'] = $data['hash'];
		$voter = getPersonInDB($data['hash']);
		$voters[$key]['picture'] = $voter['picture'];
		$voters[$key]['name'] = $voter['dailiesDisplayName'];
		$voters[$key]['weight'] = $data['weight'];
	}
	return $voters;
}

add_action( 'rest_api_init', 'dailies_add_clip_nukers_to_rest' );
function dailies_add_clip_nukers_to_rest() {
	register_rest_route('dailies-rest/v1', 'clipnukers/slug=(?P<slug>[\w\-]+)', array(
		'methods' => 'GET',
		'callback' => 'get_clip_nukers_for_rest',
	));
}

function get_clip_nukers_for_rest($data) {
	$rawNukerData = getNukersForSlug($data['slug']);
	$nukers = [];
	foreach ($rawNukerData as $key => $data) {
		$nukers[$key]['hash'] = $data['nuker'];
		$nuker = getPersonInDB($data['nuker']);
		$nukers[$key]['picture'] = $nuker['picture'];
		$nukers[$key]['name'] = $nuker['dailiesDisplayName'];
	}
	return $nukers;
}

add_action( 'rest_api_init', 'dailies_add_sluglist_voters_to_rest' );
function dailies_add_sluglist_voters_to_rest() {
	register_rest_route('dailies-rest/v1', 'sluglistVoters/slugs=(?P<slugs>[\w\-\,]+)', array(
		'methods' => 'GET',
		'callback' => 'get_sluglist_voters_for_rest',
	));
}

function get_sluglist_voters_for_rest($data) {
	$slugs = explode(',', $data['slugs']);
	$voterData = [];
	foreach ($slugs as $slug) {
		$voters = getVoterDisplayInfoForSlug($slug);
		$voterData[$slug] = $voters;
	}
	return $voterData;
}

add_action( 'rest_api_init', 'dailies_rest_hopefuls' );
function dailies_rest_hopefuls() {
	register_rest_route('dailies-rest/v1', 'hopefuls', array(
		'methods' => 'GET',
		'callback' => 'getHopefuls',
	));
}

add_action( 'rest_api_init', 'dailies_rest_live' );
function dailies_rest_live() {
	register_rest_route('dailies-rest/v1', 'live', array(
		'methods' => 'GET',
		'callback' => 'getLive',
	));
}

add_action( 'rest_api_init', 'dailies_rest_live_voters' );
function dailies_rest_live_voters() {
	register_rest_route('dailies-rest/v1', 'live-voters', array(
		'methods' => 'GET',
		'callback' => 'getLiveVoters',
	));
}

add_action( 'rest_api_init', 'dailies_add_giveable_rep_check_to_rest' );
function dailies_add_giveable_rep_check_to_rest() {
	register_rest_route('dailies-rest/v1', 'giveablerep/person=(?P<person>[\w\-\,]+)', array(
		'methods' => 'GET',
		'callback' => 'get_giveable_rep_for_rest',
	));
}

function get_giveable_rep_for_rest($data) {
	$person = $data['person'];
	$giveableRep = getGiveableRep($person);
	return $giveableRep;
}

?>