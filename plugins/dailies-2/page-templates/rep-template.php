<?php /* Template Name: Rep */ 
get_header(); ?>

<div id="repIntro">
	<div class="repIntroRow"><span class="bold">Rep</span> determines how much your vote counts for</div>
	<div class="repIntroRow">When you participate in the Dailies process, you get the ability to <span class="highlight">give other people rep</span></div>
	<div class="repIntroRow">You only get rep when someone else gives it to you</div>
</div>

<section id="repInfo">

	<div id="totalRep" class="repPageSection">

		<?php 
		global $wpdb;
		$table_name = $wpdb->prefix . "people_db";

		$everyonesRep = $wpdb->get_col("SELECT rep FROM $table_name");
		$totalRep = 0;
		$totalPeople = count($everyonesRep);
		foreach ($everyonesRep as $rep) {
			$totalRep = $totalRep + $rep;
		}
		$averageRep = round($totalRep / $totalPeople, 1, PHP_ROUND_HALF_DOWN);
		$formattedTotalRep = number_format($totalRep);
		$formattedTotalPeople = number_format($totalPeople);
		?>
		<div class='mainLine'>There is <span class='bold'><?php echo $formattedTotalRep?></span> total rep</div>
		
		There are <span class='highlight'><?php echo $formattedTotalPeople?></span> people, so people have an average of <span class='highlight'><?php echo $averageRep?></span> rep

	</div>

	<div id="multiRepPeople" class="repPageSection">

		<?php
		$multirepPeoplesRep = $wpdb->get_col("SELECT rep FROM $table_name WHERE rep > 1");
		$totalRep = 0;
		$totalPeople = count($multirepPeoplesRep);
		foreach ($multirepPeoplesRep as $rep) {
			$totalRep = $totalRep + $rep;
		}
		$averageRep = round($totalRep / $totalPeople, 1, PHP_ROUND_HALF_DOWN);
		$formattedTotalRep = number_format($totalRep);
		basicPrint("If we exclude people who only have 1 rep, There is <span class='bold'>$formattedTotalRep</span> rep");
		basicPrint("There are <span class='highlight'>$totalPeople</span> people with more than 1 rep, so those people have an average of <span class='highlight'>$averageRep</span> rep");
		?>

	</div>
	<div id="giveableRep" class="repPageSection">
	
		<?php 
		$everyonesGiveableRep = $wpdb->get_col("SELECT giveableRep FROM $table_name");
		$totalGiveableRep = 0;
		$totalPeople = count($everyonesGiveableRep);
		foreach ($everyonesGiveableRep as $Giveablerep) {
			$totalGiveableRep = $totalGiveableRep + $Giveablerep;
		}
		$averageGiveableRep = round($totalGiveableRep / $totalPeople, 1, PHP_ROUND_HALF_DOWN);

		$formattedTotalGiveableRep = number_format($totalGiveableRep);
		basicPrint("There is <span class='bold'>$formattedTotalGiveableRep</span> total giveable rep, so people have an average of <span class='highlight'>$averageGiveableRep</span> giveable rep");

		$multirepPeoplesGiveableRep = $wpdb->get_col("SELECT giveableRep FROM $table_name WHERE rep > 1");
		$totalGiveableRep = 0;
		$totalPeople = count($multirepPeoplesGiveableRep);
		foreach ($multirepPeoplesGiveableRep as $Giveablerep) {
			$totalGiveableRep = $totalGiveableRep + $Giveablerep;
		}
		$averageGiveableRep = round($totalGiveableRep / $totalPeople, 1, PHP_ROUND_HALF_DOWN);

		$formattedTotalGiveableRep = number_format($totalGiveableRep);
		basicPrint("Among people with more than 1 rep, there is <span class='bold'>$formattedTotalGiveableRep</span> giveable rep, so those people have an average of <span class='highlight'>$averageGiveableRep</span> giveable rep");
		?>

	</div>
	<div id="oneHundredClub" class="repPageSection">

		<?php
		$oneHundredClub = $wpdb->get_results("
			SELECT picture, dailiesDisplayName, twitchName
			FROM $table_name
			WHERE rep = 100
		", ARRAY_A);

		?><div class='repTableHeader'>The people who currently have 100 Rep are:</div><?php

		$var = 5;
		$var_is_greater_than_two = ($var > 2 ? true : false); 

		foreach ($oneHundredClub as $hundreder) {
			$hundrederDisplayName = ($hundreder['dailiesDisplayName'] !== "--" ? $hundreder['dailiesDisplayName'] : $hundreder['twitchName']);
			$pic = $hundreder['picture'];
			?>
			<div class='hundreder row'><img class='hudredRepPic' src='<?php echo $pic?>' /><?php echo $hundrederDisplayName?></div>
		<?php }
		?>

	</div>
	<div id="highRepPeople" class="repPageSection">

		<?php
		$nextTwentyFive = $wpdb->get_results("
			SELECT picture, dailiesDisplayName, twitchName, rep
			FROM $table_name
			WHERE rep < 100
			ORDER BY rep DESC
			LIMIT 25
		", ARRAY_A);

		?><div class='repTableHeader'>The next 25 reppiest people are:</div><?php

		foreach ($nextTwentyFive as $highRepPerson) {
			$highRepPersonDisplayName = ($highRepPerson['dailiesDisplayName'] !== "--" ? $highRepPerson['dailiesDisplayName'] : $highRepPerson['twitchName']);
			$highRep = $highRepPerson['rep'];
			$pic = $highRepPerson['picture'];
			?>
			<div class='highRepPerson row'><img class='highRepPersonPic' src='<?php echo $pic?>' /> <?php echo $highRepPersonDisplayName?> - <?php echo $highRep?></div>
		<?php }
		?>
	</div>
	
</section>

<?php get_footer(); ?>