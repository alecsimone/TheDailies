There are four categories of stuff that makes up The Dailies:
1. People: All the data on all the users, it comes from both twitch chat and the website. Every day a few cron functions run to make sure all the data is nice and tidy

2. Things: All the data on all the plays. Historically these were identified by postIDs created by wordpress, but when we transitioned from the Secret Garden (which did not really store clip data locally, just judgements) to Scout (which stores all clip data and slug data), we started identifying things by their slug, so that there can be a consistent identifier from the first time the clip enters the database until it retires to a quiet peaceful life in the archives. 

Things go through 5 stages: Scout, Hopefuls, Contenders, Nominees, and Archives


3. Rules: This has not been implemented yet, but I plan to add an official rulebook, to which people can propose ammendments. These ammendments can then be voted on and adopted during special streams devoted to that purpose.

4. Votes: Historically votes were attached to postDataObjects and could be in a voteledger, twitchvoters, or guestlist array. With the Scout Standardization, votes got their own database which keeps track of the slug the vote was cast on, the hash of the voter, and the weight of the vote.  


A quick map:

#People#
-Create Database
-Getters
---getPeopleDB()
---getPersonInDB($person)
---getValidRep($person)
---getPersonsHash($person)
---getPicForPerson($person)
---buildFreshTwitchDB()
---getSpecialPeople()
-Setters
---addPersonToDB($personArray)
---editPersonInDB($personArray)
---deletePersonFromDB($person)
---increase_rep($person, $additionalRep)
---updateRepTime($person)
---updateRole($dailiesID, $role, $old_roles)   CURRENTLY IN PEOPLE-AND-THINGS/PEOPLE-MANAGEMENT
---togglePersonSpecialness($person)
-Database Cleanup
---fixBrokenPeople()   CURRENTLY IN PEOPLE-AND-THINGS/PEOPLE-MANAGEMENT
---mergeTwitchAccounts()   CURRENTLY IN PEOPLE-AND-THINGS/PEOPLE-MANAGEMENT
---recognizeTwitchChatters()   CURRENTLY IN PEOPLE-AND-THINGS/PEOPLE-MANAGEMENT

#Things#
-Database Setup

-Submissions
---submitClipAjaxHandler()
---submitClip($newSeedlingTitle, $newSeedlingUrl, $submitter)
---gussyClip($clipType, $slug)
----getTweet($tweetID)
----gussyTweet($tweetID)
----gussyTwitch($twitchCode)
----gussyYoutube($youtubeCode)
----gussyGfy($gfyCode)
---addProspect()
----gussyProspect()

-Pull Clips
---getQueryPeriod()
---pull_all_clips()
---pull_twitch_clips()
----generateTodaysStreamlist()
----generateStreamlistForDay($day)
----get_twitch_clips($target, $queryPeriod)
---pull_twitter_mentions()
----tweetIsProbablySubmission($tweetData)
----submitTweet($tweetData)   CURRENTLY IN Things/submissions.php
----generateTwitterAuthorization($url, $method)
----createTwitterOauthSignature($url, $OAuth, $method)
---addSlugToDB($slugData)
---store_pulled_clips()
---getSlugInPulledClipsDB($slug)
---deleteSlugFromPulledClipsDB($slug)   CURRENTLY IN dailies-weeding/slug-management.php
---nukeSlug($slug)   CURRENTLY IN dailies-weeding/slug-management.php
---nukeAllDupeSlugs($slug)   CURRENTLY IN dailies-weeding/slug-management.php
---nuke_slug_handler()   CURRENTLY IN dailies-weeding/slug-management.php
---get_dupe_clips($string)   CURRENTLY IN dailies-weeding/slug-management.php
----convertVodlinkToMomentObject($vodlink)   CURRENTLY IN dailies-weeding/slug-management.php
---editPulledClip($clipArray)   CURRENTLY IN dailies-weeding/slug-management.php

-Weed (aka Scout, 1R)
---getPulledClipsDB()   CURRENTLY IN THING-MANAGEMENT.PHP
---getCleanPulledClipsDB()   CURRENTLY IN THING-MANAGEMENT.PHP
---clipCutoffTimestamp()   CURRENTLY IN THING-MANAGEMENT.PHP
---getCurrentUsersSeenSlugs()


-Hopefuls
---getHopefuls()
---keepSlug()
---hopefuls_cutter()

-Live
---post_promoter()   CURRENTLY IN themes/Dailies2/Functions/liveOperations.php
---post_demoter()
---post_trasher($postID)
---reset_live()   CURRENTLY IN themes/Dailies2/Functions/liveOperations.php

-Homepage

-Archives

-Comments
---getCommentsForSlug($slug)
---getCommentByID($id)   CURRENTLY IN dailies-weeding/comment-management.php
---postCommentHandler()   CURRENTLY IN dailies-weeding/comment-management.php
---addCommentToDB($commentData)   CURRENTLY IN dailies-weeding/comment-management.php
---yea_comment()   CURRENTLY IN dailies-weeding/comment-management.php
---del_comment()   CURRENTLY IN dailies-weeding/comment-management.php

#Rules#
--Coming Soon

#Votes#