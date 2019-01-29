<?php

function getPeopleDB() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'people_db';
    $peopleDB = $wpdb->get_results(
        "SELECT *
        FROM $table_name",
        ARRAY_A 
    );
    return $peopleDB;
}

function getPersonInDB($person) {
    global $wpdb;
    if (is_array($person)) {
        if (isset($person['hash'])) {
            $column = "hash";
            $value = $person['hash'];
        } elseif (isset($person['dailiesID'])) {
            $column = "dailiesID";
            $value = $person['dailiesID'];
        } elseif (isset($person['twitchName'])) {
            $column = "twitchName";
            $value = $person['twitchName'];
        }
    } elseif (is_string($person)) {
        $column = checkIfStringIsHashOrTwitchName($person);
        $value = $person;
    } elseif (is_numeric($person)) {
        $column = "dailiesID";
        $value = $person;
    }
    $personData = $wpdb->get_row(
        "SELECT * FROM wpik_people_db WHERE $column = '$value'",
        ARRAY_A
    );
    if ($personData === null) {$personData = false;}
    return $personData;
}

function addPersonToDB($personArray) {
    global $wpdb;
    $table_name = $wpdb->prefix . "people_db";

    if (getPersonInDB($personArray)) {
        return "That person already exists in the database";
    }

    if (!isset($personArray['hash'])) {
        $personArray['hash'] = generateHash();
    }
    if (!isset($personArray['lastRepTime'])) {
        $personArray['lastRepTime'] = time();
    }

    $wpdb->insert(
        $table_name,
        $personArray
    );
    return $wpdb->insert_id;
}

function editPersonInDB($personArray) {
    $personData = getPersonInDB($personArray);
    if (!$personData) {
        addPersonToDB($personArray);
        return;
    }

    // $personArrayKeys = array_keys($personArray);
    // foreach ($personArrayKeys as $key) {
    //     if ($key !== 'hash') {
    //         $personData[$key] = $personArray[$key];
    //     }
    // }

    $where = array();
    if (isset($personArray['hash'])) {
        $where['hash'] = $personArray['hash'];
    } elseif (isset($personArray['dailiesID'])) {
        $where['dailiesID'] = $personArray['dailiesID'];
    } elseif (isset($personArray['twitchName'])) {
        $where['twitchName'] = $personArray['twitchName'];
    }

    global $wpdb;
    $table_name = $wpdb->prefix . "people_db";
    $wpdb->update(
        $table_name,
        $personArray,
        $where
    );
}

function deletePersonFromDB($person) {
    $personArray = array();
    if (is_array($person)) {
        if (isset($person['hash'])) {
            $personArray['hash'] = $person['hash'];
        }
        if (isset($person['twitchName'])) {
            $personArray['twitchName'] = $person['twitchName'];
        }
        if (isset($person['dailiesID'])) {
            $personArray['dailiesID'] = $person['dailiesID'];
        }
    } elseif (is_string($person)) {
        $personArray[checkIfStringIsHashOrTwitchName($person)] = $person;
    } elseif (is_numeric($person)) {
        $personArray['dailiesID'] = $person;
    }
    if (is_string($personArray['dailiesID'])) {
        $personArray['dailiesID'] = intval($personArray['dailiesID']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . "people_db";
    $deleteCount = $wpdb->delete(
        $table_name,
        $personArray
    );
    return $deleteCount;
}

function buildFreshTwitchDB() {
    global $wpdb;
    $table_name = $wpdb->prefix . "people_db";
    $twitchAccounts = $wpdb->get_results(
        "
        SELECT twitchName, rep, picture
        FROM $table_name
        WHERE twitchName != '--'
        ",
        'OBJECT_K'
    );
    return $twitchAccounts;
}
add_action( 'wp_ajax_share_twitch_user_db', 'share_twitch_user_db' );
function share_twitch_user_db() {
    // $twitchUserDB = buildFreshTwitchUserDB();
    $twitchDB = buildFreshTwitchDB();
    killAjaxFunction($twitchDB);
}

add_action( 'wp_ajax_update_twitch_db', 'update_twitch_db' );
function update_twitch_db() {
    if (!currentUserIsAdmin()) {
        wp_die("You are not an admin, sorry");
    }
    $twitchName = $_POST['twitchName'];
    $twitchPic = $_POST['twitchPic'];
    $userArray = array(
        'twitchName' => $twitchName,
        'picture' => $twitchPic,
    );
    editPersonInDB($userArray);
    killAjaxFunction($userArray);
}

function getSpecialPeople() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'people_db';

    $specialPeople = $wpdb->get_results(
        "SELECT *
        FROM $table_name
        WHERE special = 1
        ",
        ARRAY_A
    );
    return $specialPeople;
}

function togglePersonSpecialness($person) {
    $personArray = getPersonInDB($person);
    if ($personArray['special'] == 0) {
        $personArray['special'] = 1;
    } else {
        $personArray['special'] = 0;
    }
    editPersonInDB($personArray);
}

function getValidRep($person) {
	$userData = getPersonInDB($person);
    if ($userData === false) {
        return false;
    }
	$rep = $userData['rep'];
	if ($rep == '' || $rep == 0) {
		$rep = 1;
	}
	return (int)$rep;
}
function getGiveableRep($person) {
    $personData = getPersonInDB($person);
    if (!$personData) {
        return 0;
    }
    $giveableRep = (int)$personData['giveableRep'];
    return $giveableRep;
}

function increase_rep($person, $additionalRep) {
    $oldRep = getValidRep($person);
    if ($oldRep == 100) {return false;}
    $newRep = $oldRep + $additionalRep;
    if ($newRep > 100) {$newRep = 100;}
    if (is_numeric($person)) {
        $personArray = array(
            'dailiesID' => intval($person),
            'rep' => $newRep,
        );
    } elseif (is_string($person)) {
        $typeOfString = checkIfStringIsHashOrTwitchName($person);
        $personArray = array(
            'rep' => $newRep,
        );
        $personArray[$typeOfString] = $person;
    }
    editPersonInDB($personArray);
    return $newRep;
}

function increase_giveable_rep($person, $additionalRep) {
    $oldRep = getGiveableRep($person);
    $newRep = $oldRep + $additionalRep;
    if ($newRep > 200) {$newRep = 200;}
    if ($newRep < 0) {return false;}
    if (is_numeric($person)) {
        $personArray = array(
            'dailiesID' => intval($person),
            'giveableRep' => $newRep,
        );
    } elseif (is_string($person)) {
        $typeOfString = checkIfStringIsHashOrTwitchName($person);
        $personArray = array(
            'giveableRep' => $newRep,
        );
        $personArray[$typeOfString] = $person;
    }
    editPersonInDB($personArray);
    return $newRep;
}

function updateRepTime($person) {
    $personArray = array(
        'lastRepTime' => time(),
    );
    if (is_string($person)) {
        $personArray[checkIfStringIsHashOrTwitchName($person)] = $person;
    } elseif (is_int($person)) {
        $personArray['dailiesID'] = $person;
    }
    editPersonInDB($personArray);
}

function getPersonsHash($person) {
    $personRow = getPersonInDB($person);
    return $personRow['hash'];
}

function getPicForPerson($person) {
	$userRow = getPersonInDB($person);
	$userPic = $userRow['picture'];
    if ($userPic === '') {
        $userPic = "https://dailies.gg/wp-content/uploads/2017/03/default_pic.jpg";
    }
	return $userPic;
}

function checkForRepIncrease($person) {
    $person = getPersonInDB($person);
    $lastNomTime = ensureTimestampInSeconds(getLastNomTimestamp());
    $lastRepTime = ensureTimestampInSeconds($person['lastRepTime']);
    $deservesNewRep = false;
    if ($lastRepTime <= $lastNomTime) {
        $oldRep = (int)getValidRep($person);
        if ($oldRep < 20) {
            increase_giveable_rep($person['hash'], 1);
            $newRep = increase_rep($person['hash'], 1);
        } else {
            $newRep = increase_giveable_rep($person['hash'], 2);
        }
        updateRepTime($person['hash']);
        $deservesNewRep = true;
        return $newRep;
    } else {
        return false;
    }
}

add_action( 'wp_ajax_give_rep', 'give_rep' );
function give_rep() {
    if (!currentUserIsAdmin()) {
        $response = array(
            'message' => "You're not an admin!", 
            "tone" => "error",
        );
        send_nightbot_message($response['message']);
        killAjaxFunction($response);
    }

    $giver = sanitize_text_field($_POST['speaker']);
    $giverData = getPersonInDB($giver);
    if (!$giverData) {
        $response = array(
            'message' => $giver . ", you're not in our database!", 
            "tone" => "error",
        );
        send_nightbot_message($response['message']);
        killAjaxFunction($response);
    }


    $receiver = sanitize_text_field($_POST['target']);
    $receiverData = getPersonInDB($receiver);
    if (!$receiverData) {
        $response = array(
            'message' => $receiver . " isn't in our database!", 
            "tone" => "error",
        );
        send_nightbot_message($response['message']);
        killAjaxFunction($response);
    }

    $amount = sanitize_text_field($_POST['amount']);
    if (!is_numeric($amount)) {
        $response = array(
            'message' => "You tried to give a non-numeric amount of rep!", 
            "tone" => "error",
        );
        send_nightbot_message($response['message']);
        killAjaxFunction($response);
    } else {
        $amount = (int)$amount;
    }

    if ($amount <= 0) {
        $response = array(
            'message' => "You have to give a positive amount of rep, asshole!", 
            "tone" => "error",
        );
        send_nightbot_message($response['message']);
        killAjaxFunction($response);
    } 

    $receiverReceivableRep = 100 - (int)$receiverData['rep'];
    if ($amount > $receiverReceivableRep) {
        $amount = $receiverReceivableRep;
    }
    if ($receiverReceivableRep == 0) {
        $response = array(
            'message' => $receiver . " already has 100 rep", 
            "tone" => "error",
        );
        send_nightbot_message($response['message']);
        killAjaxFunction($response);
    }
    
    $giverGiveableRep = (int)$giverData['giveableRep'];
    if ($giverGiveableRep < $amount) {
        $response = array(
            'message' => $giver . ", you don't have that much giveable rep.", 
            "tone" => "error",
        );
        send_nightbot_message($response['message']);
        killAjaxFunction($response);
    }

    $newGiverGiveableRep = increase_giveable_rep($giverData['hash'], $amount * -1);
    if ($newGiverGiveableRep === false) {
        $response = array(
            'message' => $giver . ", you don't have that much giveable rep.", 
            "tone" => "error",
        );
        send_nightbot_message($response['message']);
        killAjaxFunction($response);
    }
    $newReceiverRep = increase_rep($receiverData['hash'], $amount);
    if (!$newReceiverRep) {
        $response = array(
            'message' => $receiver . " already has 100 rep", 
            "tone" => "error",
        );
        send_nightbot_message($response['message']);
        killAjaxFunction($response);
    }

    global $wpdb;
    $table = $wpdb->prefix . "rep_transfers_db";
    $data = array(
        "giver" => $giverData['hash'],
        "receiver" => $receiverData['hash'],
        "rep" => $amount,
        "time" => time(),
    );
    $wpdb->insert($table, $data);

    $response = array(
            'message' => $giver . " gave " . $receiver . " " . $amount . " rep!", 
            "tone" => "success",
        );
    send_nightbot_message($response['message']);
    killAjaxFunction($response);
}

function validateUserInfo($user_login, $user) {
    //When I figure out what needs to be fixed when people login, do it here
    $dailiesID = $user->ID;

    $userMeta = get_user_meta($dailiesID);
    $userData = get_userdata($dailiesID);
    $userURL = $userData->data->user_url;
    if (strpos($userURL, "twitch.tv/")) {
        $twitchName = substr($userURL, strpos($userURL, "twitch.tv/") + 10);
        $existingPersonByTwitchName = getPersonInDB($twitchName);
    } else {
        $twitchName = "unknown";
        $existingPersonByTwitchName = false;
    }

    $personArray = array(
        'dailiesID' => $dailiesID,
        'dailiesDisplayName' => $user->display_name,
        'twitchName' => $twitchName === "unknown" ? "--" : $twitchName,
        'email' => $user->user_email,
        'provider' => $userMeta['wsl_current_provider'][0],
        'role' => $user->roles[0],
    );

    $existingPersonByDailiesID = getPersonInDB($dailiesID);
    if (!$existingPersonByDailiesID && !$existingPersonByTwitchName) {
        addPersonToDB($personArray);
    } else if ($existingPersonByTwitchName !== false && $existingPersonByDailiesID !== false) {
        if ($existingPersonByTwitchName['hash'] !== $existingPersonByDailiesID['hash']) {
            $existingPersonByDailiesID['rep'] = (int)$existingPersonByTwitchName['rep'] + (int)$existingPersonByDailiesID['rep'] - 1;
            $existingPersonByDailiesID['twitchName'] = $existingPersonByTwitchName['twitchName'];

            deletePersonFromDB($existingPersonByTwitchName['hash']);
            editPersonInDB($existingPersonByDailiesID);
        }
    } else if ($existingPersonByTwitchName !== false) {
        $personArray['hash'] = $existingPersonByTwitchName['hash'];
        editPersonInDB($personArray);
    } else if ($existingPersonByDailiesID !== false) {
        $personArray['hash'] = $existingPersonByDailiesID['hash'];
        editPersonInDB($personArray);
    }

}
add_action('wp_login', 'validateUserInfo', 10, 2);

?>