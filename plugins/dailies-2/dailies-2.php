<?php
/*
Plugin Name: The Dailies
Plugin URI:  https://dailies.gg/
Description: Handles all the additional functionality needed to run The Dailies
Version:     2.0
Author:      Alec Simone
License:     Do whatever the hell you want with it, it's mostly pretty shit code
*/

add_theme_support( 'post-thumbnails' );
add_image_size('small', 350, 800);
add_theme_support( 'title-tag' );
show_admin_bar(false);
add_filter('show_admin_bar', '__return_false');

add_filter( 'postmeta_form_limit', 'meta_limit_increase' );
function meta_limit_increase( $limit ) {
    return 50;
}

require_once( __DIR__ . '/helpers.php');
require_once( __DIR__ . '/privateData.php');
require_once( __DIR__ . '/client-information.php');

//People
require_once( __DIR__ . '/People/people-databases-setup.php');
require_once( __DIR__ . '/People/people.php');

//Things
require_once( __DIR__ . '/Things/thing-databases-setup.php');
require_once( __DIR__ . '/Things/thing-management.php');
require_once( __DIR__ . '/Things/comments.php');
require_once( __DIR__ . '/Things/submissions.php');
require_once( __DIR__ . '/Things/add-clips.php');
require_once( __DIR__ . '/Things/pull-clips.php');
require_once( __DIR__ . '/Things/weed.php');
require_once( __DIR__ . '/Things/hopefuls.php');
require_once( __DIR__ . '/Things/live.php');
require_once( __DIR__ . '/Things/postDataObj.php');

//Voting
require_once( __DIR__ . '/Voting/vote-databases-setup.php');
require_once( __DIR__ . '/Voting/populate-vote-db.php');
require_once( __DIR__ . '/Voting/voting.php');


require_once( __DIR__ . '/page-templates/add-page-templates.php');

//Rules

require_once( __DIR__ . '/rest-endpoints.php');
require_once( __DIR__ . '/crons.php');
require_once( __DIR__ . '/nightbot.php');

?>