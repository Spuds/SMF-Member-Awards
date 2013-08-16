<?php

/**
 * @name      Awards Modification
 * @license   Mozilla Public License version 1.1 http://mozilla.org/MPL/1.1/
 *
 * @version   3.0
 *
 * This file handles the loading side of Awards.
 *
 * Original Software by:           Juan "JayBachatero" Hernandez
 * Copyright (c) 2006-2009:        YodaOfDarkness (Fustrate)
 * Copyright (c) 2010:             Jason "JBlaze" Clemons
 *
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Loads all the awards for the members in the list
 *
 * @param array $new_loaded_ids
 */
function AwardsLoad($new_loaded_ids)
{
	global $user_profile, $modSettings, $smcFunc;

	$group_awards = array();
	$group_awards_details = array();

	// Build our database request to load all existing member awards for this group of members, including group awards
	$request = $smcFunc['db_query']('', '
		SELECT
			am.id_member, am.active, am.id_group,
			aw.id_award, aw.award_name, aw.description, aw.minifile, aw.award_trigger, aw.award_type, aw.award_location
		FROM {db_prefix}awards_members AS am
			INNER JOIN {db_prefix}awards AS aw ON (aw.id_award = am.id_award)
		WHERE (am.id_member IN({array_int:members}) OR am.id_member < 0)
			AND am.active = {int:active}
		ORDER BY am.favorite DESC, am.date_received DESC',
		array(
			'members' => $new_loaded_ids,
			'active'=> 1
		)
	);

	// Fetch the award info just once
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		// Track group awards separately
		if ($row['id_member'] < 0)
		{
			$group_awards[] = $row['id_group'];
			$group_awards_details[$row['id_group']] = array(
				'id' => $row['id_award'],
				'award_name' => $row['award_name'],
				'description' => $row['description'],
				'more' => '?action=profile;area=membersAwards;a_id=' . $row['id_award'],
				'href' => '?action=profile;area=showAwards;u=' . $row['id_member'],
				'minifile' => $row['minifile'],
				'img' => '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
				'trigger' => $row['award_trigger'],
				'award_type' => $row['award_type'],
				'location' => $row['award_location'],
				'active' => $row['active']
			);
		}
		else
		{
			$user_profile[$row['id_member']]['awards'][$row['id_award']] = array(
				'id' => $row['id_award'],
				'id_group' => $row['id_group'],
				'award_name' => $row['award_name'],
				'description' => $row['description'],
				'more' => '?action=profile;area=membersAwards;a_id=' . $row['id_award'],
				'href' => '?action=profile;area=showAwards;u=' . $row['id_member'],
				'minifile' => $row['minifile'],
				'img' => '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
				'trigger' => $row['award_trigger'],
				'award_type' => $row['award_type'],
				'location' => $row['award_location'],
				'active' => $row['active']
			);

			// Keep an array of just active awards for this member to make life easier
			if (!empty($row['active']))
				$user_profile[$row['id_member']]['awardlist'][] = $row['id_award'];
		}
	}
	$smcFunc['db_free_result']($request);

	// Are any group awards?
	if (!empty($group_awards))
	{
		// check each member to see if they are a member of a group that has a group awards
		foreach ($new_loaded_ids as $member_id)
		{
			// make an array of this users groups
			$user_profile[$member_id]['groups'] = array($user_profile[$member_id]['id_group'], $user_profile[$member_id]['id_post_group']);
			if (!empty($user_profile[$member_id]['additional_groups']))
				$user_profile[$member_id]['groups'] = array_merge($user_profile[$member_id]['groups'],explode(',', $user_profile[$member_id]['additional_groups']));

			// See if any of this members groups match a group award
			$give_group_awards = array_intersect($user_profile[$member_id]['groups'],$group_awards);
			if (!empty($give_group_awards))
			{
				// Woohoo ... a group award for you *IF* it was not assigned individually, you only get it once ;)
				foreach ($give_group_awards as $groupaward_id)
				{
					if (!isset($user_profile[$member_id]['awards'][$group_awards_details[$groupaward_id]['id']]))
						$user_profile[$member_id]['awards'][$groupaward_id] = $group_awards_details[$groupaward_id];
				}
			}
		}
	}

	return;
}

/**
 * Master auto award function, runs the show
 * Loads all of the defined auto awards and groups them
 * Uses the cache when it can
 * Determine if any members in the list have earned an of the auto awards
 *
 * @param type $new_loaded_ids
 */
function AwardsAutoCheck($new_loaded_ids)
{
	global $smcFunc;

	// See if we already have this in the cache
	$autoawards = cache_get_data('awards:autoawards', 4 * 3600);
	$autoawardsid = cache_get_data('awards:autoawardsid', 4 * 3600);
	if ($autoawards === null || $autoawardsid === null)
	{
		// init
		$autoawards = array();
		$autoawardsid = array();

		// Load all the defined auto awards .. uses a filesort,
		// but how many auto award definitions are there, <100? php sort instead?
		// The key is the trigger desc sort, this allows us to use 1 query for that auto award 'type',
		// all others will be a subset of that
		$request = $smcFunc['db_query']('', '
			SELECT
				id_award, award_name, award_trigger, award_type
			FROM {db_prefix}awards
			WHERE award_type > {int:type}
			ORDER BY award_type DESC, award_trigger DESC',
			array(
				'type' => 1,
			)
		);
		// build up the auto awards array
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$autoawards[$row['award_type']][] = $row; // holds all the awards information for each award type
			$autoawardsid[$row['award_type']][] = (int) $row['id_award']; // holds all the possible award id's for a given award type.
		}
		$smcFunc['db_free_result']($request);

		// save it for 4 hours, really could be longer since it only changes when a new auto award is added / edited.
		if (!empty($modSettings['cache_enable']))
		{
			cache_put_data('awards:autoawards', $autoawards, 4 * 3600);
			cache_put_data('awards:autoawardsid', $autoawardsid, 4 * 3600);
		}
	}

	// Now lets do something with each award type
	foreach($autoawards as $award_type => $awardids)
	{
		switch ($award_type)
		{
			case 2:
				// Post count based awards
				$members = AwardsAutoAssignMembers($awardids, $new_loaded_ids, 'posts');

				// If we found new awards to assign, do so
				if (!empty($members))
					AwardsAutoAssign($members, $award_type, $autoawardsid[$award_type]);
				break;
			case 3:
				// Top posters 1-N
				AwardsTopPosters_1_N($awardids[0]['award_trigger']);
				$members = AwardsAutoAssignMembers($awardids, $new_loaded_ids, 'top_posters', true);

				// If we found new awards to assign, do so
				if (!empty($members))
					AwardsAutoAssign($members, $award_type, $autoawardsid[$award_type]);
				break;
			case 4:
				// Topic count based awards
				AwardsTopicsStarted($new_loaded_ids);
				$members = AwardsAutoAssignMembers($awardids, $new_loaded_ids, 'num_topics');

				// If we found new awards to assign, do so
				if (!empty($members))
					AwardsAutoAssign($members, $award_type, $autoawardsid[$award_type]);
				break;
			case 5:
				// Top topic starters 1-N
				AwardsTopTopicStarter_1_N($awardids[0]['award_trigger']);
				$members = AwardsAutoAssignMembers($awardids, $new_loaded_ids, 'top_topics', true);

				// If we found new awards to assign, do so
				if (!empty($members))
					AwardsAutoAssign($members, $award_type, $autoawardsid[$award_type]);
				break;
			case 6:
				// Most time wasted on the site 1-N,
				AwardsTopTimeon_1_N($awardids[0]['award_trigger']);
				$members = AwardsAutoAssignMembers($awardids, $new_loaded_ids, 'top_time', true);

				// If we found new awards to assign, do so
				if (!empty($members))
					AwardsAutoAssign($members, $award_type, $autoawardsid[$award_type]);
				break;
			case 7:
				// Member join date seniority
				AwardsSeniority($new_loaded_ids);
				$members = AwardsAutoAssignMembers($awardids, $new_loaded_ids, 'join_length');

				// If we found new awards to assign, do so
				if (!empty($members))
					AwardsAutoAssign($members, $award_type, $autoawardsid[$award_type]);
				break;
			case 8:
				// People like me dammit!
				AwardsPopularity($new_loaded_ids);
				$members = AwardsAutoAssignMembers($awardids, $new_loaded_ids, 'popularity');

				// If we found new awards to assign, do so
				if (!empty($members))
					AwardsAutoAssign($members, $award_type, $autoawardsid[$award_type]);
				break;
		}
	}
}

/**
 * Given the award limits, the members to check and the area, does the comparison
 * uses the data set in $user_profile by the various award querys (topic, post, timeon, etc)
 * Returns the member ids, from the supplied list, of any who have reached a threshold
 *
 * @param type $awardids
 * @param type $new_loaded_ids
 * @param type $area
 * @param type $one_to_n
 */
function AwardsAutoAssignMembers($awardids, $new_loaded_ids, $area, $one_to_n = false)
{
	global $user_profile;
	$members = array();

	// 1-n awards need to be ascending order, others use the default descending order
	if ($one_to_n)
		$awardids = array_reverse($awardids);

	// For all the members in this request
	foreach($new_loaded_ids as $member_id)
	{
		// see if they have enough of '$areas' to hit one of the trigger levels
		foreach($awardids as $award)
		{
			// normal value based awards
			if (!$one_to_n)
			{
				if (isset($user_profile[$member_id][$area]) && ($user_profile[$member_id][$area] >= $award['award_trigger']))
				{
					// Give this member a cupcake, if they don't already have it, and stop looking for more
					if (!in_array($award['id_award'],$user_profile[$member_id]['awardlist']))
						$members[$member_id] = (int) $award['id_award'];
					break;
				}
			}
			// 1 to n position based awards
			else
			{
				if (isset($user_profile[$member_id][$area]) && ($user_profile[$member_id][$area] <= $award['award_trigger']))
				{
					// Give this member a hoho, if they don't already have it, and stop looking for more
					if (!in_array($award['id_award'],$user_profile[$member_id]['awardlist']))
						$members[$member_id] = (int) $award['id_award'];
					break;
				}
			}
		}
	}

	return $members;
}

/**
 * Does the database work of setting an autoaward to a member
 * Makes sure each member only has 1 of each award
 *
 * @param type $members
 * @param type $award_type
 * @param type $awardids
 */
function AwardsAutoAssign($members, $award_type, $awardids)
{
	global $smcFunc, $user_profile;

	// init
	$values = array();
	$users = array();
	$remove = array();

	// Set a date
	$date_received = date('Y') . '-' . date('m') . '-' . date('d');

	// Prepare the database values.
	foreach ($members as $member => $memberaward)
	{
		$values[] = array((int) $memberaward, (int) $member, $date_received, (int) $award_type, 1) ;
	    $users[] = $member;

		// These are all the awardids, for this award type, that this user should no longer have
		$remove[$member] = array_diff($awardids, array($memberaward));

		// And this will contain just the specific award_ids that he should no longer have
		$remove[$member] = array_intersect($user_profile[$member]['awardlist'],$remove[$member]);
	}

	// First the removals ... Members can only have one active award of each auto 'type'
	foreach ($members as $member => $dummy)
	{
		if (!empty($remove[$member]))
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}awards_members
				WHERE id_award IN ({array_int:award_list})
					AND id_member = {int:id_member}',
				array(
					'id_member' => $member,
					'award_list' => $remove[$member],
				)
			);
	}

	// Now the adds, Insert the award data
	$smcFunc['db_insert']('insert',
		'{db_prefix}awards_members',
		array('id_award' => 'int', 'id_member' => 'int', 'date_received' => 'string', 'award_type' => 'int', 'active' => 'int'),
		$values,
		array('id_member', 'id_award')
	);

	return;
}

/**
 * returns the number of topics started for each member in memberlist
 *
 * @param type $memberlist
 * @param type $ttl
 */
function AwardsTopicsStarted($memberlist, $ttl = 300)
{
	// Load up how many topics this list of users has started.
	global $modSettings, $user_profile, $smcFunc;

	// init with all members in the query
	$temp = $memberlist;

	// Lets see if this is cached in our "cache in a cache"tm :P
	if (($awards_topic_started = cache_get_data('awards:topic_started', $ttl)) != null)
	{
		// reset this since we have a cache, we will build it for only the members we need data on
		$temp = array();

		// we have *some* cache data, see what members we have data for, and if its not stale use it
		foreach ($memberlist as $member)
		{
			if (isset($awards_topic_started[$member]['update']))
			{
				// See if this member entry, found in the cache is still valid
				if ($awards_topic_started[$member]['update'] >= (time() - $ttl))
					$user_profile[$member]['num_topics'] = $awards_topic_started[$member]['num_topics'];
				else
				{
					// its a stale entry in the cache, add it to our lookup and drop if from the cache array
					unset($awards_topic_started[$member]);
					$temp[] = $member;
				}
			}
			else
				$temp[] = $member;
		}
	}

	// if we did not find them all in the cache, or it was stale then do the query
	if (!empty($temp))
	{
		// Number of topics started.
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*) AS num_topics, id_member_started
			FROM smf_topics
			WHERE id_member_started IN ({array_int:memberlist})' . (!empty($modSettings['recycle_enable']) && $modSettings['recycle_board'] > 0 ? '
				AND id_board != {int:recycle_board}' : '') . '
			GROUP BY id_member_started',
			array(
				'memberlist' => $temp,
				'recycle_board' => $modSettings['recycle_board'],
			)
		);

		// load them in to user_profile
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$user_profile[$row['id_member_started']]['num_topics'] = $row['num_topics'];

			// add them to our existing cache array
			$awards_topic_started[$row['id_member_started']]['num_topics'] = $row['num_topics'];
			$awards_topic_started[$row['id_member_started']]['update'] = time();
		}
		$smcFunc['db_free_result']($request);
	}

	// put this back in the cache
	cache_put_data('awards:topic_started', $awards_topic_started, $ttl);
}

/**
 * returns the top X posters in $user_profile
 *
 * @param type $limit
 */
function AwardsTopPosters_1_N($limit = 10)
{
	global $user_profile, $smcFunc;

	// Top Posters 1-N, basis from stats.php
	// Try to cache this part of the query since its generic and slow
	if (($members = cache_get_data('awards_top_posters', 360)) == null)
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_member, posts
			FROM {db_prefix}members
			WHERE posts > {int:no_posts}
			ORDER BY posts DESC
			LIMIT {int:limit}',
			array(
				'no_posts' => 0,
				'limit' => $limit
			)
		);

		$poster_number = 0;
		$members = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$poster_number++;
			$members[$row['id_member']] = $poster_number;
		}

		// close up
		$smcFunc['db_free_result']($request);

		// save this one for the next few mins ....
		cache_put_data('awards_top_posters', $members, 360);
	}

	if (empty($members))
		$members = array(0 => 0);

	// Load them up so we can see if the kids have won a new toy
	foreach ($members as $id_member => $poster_number)
		$user_profile[$id_member]['top_posters'] = $poster_number;
}

/**
 * returns the top X topic starters in $user_profile
 *
 * @param type $limit
 */
function AwardsTopTopicStarter_1_N($limit = 10)
{
	global $modSettings, $user_profile, $smcFunc;

	// Code basis from stats.php
	// Try to cache this part of the query when possible, because it's a bit of a pig :8
	if (($members = cache_get_data('awards_top_starters', 360)) == null)
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_member_started, COUNT(*) AS hits
			FROM {db_prefix}topics' . (!empty($modSettings['recycle_enable']) && $modSettings['recycle_board'] > 0 ? '
			WHERE id_board != {int:recycle_board}' : '') . '
			GROUP BY id_member_started
			ORDER BY hits DESC
			LIMIT {int:limit}',
			array(
				'recycle_board' => $modSettings['recycle_board'],
				'limit' => $limit
			)
		);
		$members = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$members[$row['id_member_started']] = $row['hits'];
		$smcFunc['db_free_result']($request);

		// save this one for the next few mins ....
		cache_put_data('awards_top_starters', $members, 360);
	}

	// Need to have something ....
	if (empty($members))
		$members = array(0 => 0);

	// And now get the top 1-N topic starter.
	$request = $smcFunc['db_query']('top_topic_starters', '
		SELECT id_member
		FROM {db_prefix}members
		WHERE id_member IN ({array_int:member_list})
		ORDER BY FIND_IN_SET(id_member, {string:top_topic_posters})
		LIMIT {int:limit}',
		array(
			'member_list' => array_keys($members),
			'top_topic_posters' => implode(',', array_keys($members)),
			'limit' => $limit
		)
	);

	// Make them available for use to use in user_profile
	$topic_number = 0;
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$topic_number++;
		$user_profile[$row['id_member']]['top_topics'] = $topic_number;
	}
	$smcFunc['db_free_result']($request);
}

/**
 * returns the top X time on line members in $user_profile
 *
 * @param type $limit
 */
function AwardsTopTimeon_1_N($limit = 10)
{
	global $user_profile, $smcFunc;

	// The time on line 1-N list will not change that often, so cache it for a bit
	$temp = cache_get_data('awards_total_time_members', 600);
	$request = $smcFunc['db_query']('', '
		SELECT id_member, total_time_logged_in
		FROM {db_prefix}members' . (!empty($temp) ? '
		WHERE id_member IN ({array_int:member_list_cached})' : '') . '
		ORDER BY total_time_logged_in DESC
		LIMIT {int:limit}',
		array(
			'member_list_cached' => $temp,
			'limit' => $limit
		)
	);

	// init
	$time_number = 0;
	$temp2 = array();

	// Make them available for use to use in user_profile
	while ($row_members = $smcFunc['db_fetch_assoc']($request))
	{
		$temp2[] = (int) $row_members['id_member'];
		if ($time_number++ >= $limit)
			continue;

		$user_profile[ $row_members['id_member']]['top_time'] = $time_number;
	}
	$smcFunc['db_free_result']($request);

	// Cache the ones we found for a bit, just so we don't have to look again.
	if ($temp !== $temp2)
		cache_put_data('awards_total_time_members', $temp2, 600);
}

/**
 * returns the top X join date based in $user_profile
 *
 * @param type $memberlist
 */
function AwardsSeniority($memberlist)
{
	// Load up how long this member has been a member X.x years.months
	global $user_profile;
	$now = time();

	foreach ($memberlist as $member)
		$user_profile[$member]['join_length'] = AwardsDateDiff($user_profile[$member]['date_registered'],$now);
}

/**
 * returns the karma level for the given list of users
 *
 * @param type $memberlist
 */
function AwardsPopularity($memberlist)
{
	// Get members total positive karma, the values are set via loadusersettings for us
	global $user_profile;
	foreach ($memberlist as $member)
	{
		$kg = !empty($user_profile[$member]['karma_good']) ? $user_profile[$member]['karma_good'] : 0;
		$kb = !empty($user_profile[$member]['karma_bad']) ? $user_profile[$member]['karma_bad'] : 0;
		$user_profile[$member]['popularity'] = $kg - $kb;
	}
}

/**
 * utility function to get the x.y years between to dates e.g. 1.5 is 1 year 6 months
 *
 * @param type $time1
 * @param type $time2
 */
function AwardsDateDiff($time1, $time2)
{
	// To try and account for leap years, and php4, we do all this
	$intervals = array('year','month'); // add day if you need it ;)
	$diffs = array();
	$times = array();

	// Loop thru all intervals
	foreach ($intervals as $interval)
	{
		$diffs[$interval] = 0;

		// Create a temp time from time1 for this 'interval'
		$ttime = strtotime('+1 ' . $interval, $time1);

		// Loop until temp time is smaller than time2
		while ($time2 >= $ttime)
		{
			$time1 = $ttime;
			$diffs[$interval]++;
			$ttime = strtotime('+1 ' . $interval, $time1);
		}
	}

	// build our return array of year, month
	foreach ($diffs as $interval => $value)
		$times[$interval] = !empty($value) ? $value : 0;

	return $times['year'] + $times['month']/12;
}