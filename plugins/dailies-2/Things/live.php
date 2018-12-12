<?php

function getLive() {
	$resetTime = getResetTime();
	$livePostArgs = array(
		'category_name' => 'contenders',
		'posts_per_page' => 50,
		'order' => 'asc',
		'date_query' => array(
			array(
			//	'after' => '240 hours ago',
				'after' => $resetTime,
			)
		)
	);
	$livePosts = get_posts($livePostArgs);
	$liveData = [];
	foreach ($livePosts as $post) {
		setup_postdata($post);
		$postData = buildPostDataObject($post->ID);
		$clipdata = convertPostDataObjectToClipdata($postData);
		$clipdata['eliminated'] = get_post_meta($post->ID, "eliminated", true);
		if ($clipdata['eliminated'] === "") {$clipdata['eliminated'] = "false";}
		$liveData[] = $clipdata;
	}
	return $liveData;
}

function getResetTime() {
	$liveID = getPageIDBySlug('live');
	$resetTime = get_post_meta($liveID, 'liveResetTime', true);
	$resetTime = $resetTime / 1000;
	$wordpressUsableTime = date('c', $resetTime);
	return $wordpressUsableTime;
}

add_action( 'wp_ajax_post_promoter', 'post_promoter' );
function post_promoter() {
	$postID = $_POST['id'];
	if (current_user_can('edit_others_posts', $postID)) {
		$category_list = get_the_category($postID);
		$category_name = $category_list[0]->cat_name;
		// $authorID = get_post_field('post_author', $postID);
		if ($category_name === 'Prospects') {
			wp_remove_object_terms($postID, 'prospects', 'category');
			wp_add_object_terms( $postID, 'contenders', 'category' );
			absorb_votes($postID);
		} elseif ($category_name === 'Contenders') {
			wp_remove_object_terms($postID, 'contenders', 'category');
			wp_add_object_terms( $postID, 'noms', 'category' );
		}
	};
	echo json_encode($postID);
	wp_die();
}

add_action( 'wp_ajax_post_demoter', 'post_demoter' );
function post_demoter() {
	$postID = $_POST['id'];
	if (current_user_can('edit_others_posts', $postID)) {
		$category_list = get_the_category($postID);
		$category_name = $category_list[0]->cat_name;
		$authorID = get_post_field('post_author', $postID);
		if ($category_name === 'Nominees') {
			wp_remove_object_terms($postID, 'nominees', 'category');
			wp_add_object_terms( $postID, 'contenders', 'category' );
		} elseif ($category_name === 'Contenders') {
			// wp_remove_object_terms($postID, 'contenders', 'category');
			// wp_add_object_terms( $postID, 'prospects', 'category' );
			post_trasher($postID);
		} elseif ($category_name === 'Prospects') {
			post_trasher($postID);
		}
	};

	killAjaxFunction($postID);
}

add_action( 'wp_ajax_eliminate_post', 'eliminate_post' );
function eliminate_post() {
	if (!currentUserIsAdmin()) {
		wp_die("You are not an admin, sorry");
	}
	update_post_meta($_POST['id'], 'eliminated', "true");
	killAjaxFunction("Eliminated post " . $_POST['id']);
}

function post_trasher($postID) {
	if (current_user_can('delete_published_posts', $postID)) {
		wp_trash_post($postID);
	};
	reset_chat_votes();
	return ($postID);
}

function getLiveVoters() {
	$liveSlug = get_option("liveSlug");
	if ($liveSlug == "false") {$liveSlug = "live";}
	$liveVoters = getVoterDisplayInfoForSlug($liveSlug);
	return $liveVoters;
}

?>