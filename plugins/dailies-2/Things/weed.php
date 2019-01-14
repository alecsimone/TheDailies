<?php

function getCurrentUsersSeenSlugs() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'vote_db';

	$hash = getPersonsHash(get_current_user_id());
	$cutoff = clipCutoffTimestamp() - 72 * 60 * 60;

	$seenSlugs = $wpdb->get_results(
		"
		SELECT *
		FROM $table_name
		WHERE hash = '$hash' AND time > '$cutoff'
		",
		ARRAY_A
	);

	return $seenSlugs;
}

add_action( 'wp_ajax_judge_slug', 'judge_slug' );
function judge_slug() {
	$slug = $_POST['slug'];
	$judgment = $_POST['judgment'];
	$userID = get_current_user_id();

	$clipArray = getSlugInPulledClipsDB($slug);

	if ($clipArray === null) {
		killAjaxFunction("Unknown Clip: " . $slug);
	}

	if ($judgment === 'down') {
		$clipArray['score'] = $clipArray['score'] - getValidRep($userID) * floatval(get_option("nayCoefficient"));
	} elseif ($judgment === 'up') {
		$clipArray['score'] = $clipArray['score'] + getValidRep($userID);
	}
	if (!is_numeric($clipArray['votecount'])) {
		$clipArray['votecount'] = 0;
	}
	$clipArray['votecount'] = (int)$clipArray['votecount'] + 1;
	editPulledClip($clipArray);

	checkForScoutRepIncrease($userID);

	killAjaxFunction($clipArray);
}

function checkForScoutRepIncrease($person) {
	$person = getPersonInDB($person);
    $lastScoutRepTime = ensureTimestampInSeconds($person['lastScoutRepTime']);
    $deservesNewRep = false;
    if ($lastScoutRepTime <= time() - 24 * 60 * 60) {
    	$oldRep = (int)getValidRep($person);
        if ($oldRep < 20) {
            increase_giveable_rep($person['hash'], 1);
            $newRep = increase_rep($person['hash'], 1);
        } else {
            $newRep = increase_giveable_rep($person['hash'], 2);
        }
        updateScoutRepTime($person['hash']);
        $deservesNewRep = true;
    }
    if ($deservesNewRep) {
        return $newRep;
    } else {
        return false;
    }
}

function updateScoutRepTime($person) {
	$personArray = array(
        'lastScoutRepTime' => time(),
    );
    if (is_string($person)) {
        $personArray[checkIfStringIsHashOrTwitchName($person)] = $person;
    } elseif (is_int($person)) {
        $personArray['dailiesID'] = $person;
    }
    editPersonInDB($personArray);
}

?>