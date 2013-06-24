<?php

/**
 * @name      Awards Modification
 * @license   Mozilla Public License version 2.0 http://mozilla.org/MPL/2.0/.
 *
 * @version   3.0
 *
 * Original Software by:           Juan "JayBachatero" Hernandez
 * Copyright (c) 2006-2009:        YodaOfDarkness (Fustrate)
 * Copyright (c) 2010:             Jason "JBlaze" Clemons
 *
 */

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

/**
 * Loads an award by ID and places the values in to context
 *
 * @param int $id
 */
function AwardsLoadAward($id = -1)
{
	global $context, $smcFunc, $modSettings, $scripturl;

	// Load single award
	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}awards
		WHERE id_award = {int:id}
		LIMIT 1',
		array(
			'id' => $id
		)
	);
	$row = $smcFunc['db_fetch_assoc']($request);

	// Check if that award actually exists
	if (count($row['id_award']) != 1)
		fatal_lang_error('awards_error_no_award');

	$context['award'] = array(
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
}

/**
 * Used to validate images uploaded are valid for the system
 * @todo incomplete
 *
 * @param string $name
 * @param int $id
 */
function AwardsValidateImage($name, $id)
{
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

	// @todo
	// awards_error_upload_size
	// AwardsValidateImage('awardFile', $id_award);
	// AwardsValidateImage('awardFileMini', $id_award);
}

/**
 * Get the list of groups that this member can see
 * Counts the number of members in each group (including post count based ones)
 * returns the values in $context['groups']
 */
function AwardsGetGroups()
{
	global $context, $smcFunc, $user_info;

	// Get started
	$context['groups'] = array();
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

		$context['groups'][$row['id_group']] = array(
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
			$context['groups'][$row['id_group']]['member_count'] += $row['num_members'];
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
				$context['groups'][$row['id_group']]['member_count'] += $row['num_members'];
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
			$context['groups'][$row['id_group']]['member_count'] += $row['num_members'];
		$smcFunc['db_free_result']($query);
	}
}

/**
 * Callback for createlist
 * List all members and groups who have recived an award
 *
 * @param int $start
 * @param int $items_per_page
 * @param string $sort
 * @param int $id
 */
function AwardsGetMembers($start, $items_per_page, $sort, $id)
{
	global $smcFunc, $txt;

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
 * Used to get the total number of members/groups who have recived a specific award
 *
 * @param type $id
 */
function AwardsGetMembersCount($id)
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