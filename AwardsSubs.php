<?php

/**
 * @name      Awards Modification
 * @license   Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version   3.0
 *
 * Original Software by:           Juan "JayBachatero" Hernandez
 * Copyright (c) 2006-2009:        YodaOfDarkness (Fustrate)
 * Copyright (c) 2010:             Jason "JBlaze" Clemons
 *
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Loads an award by ID and places the values in to context
 *
 * @param int $id
 */
function AwardsLoadAward($id = -1)
{
	global $smcFunc, $modSettings, $scripturl;

	// Load single award
	$request = $smcFunc['db_query']('', '
		SELECT id_award, award_name, description, id_category, time_added, award_trigger,
			award_type, award_location, award_requestable, award_assignable, filename, minifile
		FROM {db_prefix}awards
		WHERE id_award = {int:id}
		LIMIT 1',
		array(
			'id' => $id
		)
	);
	$award = array();
	$row = $smcFunc['db_fetch_assoc']($request);

	// Check if that award actually exists
	if (count($row['id_award']) != 1)
		fatal_lang_error('awards_error_no_award');

	$award = array(
		'id' => $row['id_award'],
		'award_name' => $row['award_name'],
		'description' => $row['description'],
		'category' => $row['id_category'],
		'time' => timeformat($row['time_added']),
		'trigger' => $row['award_trigger'],
		'type' => $row['award_type'],
		'location' => $row['award_location'],
		'requestable' => $row['award_requestable'],
		'assignable' => $row['award_assignable'],
		'filename' => $row['filename'],
		'minifile' => $row['minifile'],
		'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['filename'],
		'small' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
	);

	// Free results
	$smcFunc['db_free_result']($request);

	return $award;
}

/**
 * Helper function to load the number of awards in a given category
 * Used by createlist in awardsmain
 *
 * @param int $cat
 */
function AwardsCountCategoryAwards($cat)
{
	global $smcFunc;

	// Count the number of items in the database for create index
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(id_award)
		FROM {db_prefix}awards
		WHERE id_category = {int:cat}',
		array(
			'cat' => $cat,
		)
	);
	list($countAwards) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $countAwards;
}

/**
 * Helper function to load the awards in a given category
 * Used by createlist in awardsmain
 *
 * @param int $start
 * @param int $items_per_page
 * @param string $sort
 * @param int $cat
 */
function AwardsLoadCategoryAwards($start, $items_per_page, $sort, $cat)
{
	global $context, $scripturl, $modSettings, $smcFunc;

	// Select the awards and their categories.
	$request = $smcFunc['db_query']('', '
		SELECT
			a.id_category, a.id_award, a.award_name, a.description, a.time_added, a.filename, a.minifile, a.award_type, a.award_requestable, a.award_assignable,
			c.category_name
		FROM {db_prefix}awards AS a
			LEFT JOIN {db_prefix}awards_categories AS c ON (c.id_category = a.id_category)
		WHERE a.id_category = {int:cat}
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:end}',
		array(
			'start' => $start,
			'end' => $items_per_page,
			'sort' => $sort,
			'cat' => $cat,
		)
	);
	$categories = array();
	// Loop through the results.
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		// load up the award details
		$categories[] =	array(
			'id' => $row['id_award'],
			'award_name' => $row['award_name'],
			'award_type' => $row['award_type'],
			'description' => $row['description'],
			'time' => timeformat($row['time_added']),
			'requestable' => $row['award_requestable'],
			'assignable' => $row['award_assignable'],
			'filename' => $row['filename'],
			'minifile' => $row['minifile'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['filename'],
			'small' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
			'edit' => ((allowedTo('manage_awards')) ? $scripturl . '?action=admin;area=awards;sa=modify;a_id=' . $row['id_award'] : ''),
			'delete' =>  ((allowedTo('manage_awards')) ? $scripturl . '?action=admin;area=awards;sa=delete;a_id=' . $row['id_award'] . ';' . $context['session_var'] . '=' . $context['session_id'] : ''),
			'assign' => ((allowedTo('manage_awards') || !empty($row['award_assignable'])) ? $scripturl . '?action=admin;area=awards;sa=assign;step=1;a_id=' . $row['id_award'] : ''),
			'view_assigned' => $scripturl . '?action=admin;area=awards;sa=viewassigned;a_id=' . $row['id_award'],
		);
	}
	$smcFunc['db_free_result']($request);

	return $categories;
}

/**
 * Determines the number of individual or group awards a member has received
 *
 * @param int $memID
 */
function AwardsCountMembersAwards($memID)
{
	global $smcFunc, $user_info;

	// Count the number of items in the database for create index
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}awards_members
		WHERE (id_member = {int:mem}
			OR (id_member = -1 AND id_group IN({array_int:groups})))
			AND active = {int:active}',
		array(
			'mem' => $memID,
			'groups' => $user_info['groups'],
			'active' => 1
		)
	);
	list ($count_awards) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $count_awards;
}

/**
 * Loads in the awards/categories details for an members set of awards
 *
 * @param int $start
 * @param int $end
 * @param int $memID
  */
function AwardsLoadMembersAwards($start, $end, $memID)
{
	global $smcFunc, $user_info, $scripturl, $settings;

	// Load the individual and group awards
	$request = $smcFunc['db_query']('', '
		SELECT
			aw.id_award, aw.award_name, aw.description, aw.filename, aw.minifile,
			am.id_member, am.date_received, am.favorite, am.id_group,
			c.category_name, c.id_category
		FROM {db_prefix}awards AS aw
			LEFT JOIN {db_prefix}awards_members AS am ON (am.id_award = aw.id_award)
			LEFT JOIN {db_prefix}awards_categories AS c ON (c.id_category = aw.id_category)
		WHERE (am.id_member = {int:member}
			OR (am.id_member = -1 AND am.id_group IN({array_int:groups})))
			AND am.active = {int:active}
		ORDER BY am.favorite DESC, c.category_name DESC, aw.award_name DESC
		LIMIT {int:start}, {int:end}',
		array(
			'start' => $start,
			'end' => $end,
			'member' => $memID,
			'groups' => $user_info['groups'],
			'active' => 1
		)
	);
	$categories = array();
	// Fetch the award info just once
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($categories[$row['id_category']]['name']))
			$categories[$row['id_category']] = array(
				'name' => $row['category_name'],
				'view' => $scripturl . '?action=admin;area=awards;sa=viewcategory;id=' . $row['id_category'],
				'awards' => array(),
			);

		$categories[$row['id_category']]['awards'][$row['id_award']] = array(
			'id' => $row['id_award'],
			'award_name' => $row['award_name'],
			'description' => $row['description'],
			'more' => $scripturl . '?action=profile;area=membersAwards;a_id=' . $row['id_award'],
			'favorite' => array(
				'fav' => $row['favorite'],
				'href' => $scripturl . '?action=profile;area=showAwards;id=' . $row['id_award'] . ';makeFavorite=' . ($row['favorite'] == 1 ? '0' : '1') . (isset($_REQUEST['u']) ? ';u=' . $_REQUEST['u'] : ''),
				'img' => '<img src="' . $settings['images_url'] . '/awards/' . ($row['favorite'] == 1 ? 'delete' : 'add') . '.png" alt="' . $txt['awards_favorite'] . '" title="' . $txt['awards_favorite'] . '" />',
			),
			'filename' => $row['filename'],
			'time' => list ($year, $month, $day) = sscanf($row['date_received'], '%d-%d-%d'),
			'img' => dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/' . $row['filename'],
			'mini' => dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/' . $row['minifile'],
		);
	}
	$smcFunc['db_free_result']($request);

	return $categories;
}

/**
 * Gets all of the non-auto assignable awards for use in the template
 * Returns an array of awards/details for each one allowable
 */
function AwardsLoadAssignableAwards()
{
	global $smcFunc;

	// Select the awards (NON-auto) that can be assigned by this member
	$request = $smcFunc['db_query']('', '
		SELECT id_award, award_name, filename, minifile, description, award_assignable
		FROM {db_prefix}awards
		WHERE award_type <= {int:type}' . ((allowedTo('manage_awards')) ? '' : ' AND award_assignable = {int:assign}') . '
		ORDER BY award_name ASC',
		array(
			'type' => 1,
			'assign' => 1,
		)
	);
	$awards = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$awards[$row['id_award']] = array(
			'award_name' => $row['award_name'],
			'filename' => $row['filename'],
			'minifile' => $row['minifile'],
			'description' => $row['description'],
			'assignable' => $row['award_assignable']
		);
	}
	$smcFunc['db_free_result']($request);

	return $awards;
}

/**
 * Loads all of the member requestable awards that have active requests against them
 * Finds members that have requested these awards for approval display
 */
function AwardsLoadRequestedAwards()
{
	global $scripturl, $modSettings, $settings, $smcFunc;

	// Select all the requestable awards so we have the award specifics
	$request = $smcFunc['db_query']('', '
		SELECT a.id_award, a.award_name, a.filename, a.minifile, a.description
		FROM {db_prefix}awards as a
			LEFT JOIN {db_prefix}awards_members as am ON (a.id_award = am.id_award)
		WHERE a.award_type <= {int:type}
			AND a.award_requestable = {int:requestable}
			AND am.active = {int:active}
		ORDER BY award_name ASC',
		array(
			'type' => 1,
			'requestable' => 1,
			'active' => 0,
		)
	);
	$awards = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$awards[$row['id_award']] = array(
			'id' => $row['id_award'],
			'award_name' => $row['award_name'],
			'filename' => $row['filename'],
			'minifile' => $row['minifile'],
			'description' => $row['description'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['filename'],
			'miniimg' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
			'members' => array(),
		);
	}
	$smcFunc['db_free_result']($request);

	// Now get just the members awaiting approval
	$request = $smcFunc['db_query']('', '
		SELECT mem.real_name, mem.id_member,
			am.id_award, am.comments
		FROM {db_prefix}awards_members AS am
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = am.id_member)
		WHERE am.active = {int:active}',
		array(
			'active' => 0
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$awards[$row['id_award']]['members'][$row['id_member']] = array(
			'id' => $row['id_member'],
			'name' => $row['real_name'],
			'href' => $scripturl . '?action=profile;u=' . $row['id_member'],
			'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
			'pm' => '<a href="' . $scripturl . '?action=pm;sa=send;u=' . $row['id_member'] . '"><img src="' . $settings['images_url'] . '/icons/pm_read.gif" alt="" /></a>',
			'comments' => $row['comments'],
		);
	}
	$smcFunc['db_free_result']($request);

	return $awards;
}

/**
 * Adds a new award to the system, returns the id
 *
 * @param string $award_name
 * @param string $description
 * @param int $time_added
 * @param int $category
 * @param int $award_type
 * @param int $trigger
 * @param int $award_location
 * @param int $award_requestable
 * @param int $award_assignable
 */
function AwardsAddAward($award_name, $description, $time_added, $category, $award_type, $trigger, $award_location, $award_requestable, $award_assignable)
{
	global $smcFunc;

	// Add in a new award
	$smcFunc['db_insert']('replace', '{db_prefix}awards',
		array(
			'award_name' => 'string',
			'description' => 'string',
			'time_added' => 'int',
			'id_category' => 'int',
			'award_type' => 'int',
			'award_trigger' => 'int',
			'award_location' => 'int',
			'award_requestable' => 'int',
			'award_assignable' =>'int'
		),
		array(
			$award_name,
			$description,
			$time_added,
			$category,
			$award_type,
			$trigger,
			$award_location,
			$award_requestable,
			$award_assignable
		),
		array('id_award')
	);

	// Get the id_award for this new award
	$id = $smcFunc['db_insert_id']('{db_prefix}awards', 'id_award');

	return $id;
}

/**
 * Updates an award that already exists in the system
 *
 * @param string $award_name
 * @param string $description
 * @param int $category
 * @param int $award_type
 * @param int $trigger
 * @param int $award_location
 * @param int $award_requestable
 * @param int $award_assignable
 */
function AwardsUpdateAward($award_name, $description, $category, $award_type, $trigger, $award_location, $award_requestable, $award_assignable)
{
	global $smcFunc;

	// Make the updates to the award
	$editAward = $smcFunc['db_query']('', '
		UPDATE {db_prefix}awards
		SET
			award_name = {string:awardname},
			description = {string:award_desc},
			id_category = {int:category},
			award_type = {int:awardtype},
			award_trigger = {int:trigger},
			award_location = {int:awardlocation},
			award_requestable = {int:awardrequestable},
			award_assignable = {int:awardassignable}
		WHERE id_award = {int:id_award}',
		array(
			'awardname' => $award_name,
			'award_desc' => $description,
			'id_award' => $id,
			'category' => $category,
			'awardtype' => $award_type,
			'trigger' => $trigger,
			'awardlocation' => $award_location,
			'awardrequestable' => $award_requestable,
			'awardassignable' => $award_assignable,
		)
	);

	return $editAward;
}

/**
 * Deletes an award by id
 *
 * @param int $id
 */
function AwardsDeleteAward($id)
{
	global $smcFunc;

	// Delete the award
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}awards
		WHERE id_award = {int:award}
		LIMIT 1',
		array(
			'award' => $id
		)
	);
}

/**
 * Loads the list of groups that this member can see
 * Counts the number of members in each group (including post count based ones)
 * returns the array of values
 */
function AwardsLoadGroups()
{
	global $context, $user_info, $smcFunc;

	// Get started
	$groups = array();
	$context['can_moderate'] = allowedTo('manage_membergroups');
	$group_ids_pc = array();
	$group_ids = array();

	// Find all the groups
	$request = $smcFunc['db_query']('', '
		SELECT mg.id_group, mg.group_name, mg.group_type, mg.hidden,
			IFNULL(gm.id_member, 0) AS can_moderate, CASE WHEN min_posts != {int:min_posts} THEN 1 ELSE 0 END AS is_post_group
		FROM {db_prefix}membergroups AS mg
			LEFT JOIN {db_prefix}group_moderators AS gm ON (gm.id_group = mg.id_group AND gm.id_member = {int:current_member})
		WHERE mg.id_group != {int:mod_group}' . (allowedTo('admin_forum') ? '' : '
			AND mg.group_type != {int:is_protected}') . '
		ORDER BY group_name',
		array(
			'current_member' => $user_info['id'],
			'min_posts' => -1,
			'mod_group' => 3,
			'is_protected' => 1,
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		// If this group is hidden then it can only exist if the user can moderate it!
		if ($row['hidden'] && !$row['can_moderate'] && !allowedTo('manage_membergroups'))
			continue;

		$groups[$row['id_group']] = array(
			'id' => $row['id_group'],
			'name' => $row['group_name'],
			'type' => $row['group_type'],
			'is_post_group' => $row['is_post_group'],
			'member_count' => 0,
		);

		$context['can_moderate'] |= $row['can_moderate'];

		// Keep track of the groups we can see as normal or post count
		if (!empty($row['is_post_group']))
			$group_ids_pc[] = $row['id_group'];
		else
			$group_ids[] = $row['id_group'];
	}
	$smcFunc['db_free_result']($request);

	// Now count up the number of members in each of the normal groups
	if (!empty($group_ids))
	{
		$query = $smcFunc['db_query']('', '
			SELECT id_group, COUNT(*) AS num_members
			FROM {db_prefix}members
			WHERE id_group IN ({array_int:group_list})
			GROUP BY id_group',
			array(
				'group_list' => $group_ids,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($query))
			$groups[$row['id_group']]['member_count'] += $row['num_members'];
		$smcFunc['db_free_result']($query);

		// Only do additional groups if we can moderate...
		if ($context['can_moderate'])
		{
			$query = $smcFunc['db_query']('', '
				SELECT mg.id_group, COUNT(*) AS num_members
				FROM {db_prefix}membergroups AS mg
					INNER JOIN {db_prefix}members AS mem ON (mem.additional_groups != {string:blank_screen}
						AND mem.id_group != mg.id_group
						AND FIND_IN_SET(mg.id_group, mem.additional_groups) != 0)
				WHERE mg.id_group IN ({array_int:group_list})
				GROUP BY mg.id_group',
				array(
					'group_list' => $group_ids,
					'blank_screen' => '',
				)
			);
			while ($row = $smcFunc['db_fetch_assoc']($query))
				$groups[$row['id_group']]['member_count'] += $row['num_members'];
			$smcFunc['db_free_result']($query);
		}
	}

	// Now on to the post count groups
	if (!empty($group_ids_pc))
	{
		$query = $smcFunc['db_query']('', '
			SELECT id_post_group AS id_group, COUNT(*) AS num_members
			FROM {db_prefix}members
			WHERE id_post_group IN ({array_int:group_list})
			GROUP BY id_post_group',
			array(
				'group_list' => $group_ids_pc,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($query))
			$groups[$row['id_group']]['member_count'] += $row['num_members'];
		$smcFunc['db_free_result']($query);
	}

	return $groups;
}

/**
 * For a given set of groups, gets all of the members in those groups
 */
function AwardsLoadGroupMembers()
{
	global $smcFunc;

	$members = array();

	// Stop any monkey business
	$allowed_groups = $_SESSION['allowed_groups'];
	$_POST['who'] = array_intersect_key($_POST['who'], $allowed_groups);
	$postsave = $_POST['who'];
	unset($_SESSION['allowed_groups']);

	// Did they select the moderator group
	if (!empty($_POST['who']) && in_array(3, $_POST['who']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT DISTINCT mem.id_member, mem.real_name
			FROM ({db_prefix}members AS mem, {db_prefix}moderators AS mods)
			WHERE mem.id_member = mods.id_member
			ORDER BY mem.real_name ASC',
			array(
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$members[$row['id_member']] = $row['real_name'];
		$smcFunc['db_free_result']($request);
		unset($_POST['who'][3]);
	}

	// How about regular members, they are people too, well most of them :P
	if (!empty($_POST['who']) && in_array(0, $_POST['who']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT mem.id_member, mem.real_name
			FROM {db_prefix}members AS mem
			WHERE mem.id_group = {int:id_group}
			ORDER BY mem.real_name ASC',
			array(
				'id_group' => 0,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$members[$row['id_member']] = $row['real_name'];
		$smcFunc['db_free_result']($request);
		unset($_POST['who'][0]);
	}

	// Anyone else ?
	if (!empty($_POST['who']))
	{
		// Select the members.
		$request = $smcFunc['db_query']('', '
			SELECT id_member, real_name
			FROM ({db_prefix}members AS mem, {db_prefix}membergroups AS mg)
			WHERE (mg.id_group = mem.id_group OR FIND_IN_SET(mg.id_group, mem.additional_groups) OR mg.id_group = mem.id_post_group)
			AND mg.id_group IN ({array_int:who})
			ORDER BY real_name ASC',
			array(
				'who' => $_POST['who'],
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$members[$row['id_member']] = $row['real_name'];
		$smcFunc['db_free_result']($request);
	}

	// Put it back so the template remains loaded (checked)
	$_POST['who'] = $postsave;

	return $members;
}

/**
 * Callback for createlist
 * List all members and groups who have received an award
 *
 * @param int $start
 * @param int $items_per_page
 * @param string $sort
 * @param int $id
 */
function AwardsLoadMembers($start, $items_per_page, $sort, $id)
{
	global $txt, $smcFunc;

	// All the members with this award
	$request = $smcFunc['db_query']('', '
		SELECT
			m.real_name, m.member_name,
			a.id_member, a.date_received, a.id_group, a.uniq_id,
			g.group_name
		FROM {db_prefix}awards_members AS a
			LEFT JOIN {db_prefix}members AS m ON (m.id_member = a.id_member)
			LEFT JOIN {db_prefix}membergroups AS g ON (a.id_group = g.id_group)
		WHERE a.id_award = {int:award}
			AND a.active = {int:active}
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:per_page}',
		array(
			'award' => (int) $id,
			'active' => 1,
			'sort' => $sort,
			'start' => $start,
			'per_page' => $items_per_page,
		)
	);
	$members = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		// Group award?
		if ($row['id_member'] == -1)
		{
			$row['member_name'] = $row['group_name'];
			$row['real_name'] = $txt['awards_assign_membergroup'];
		}
		$members[] = $row;
	}
	$smcFunc['db_free_result']($request);

	return $members;
}

/**
 * Callback for createlist
 * Used to get the total number of members/groups who have received a specific award
 *
 * @param type $id
 */
function AwardsLoadMembersCount($id)
{
	global $smcFunc;

	// Count the number of items in the database for create index
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}awards_members
		WHERE id_award = {int:award}
			AND active = {int:active}',
		array(
			'award' => (int) $id,
			'active' => 1
		)
	);

	list ($num_members) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $num_members;
}

/**
 * Places a member request for an award in to the system queue
 *
 * @param int $date
 * @param string $comments
 * @param boolean $flush
 */
function AwardsMakeRequest($id, $date, $comments, $flush = true)
{
	global $smcFunc, $user_info, $modSettings;

	$smcFunc['db_insert']('replace', '
		{db_prefix}awards_members',
		array(
			'id_award' => 'int',
			'id_member' => 'int',
			'id_group' => 'int',
			'date_received' => 'string',
			'favorite' => 'int',
			'award_type' => 'int',
			'active' => 'int',
			'comments' => 'string'
		),
		array (
			$id,
			$user_info['id'],
			0,
			$date,
			0,
			1,
			0,
			$comments
		),
		array('id_member', 'id_award')
	);

	// update the cache as well
	if ($flush)
	{
		// Get the number of unapproved requests so the awards team knows about it.
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}awards_members
			WHERE active = {int:active}',
			array(
				'active' => 0
			)
		);
		list($modSettings['awards_request']) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		updateSettings(array(
			'awards_request' => $modSettings['awards_request'],
		));
	}
}

/**
 * Approve or deny a requst by a member for a requestable award
 *
 * @param array $awards
 * @param boolean $approve
 */
function AwardsApproveDenyRequests($awards, $approve = true)
{
	global $smcFunc;

	// Accept the request
	if ($approve)
	{
		// Now for the database.
		foreach ($awards as $id_award => $member)
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}awards_members
				SET active = {int:active}
				WHERE id_award = {int:id_award}
					AND id_member IN ({array_int:members})',
				array(
					'active' => 1,
					'id_award' => $id_award,
					'members' => $awards[$id_award],
				)
			);
	}
	// Or Deny
	else
	{
		// Now for the database.
		foreach ($awards as $id_award => $member)
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}awards_members
				WHERE id_award = {int:id_award}
					AND id_member IN ({array_int:members})',
				array(
					'id_award' => $id_award,
					'members' => $awards[$id_award],
				)
			);
	}

	return;
}

/**
 * Loads all of the categories in the system
 * Returns array of categories with key of name and value of id
 *
 * @param string $sort order to return the categories
 * @param boolean $multi if true will return an array of arrays
 */
function AwardsLoadCategories($sort = 'DESC', $multi = false)
{
	global $smcFunc;

	// Load all the categories.
	$request = $smcFunc['db_query']('', '
		SELECT id_category, category_name
		FROM {db_prefix}awards_categories
		ORDER BY category_name {raw:sort}',
		array(
			'sort' => $sort,
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		// return the data as key names or arrays
		if (!$multi)
			$categories[$row['category_name']] = $row['id_category'];
		else
		{
			$categories[] = array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
			);
		}
	}
	$smcFunc['db_free_result']($request);

	return $categories;
}

/**
 * Loads in the details of a category
 *
 * @param int $id
 */
function AwardsLoadCategory($id)
{
	global $smcFunc;

	// Load single category for editing.
	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}awards_categories
		WHERE id_category = {int:id}
		LIMIT 1',
		array(
			'id' => $id
		)
	);
	$category = array();
	$row = $smcFunc['db_fetch_assoc']($request);

	// Check if that category exists
	if (count($row['id_category']) != 1)
		fatal_lang_error('awards_error_no_category');

	$category = array(
		'id' => $row['id_category'],
		'name' => $row['category_name'],
	);
	$smcFunc['db_free_result']($request);

	return $category;
}

/**
 * Loads all the categories in the system
 * Returns an array of categories and links
 */
function AwardsLoadAllCategories()
{
	global $scripturl, $smcFunc;

	// Load all the categories.
	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}awards_categories'
	);
	$categories = array();
	while($row = $smcFunc['db_fetch_assoc']($request))
		$categories[$row['id_category']] = array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
			'view' => $scripturl . '?action=admin;area=awards;sa=viewcategory;a_id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
			'edit' => $scripturl . '?action=admin;area=awards;sa=editcategory;a_id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
			'delete' => $scripturl . '?action=admin;area=awards;sa=deletecategory;a_id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
		);

	$smcFunc['db_free_result']($request);

	return $categories;
}

/**
 * Returns the number of awards in each category
 *
 * @param int $id
 */
function AwardsInCategories($id = null)
{
	global $smcFunc;

	if ($id === null)
	{
		// Count the number of awards in each category
		$request = $smcFunc['db_query']('', '
			SELECT id_category, COUNT(*) AS num_awards
			FROM {db_prefix}awards
			GROUP BY id_category'
		);
		$categories = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$categories[$row['id_category']]['awards'] = $row['num_awards'];
	}
	else
	{
		// Count the number of awards in a specific category
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}awards
			WHERE id_category = {int:id}',
			array(
				'id' => $id
			)
		);
		$categories = 0;
		list($categories) = $smcFunc['db_fetch_row']($request);
	}

	$smcFunc['db_free_result']($request);

	return $categories;
}

/**
 * Get the total count of awards in the system
 */
function AwardsCount()
{
	global $smcFunc;

	// Count the number of items in the database for create index
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}awards',
		array()
	);
	list($countAwards) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $countAwards;
}

/**
 * Lists all awards in teh system by cat, tuned to a users view
 * @todo combine with AwardsLoadCategoryAwards
 *
 * @param array $awardcheck
 * @return type
 */
function AwardsListAll($start, $end, $awardcheck = array())
{
	global $smcFunc, $scripturl, $modSettings;

	// Select the awards and their categories.
	$request = $smcFunc['db_query']('', '
		SELECT 	a.id_category, a.id_award, a.award_name, a.description, a.time_added, a.filename, a.minifile, a.award_type,
			a.award_requestable, a.award_assignable, a.trigger,
			c.category_name
		FROM {db_prefix}awards AS a
			LEFT JOIN {db_prefix}awards_categories AS c ON (c.id_category = a.id_category)
		ORDER BY c.category_name DESC, a.award_name DESC
		LIMIT {int:start}, {int:end}',
		array(
			'start' => $start,
			'end' => $end
		)
	);
	// Loop through the results.
	$categories = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($categories[$row['id_category']]['name']))
			$categories[$row['id_category']] = array(
				'name' => $row['category_name'],
				'view' => $scripturl . '?action=admin;area=awards;sa=viewcategory;id=' . $row['id_category'],
				'awards' => array(),
			);

		$categories[$row['id_category']]['awards'][] = array(
			'id' => $row['id_award'],
			'award_name' => $row['award_name'],
			'description' => $row['description'],
			'time' => timeformat($row['time_added']),
			'filename' => $row['filename'],
			'minifile' => $row['minifile'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['filename'],
			'miniimg' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
			'view_assigned' => $scripturl . '?action=profile;area=membersAwards;a_id=' . $row['id_award'],
			'trigger' => $row['award_trigger'],
			'award_type' => $row['award_type'],
			'requestable' => (!empty($row['award_requestable']) && empty($awardcheck[$row['id_award']])),
			'requestable_link' => ((!empty($row['award_requestable']) && empty($awardcheck[$row['id_award']])) ? $scripturl . '?action=profile;area=requestAwards;a_id=' . $row['id_award'] : ''),
			'members' => array(),
		);
	}
	$smcFunc['db_free_result']($request);

	return $categories;
}

/**
 * Save or update a category in the system
 * @param string $name
 * @param int $id_category
 */
function AwardsSaveCategory($name, $id_category = 0)
{
	global $smcFunc;

	// Add a new category.
	if (empty($id_category))
	{
		$smcFunc['db_insert']('replace',
			'{db_prefix}awards_categories',
			array('category_name' => 'string'),
			array($name),
			array('id_category')
		);
	}
	// Edit the category
	else
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}awards_categories
			SET category_name = {string:category}
			WHERE id_category = {int:id}',
			array(
				'category' => $name,
				'id' => $id_category
			)
		);
	}

	return;
}

/**
 * Removes a category from the system
 * Moves any awards assigned to that category back to the default cat
 *
 * @param int $id
 */
function AwardsDeleteCategory($id)
{
	global $smcFunc;

	// If any awards will go astray after we delete their category we first move them to
	// the default cat to prevent issues
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}awards
		SET id_category = 1
		WHERE id_category = {int:id}',
		array(
			'id' => $id
		)
	);

	// Now delete the entry from the database.
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}awards_categories
		WHERE id_category = {int:id}
		LIMIT 1',
		array(
			'id' => $id
		)
	);

	return;
}

/**
 * Removes an award by id from all members who received it
 *
 * @param int $id id of the award
 * @param array $members optional array of members to remove the award from
 */
function AwardsRemoveMembers($id, $members = array())
{
	global $smcFunc;

	// If specific members have not been sent, remove it from everyone
	if (empty($members))
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}awards_members
			WHERE id_award = {int:award}',
			array(
				'award' => $id
			)
		);
	// Or if members were sent, just remove it from this log
	else
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}awards_members
			WHERE id_award = {int:id}
				AND uniq_id IN (' . implode(', ', $ids) . ')',
			array(
				'id' => $id
			)
		);
}

/**
 * Adds an award to a group of members
 *
 * @param array $values
 */
function AwardsAddMembers($values, $group = false)
{
	global $smcFunc;

	// Insert the data
	if (!$group)
		$smcFunc['db_insert']('ignore',
			'{db_prefix}awards_members',
			array('id_award' => 'int', 'id_member' => 'int', 'date_received' => 'string', 'active' => 'int'),
			$values,
			array('id_member', 'id_award')
		);
	else
		$smcFunc['db_insert']('ignore',
			'{db_prefix}awards_members',
			array('id_award' => 'int', 'id_member' => 'int', 'id_group' => 'int', 'date_received' => 'string', 'active' => 'int'),
			$values,
			array('id_member', 'id_award')
		);
}

/**
 * For a given award get the full and mini image filenames associated with it
 *
 * @param int id
 */
function AwardsLoadFiles($id)
{
	global $smcFunc;

	$row = array();

	// Lets make sure that we delete the file that we are supposed to and not something harmful
	$request = $smcFunc['db_query']('', '
		SELECT filename, minifile
		FROM {db_prefix}awards
		WHERE id_award = {int:id}',
		array(
			'id' => $id
		)
	);
	$row = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $row;
}

/**
 * Updates the award database with the  image filenames
 * Requires a specific award ID to update
 *
 * @param int $id
 * @param string $newName
 * @param string $miniName
 */
function AwardsAddImage($id, $newName = '', $miniName = '')
{
	global $smcFunc;

	// update the database with this new image so its available.
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}awards
		SET ' . (!empty($newName) ? 'filename = {string:file},' : '') .
				(!empty($miniName) ? 'minifile = {string:mini}' : '') . '
		WHERE id_award = {int:id}',
		array(
			'file' => !empty($newName) ? basename($newName) : '',
			'mini' => !empty($miniName) ? basename($miniName) : '',
			'id' => $id
		)
	);
}

/**
 * This handles the uploading of award images, regular and mini
 * Runs all files though AwardsValidateImage for security
 * To prevent duplicate file; filenames have the awardid prefixed to them
 *
 * @param int $id_award
 */
function AwardsUpload($id_award)
{
	global $modSettings, $boarddir, $sourcedir;

	// Go
	$newName = '';
	$miniName = '';

	// Load in our helper functions
	require_once($sourcedir . '/AwardsSubs.php');

	// Lets try to CHMOD the awards dir if needed.
	if (!is_writable($boarddir . '/' . $modSettings['awards_dir']))
		@chmod($boarddir . '/' . $modSettings['awards_dir'], 0755);

	// Did they upload a new award image
	if ($_FILES['awardFile']['error'] != 4)
	{
		// Make sure the image file made it and its legit
		AwardsValidateImage('awardFile', $id_award);

		// Define $award
		$award = $_FILES['awardFile'];
		$newName = $boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $id_award . '.' . strtolower(substr(strrchr($award['name'], '.'), 1));

		// create the miniName in case we need to use this file as the mini as well
		$miniName = $boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $id_award . '-mini.' . strtolower(substr(strrchr($award['name'], '.'), 1));

		// Move the file to the right directory
		move_uploaded_file($award['tmp_name'], $newName);

		// Try to CHMOD the uploaded file
		@chmod($newName, 0755);
	}

	// Did they upload a mini as well?
	if ($_FILES['awardFileMini']['error'] != 4)
	{
		// Make sure the miniimage file made it and its legit
		AwardsValidateImage('awardFileMini', $id_award);

		// Define $award
		$award = $_FILES['awardFileMini'];
		$miniName = $boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $id_award . '-mini.' . strtolower(substr(strrchr($award['name'], '.'), 1));

		// Now move the file to the right directory
		move_uploaded_file($award['tmp_name'], $miniName);

		// Try to CHMOD the uploaded file
		@chmod($miniName, 0755);
	}
	// No mini just just the regular for it instead
	elseif (($_FILES['awardFileMini']['error'] == 4) && ($_FILES['awardFile']['error'] != 4))
		copy($newName, $miniName);

	// Update the database with this new image(s) so its available.
	AwardsAddImage($id_award, $newName, $miniName);
}

/**
 * Used to validate images uploaded are valid for the system
 *
 * @param string $name
 * @param int $id
 */
function AwardsValidateImage($name, $id)
{
	global $sourcedir, $modSettings;

	$award = $_FILES[$name];

	// Check if file was uploaded.
	if ($award['error'] === 1 || $award['error'] === 2)
		fatal_lang_error('awards_error_upload_size');
	elseif ($award['error'] !== 0)
		fatal_lang_error('awards_error_upload_failed');

	// Check the extensions
	$goodExtensions = array('jpg', 'jpeg', 'gif', 'png');
	if (!in_array(strtolower(substr(strrchr($award['name'], '.'), 1)), $goodExtensions))
		fatal_lang_error('awards_error_wrong_extension');

	// Generally a valid image file?
	$sizes = @getimagesize($award['tmp_name']);
	if ($sizes === false)
		fatal_lang_error('awards_error_upload_failed');

	// Now check if it has a potential virus etc.
	require_once($sourcedir . '/Subs-Graphics.php');
	if (!checkImageContents($award['tmp_name'], !empty($modSettings['avatar_paranoid'])))
		fatal_lang_error('awards_error_upload_security_failed');
}

/**
 * Converts a php array to a JS object
 * Yes I know about json, kthanks
 *
 * @param array $array
 * @param string $object_name
 */
function AwardsBuildJavascriptObject($array, $object_name)
{
    return 'var ' . $object_name . ' = ' . AwardsBuildJavascriptObject_Recurse($array) . ";\n";
}

/**
 * Main function to do the array to JS object conversion
 *
 * @param array $array
 */
function AwardsBuildJavascriptObject_Recurse($array)
{
	// Not an array so just output it.
	if (!is_array($array))
	{
		// Handle null correctly
		if ($array === null)
			return 'null';

		return '"' . $array . '"';
	}

	// Start of this object.
	$retVal = "{";

	// Output all key/value pairs as "$key" : $value
	$first = true;
	foreach ($array as $key => $value)
	{
		// Add a comma before all but the first pair.
		if (!$first)
			$retVal .= ', ';

		$first = false;

		// Quote $key if it's a string.
		if (is_string($key))
			$key = '"' . $key . '"';

		$retVal .= $key . ' : ' . AwardsBuildJavascriptObject_Recurse($value);
	}

	// Close and return the JS object.
	return $retVal . "}";
}