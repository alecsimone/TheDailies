<?php /* Template Name: Your Submissions */ 
get_header();

if (is_user_logged_in()) { ?>
	
	<table id="yourSubmissionsTable">
		<tr>
			<th>Submission</th>
			<th>Score</th>
		</tr>
	<?php 
	global $wpdb;
	$submissionsDB = $wpdb->prefix . "submissions_db";
	$votesDB = $wpdb->prefix . "vote_db";
	$pulledClipsDB = $wpdb->prefix . "pulled_clips_db";

	$yourName = get_user_meta(get_current_user_id(), 'nickname', true);

	$yourSubmissions = $wpdb->get_results("SELECT * FROM $submissionsDB WHERE submitter = '$yourName' ORDER BY id DESC", ARRAY_A);

	if (count($yourSubmissions) === 0) {
		?>
		<tr class="submissionRow">
			<td>You haven't submitted anything</td>
			<td>--</td>
		</tr>
		<?php
	}

	foreach ($yourSubmissions as $submission) {
		$slug = $submission['slug'];
		$title = stripslashes($submission['title']);
		if ($title === "") {$title = "No title provided";}
		$slugRow = $wpdb->get_row("SELECT * FROM $pulledClipsDB WHERE slug = '$slug'");
		$postID = getPostIDBySlug($slug);
		if ($slugRow === null && $postID === null) {
			$score = "Killed";
			$scoreClass = "rejected";
		} else {
			$voters = getVotersForSlug($slug);
			$score = 0;
			$scoreClass = "chosen";
			foreach ($voters as $voter) {
				$score = $score + (int)$voter['weight'];
			}
		}

		if ($submission['type'] === "twitch") {
			$link = "https://clips.twitch.tv/" . $slug;
		} elseif ($submission['type'] === "twitter") {
			$link = "https://twitter.com/statuses/" . $slug;
		} elseif ($submission['type'] === "gfycat") {
			$link = "https://gfycat.com/" . $slug;
		} elseif ($submission['type'] === "youtube" || $submission['type'] === "ytbe") {
			$link = "https://www.youtube.com/watch?v=" . $slug;
		} elseif ($submission['type'] === "gifyourgame") {
			$link = "https://gifyourgame.com/" . $slug;
		}

		?>
		<tr class="submissionRow">
			<td><a href="<?php echo $link; ?>" target="_blank"><?php echo $title; ?></a></td>
			<td><span class="<?php echo $scoreClass; ?>"><?php echo $score; ?></span></td>
		</tr>
	<?php } ?>
	</table>

<?php } else { ?>

<div id="wp-social-login"><?php do_action('wordpress_social_login'); ?></div>

<?php }
get_footer(); ?>