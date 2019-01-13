<?php

function clipTypeDetector($clipURLRaw) {
	$clipURL = strtolower($clipURLRaw);
	$isTwitch = strpos($clipURL, 'twitch');
	$isYouTube = strpos($clipURL, 'youtube');
	$isYtbe = strpos($clipURL, 'youtu.be');
	$isTwitter = strpos($clipURL, 'twitter');
	$isGfy = strpos($clipURL, 'gfycat');
	$isGYG = strpos($clipURL, 'gifyourgame.com');

	if ($isTwitch !== false ) {
		return 'twitch';
	} elseif ($isYouTube !== false) {
		return 'youtube';
	} elseif ($isYtbe !== false) {
		return 'ytbe';
	} elseif ($isTwitter !== false) {
		return 'twitter';
	} elseif ($isGfy !== false) {
		return 'gfycat';
	} elseif ($isGYG !== false) {
		return 'gifyourgame';
	}
}

function turnURLIntoTwitchCode($url) {
	$unCasedUrl = strtolower($url);
	if (!strpos($unCasedUrl, 'twitch.tv/')) {
		return false;
	}

	if (strpos($unCasedUrl, 'clips.twitch.tv/')) {
		$twitchCodePosition = strpos($unCasedUrl, 'clips.twitch.tv/') + 16;
	} elseif (strpos($unCasedUrl, '/clip/')) {
		$twitchCodePosition = strpos($unCasedUrl, '/clip/') + 6;
	}
	if (strpos($unCasedUrl, '?')) {
		$twitchCodeEnd = strpos($unCasedUrl, '?');
		$twitchCodeLength = $twitchCodeEnd - $twitchCodePosition;
		$twitchCode = substr($url, $twitchCodePosition, $twitchCodeLength);
	} else {
		$twitchCode = substr($url, $twitchCodePosition);
	}
	// } else {
	// 	$twitchCodePosition = strpos($unCasedUrl, '/clip/') + 6;
	// 	if (strpos($unCasedUrl, '?')) {
	// 		$twitchCodeEnd = strpos($unCasedUrl, '?');
	// 		$twitchCodeLength = $twitchCodeEnd - $twitchCodePosition;
	// 		$twitchCode = substr($url, $twitchCodePosition, $twitchCodeLength);
	// 	} else {
	// 		$twitchCode = substr($url, $twitchCodePosition);
	// 	}
	// }

	return $twitchCode;
}

function turnURLIntoYoutubeCode($url) {
	$unCasedUrl = strtolower($url);
	if (!strpos($unCasedUrl, 'youtube.com/watch?v=')) {
		return false;
	}

	$youtubeCodePosition = strpos($unCasedUrl, 'youtube.com/watch?v=') + 20;
	if (strpos($unCasedUrl, '&')) {
		$youtubeCodeEndPosition = strpos($unCasedUrl, '&');
		$youtubeCodeLength = $youtubeCodeEndPosition - $youtubeCodePosition;
		$youtubeCode = substr($url, $youtubeCodePosition, $youtubeCodeLength);
	} else {
		$youtubeCode = substr($url, $youtubeCodePosition);
	}

	return $youtubeCode;
}

function turnURLIntoYtbeCode($url) {
	$unCasedUrl = strtolower($url);
	if (!strpos($url, 'youtu.be/')) {
		return false;
	}

	$youtubeCodePosition = strpos($unCasedUrl, 'youtu.be/') + 9;
	if (strpos($unCasedUrl, '?')) {
		$youtubeCodeEndPosition = strpos($unCasedUrl, '?');
		$youtubeCodeLength = $youtubeCodeEndPosition - $youtubeCodePosition;
		$youtubeCode = substr($url, $youtubeCodePosition, $youtubeCodeLength);
	} else {
		$youtubeCode = substr($url, $youtubeCodePosition);
	}

	return $youtubeCode;
}

function turnURLIntoTwitterCode($url) {
	$unCasedUrl = strtolower($url);
	if (!strpos($unCasedUrl, 'twitter.com/') || !strpos($unCasedUrl, '/status/')) {
		return false;
	}
	$twitterCodePosition = strpos($unCasedUrl, '/status/') + 8;
	if (strpos($unCasedUrl, '?')) {
		$twitterCodeEndPosition = strpos($unCasedUrl, '?');
		$twitterCodeLength = $twitterCodeEndPosition - $twitterCodePosition;
		$twitterCode = substr($url, $twitterCodePosition, $twitterCodeLength);
	} else {
		$twitterCode = substr($url, $twitterCodePosition);
	}

	return $twitterCode;
}

function turnURLIntoGfycode($url) {
	$unCasedUrl = strtolower($url);
	if (!strpos($unCasedUrl, 'gfycat.com/')) {
		return false;
	}

	if (strpos($unCasedUrl, '/detail/')) {
		$gfyCodePosition = strpos($unCasedUrl, '/detail/') + 8;
		if (strpos($unCasedUrl, '?')) {
			$gfyCodeEndPosition = strpos($unCasedUrl, '?');
			$gfyCodeLength = $gfyCodeEndPosition - $gfyCodePosition;
			$gfyCode = substr($url, $gfyCodePosition, $gfyCodeLength);
		} else {
			$gfyCode = substr($url, $gfyCodePosition);
		}
	} else {
		$gfyCodePosition = strpos($unCasedUrl, 'gfycat.com/') + 11;
		if (strpos($unCasedUrl, '?')) {
			$gfyCodeEndPosition = strpos($unCasedUrl, '?');
			$gfyCodeLength = $gfyCodeEndPosition - $gfyCodePosition;
			$gfyCode = substr($url, $gfyCodePosition, $gfyCodeLength);
		} elseif (strpos($unCasedUrl, '.mp4')) {
			$gfyCodeEndPosition = strpos($unCasedUrl, '.mp4');
			$gfyCodeLength = $gfyCodeEndPosition - $gfyCodePosition;
			$gfyCode = substr($url, $gfyCodePosition, $gfyCodeLength);
		} else {
			$gfyCode = substr($url, $gfyCodePosition);
		}
	}

	return $gfyCode;
}

function turnURLIntoGifYourGameCode($url) {
	$unCasedUrl = strtolower($url);
	if (!strpos($unCasedUrl, 'gifyourgame.com/')) {
		return false;
	}

	$gygCodePosition = strpos($unCasedUrl, 'gifyourgame.com/') + 16;
	$gygCode = substr($url, $gygCodePosition);

	return $gygCode;
}

function sourceFinder($channelURL) {
	$channelURL = strtoupper($channelURL);
	$sourceArgs = array(
		'taxonomy' => 'source'
	);
	$sources = get_terms($sourceArgs);
	$sourceID = 632; //632 is User Submits
	foreach ($sources as $source) {
		$key = strtoupper(get_term_meta($source->term_id, 'twitch', true));
		if (strpos($key, $channelURL)) {
			$sourceID = $source->term_id;
		}
	}
	return $sourceID;
}

function starChecker($thingTitle) {
	$titleWords = explode(" ", $thingTitle);
	$starNickname = strtolower($titleWords[0]);
	$starNickLength = strlen($starNickname);
	$star_args = array(
		'taxonomy' => 'stars',
	);
	$stars = get_terms($star_args);
	$postStar = 'X';
	$singleStar = true;
	foreach ($stars as $star) {
		$starSlug = $star->slug;
		$starShortSlug = substr($starSlug, 0, $starNickLength);
		if ($starShortSlug == $starNickname && $singleStar) {
			$postStar = $star->term_id;
			$singleStar = false;
		} elseif ($starShortSlug == $starNickname && !$singleStar) {
			$postStar = 'X';
		}
	};
	return $postStar;
}

function getSlugByPostID($postID) {
	$slugsArray = array(
		'twitch' => get_post_meta($postID, 'TwitchCode', true),
		'twitter' => get_post_meta($postID, 'TwitterCode', true),
		'gfy' => get_post_meta($postID, 'GFYtitle', true),
		'youtube' => get_post_meta($postID, 'YouTubeCode', true),
	);
	foreach ($slugsArray as $slugCheck) {
		$slugCheck != "" ? $slug = $slugCheck : $slug = $slug;
	}
	return $slug;
}
function getPostIDBySlug($slug) {
	$args = array(
		"meta_value" => $slug,
		"post_status" => array("publish", "future"),
	);
	$query = new WP_Query($args);
	return $query->posts[0]->ID;
}

function getClipTypeByPostID($postID) {
	$twitch = get_post_meta($postID, 'TwitchCode', true);
	if ($twitch) {return "twitch";}
	$twitter = get_post_meta($postID, 'TwitterCode', true);
	if ($twitter) {return "twitter";}
	$gfycat = get_post_meta($postID, 'GFYtitle', true);
	if ($gfycat) {return "gfycat";}
	$youtube = get_post_meta($postID, 'YouTubeCode', true);
	if ($youtube) {return "youtube";}
}

function convertTwitchTimeToTimestamp($twitchTime) {
	return date("U",strtotime($twitchTime));
}

?>