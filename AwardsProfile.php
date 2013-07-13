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

function showAwards($memID)
{
	global $context, $txt, $scripturl, $modSettings, $settings, $smcFunc, $user_info;

	// Do they want to make a favorite?
	if (isset($_GET['makeFavorite']) && allowedTo(array('profile_extra_any', 'profile_extra_own')))
	{
		// Check session
		checkSession('get');

		// Do they only allow one fav?
		if (empty($modSettings['awards_favorites']))
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}awards_members
				SET favorite = 0
				WHERE id_member = {int:mem}',
				array(
					'mem' => $memID,
				)
			);

		// Now make this one a fav.
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}awards_members
			SET favorite = {int:make_favorite}
			WHERE id_award = {int:award}
				AND id_member = {int:mem}
			LIMIT 1',
			array(
				'award' => (int) $_GET['id'],
				'mem' => $memID,
				'make_favorite' => ((int) $_GET['makeFavorite'] > 0) ? 1 : 0,
			)
		);

		// To make changes appear redirect back to that page
		redirectexit('action=profile;area=showAwards;u=' . $memID);
	}

	// Load language & template
	loadLanguage('AwardsManage');
	loadTemplate('AwardsProfile');

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
	list ($context['count_awards']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Calculate the number of results to pull up.
	$max_awards = 25;

	// Construct the page index
	$context['page_index'] = constructPageIndex($scripturl . '?action=profile;area=showAwards;u=' . $memID, $_REQUEST['start'], $context['count_awards'], $max_awards);
	$context['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

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
			'start' => $context['start'],
			'end' => $max_awards,
			'member' => $memID,
			'groups' => $user_info['groups'],
			'active' => 1
		)
	);

	$context['categories'] = array();

	// Fetch the award info just once
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($context['categories'][$row['id_category']]['name']))
			$context['categories'][$row['id_category']] = array(
				'name' => $row['category_name'],
				'view' => $scripturl . '?action=admin;area=awards;sa=viewcategory;id=' . $row['id_category'],
				'awards' => array(),
			);

		$context['categories'][$row['id_category']]['awards'][$row['id_award']] = array(
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

	$context['page_title'] = $txt['profile'] . ' - ' . $txt['awards_title'];
	$context['sub_template'] = 'awards';
	$context['allowed_fav'] = ($context['user']['is_owner'] && allowedTo('profile_view_own')) || allowedTo('profile_extra_any');
}

function membersAwards()
{
	global $context, $scripturl, $txt, $sourcedir;

	// Are they allowed to see the memberlist at all?
	isAllowedTo('view_mlist');

	// Load language & template
	loadLanguage('AwardsManage');
	loadTemplate('AwardsProfile');

	// Load in our helper functions
	require_once($sourcedir . '/AwardsSubs.php');
	require_once($sourcedir . '/Subs-List.php');

	// Load this awards details
	$id = (int) $_REQUEST['a_id'];
	AwardsLoadAward($id);

	// build the listoption array to display the data
	$listOptions = array(
		'id' => 'view_assigned',
		'title' => $txt['awards_showmembers'] . ': ' . $context['award']['award_name'],
		'items_per_page' => 25,
		'no_items_label' => $txt['awards_no_assigned_members2'],
		'base_href' => $scripturl . '?action=profile;area=membersAwards;a_id=' . $id,
		'default_sort_col' => 'username',
		'get_items' => array(
			'function' => 'AwardsGetMembers',
			'params' => array(
				$id,
			),
		),
		'get_count' => array(
			'function' => 'AwardsGetMembersCount',
			'params' => array(
				$id,
			),
		),
		'columns' => array(
			'username' => array(
				'header' => array(
					'value' => $txt['username'],
				),
				'data' => array(
					'db' => 'link',
				),
				'sort' => array(
					'default' => 'm.real_name ',
					'reverse' => 'm.real_name DESC',
				),
			),
			'date' => array(
				'header' => array(
					'value' => $txt['awards_date'],
				),
				'data' => array(
					'db' => 'date',
				),
				'sort' => array(
					'default' => 'a.date_received DESC',
					'reverse' => 'a.date_received',
				),
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'top_of_list',
				'value' => '<br class="clear" />',
			),
		),
	);

	// Set the context values
	$context['page_title'] = $txt['awards_title'] . ' - ' . $context['award']['award_name'];
	$context['sub_template'] = 'awards_members';

	// Create the list.
	createList($listOptions);
}

function listAwards()
{
	global $context, $txt, $scripturl, $modSettings, $smcFunc, $user_info, $user_profile;

	loadLanguage('AwardsManage');
	loadTemplate('AwardsProfile');

	// Count the number of items in the database for create index
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}awards',
		array()
	);

	list($countAwards) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Calculate the number of results to pull up.
	$maxAwards = 20;

	// Construct the page index
	$context['page_index'] = constructPageIndex($scripturl . '?action=profile;area=listAwards', $_REQUEST['start'], $countAwards, $maxAwards);
	$context['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

	// Select the awards and their categories.
	$request = $smcFunc['db_query']('', '
		SELECT a.*, c.category_name
		FROM {db_prefix}awards AS a
			LEFT JOIN {db_prefix}awards_categories AS c ON (c.id_category = a.id_category)
		ORDER BY c.category_name DESC, a.award_name DESC
		LIMIT {int:start}, {int:end}',
		array(
			'start' => $context['start'],
			'end' => $maxAwards
		)
	);

	// array of this members awards to prevent a request for something they have
	$awardcheck = array();
	$awards = $user_profile[$user_info['id']]['awards'];
	foreach ($awards as $award)
		$awardcheck[$award['id']] = 1;

	// Loop through the results.
	$context['categories'] = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($context['categories'][$row['id_category']]['name']))
			$context['categories'][$row['id_category']] = array(
				'name' => $row['category_name'],
				'view' => $scripturl . '?action=admin;area=awards;sa=viewcategory;id=' . $row['id_category'],
				'awards' => array(),
			);

		$context['categories'][$row['id_category']]['awards'][] = array(
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

	$context['page_title'] = $txt['profile'] . ' - ' . $txt['awards_title'];
	$context['sub_template'] = 'awards_list';
}

function requestAwards()
{
	global $context, $modSettings, $txt, $smcFunc, $sourcedir, $user_info, $user_profile;

	// Load language
	loadLanguage('AwardsManage');
	loadTemplate('AwardsProfile');

	// Load in our helper functions
	require_once($sourcedir . '/AwardsSubs.php');

	// First step, load the details of the requested award
	if (!isset($_GET['step']) || $_GET['step'] != 2)
	{
		// Load this awards details for the form
		$id = (int) $_REQUEST['a_id'];
		AwardsLoadAward($id);

		// Not requestable, how did we get here?
		if (empty($context['award']['requestable']))
			fatal_lang_error('awards_error_not_requestable');

		// Dude allready has this one?
		foreach ($user_profile[$user_info['id']]['awards'] as $award)
			if ($award['id'] == $id)
				fatal_lang_error('awards_error_have_already');

		// Set the context values
		$context['step'] = 1;
		$context['page_title'] = $txt['awards_request_award'] . ' - ' . $context['award']['award_name'];
		$context['sub_template'] = 'awards_request';
	}
	// step '2', they have actually demanded an award!
	elseif (isset($_GET['step']) && (int) $_GET['step'] == 2)
	{
		// Check session.
		checkSession('post');

		// Clean those dirty pigs.
		$id = (int) $_POST['id_award'];
		$comments = strtr($smcFunc['htmlspecialchars']($_POST['comments'], ENT_QUOTES), array("\n" => '<br />', '"' => '&quot;', '<' => '&lt;', '>' => '&gt;', '  ' => ' &nbsp;'));
		censorText($comments);
		$date = date('Y-m-d');

		// let's see if the award exists, silly hackers
		AwardsLoadAward($id);

		// Not requestable, how did we get here?
		if (empty($context['award']['requestable']))
			fatal_lang_error('awards_error_not_requestable');

		// cant ask for what you have
		foreach ($user_profile[$user_info['id']]['awards'] as $award)
			if ($award['id'] == $id)
				fatal_lang_error('awards_error_have_already');

		// If we made it this far insert /replace it so it can be reviewed.
		$request = $smcFunc['db_insert']('replace', '
			{db_prefix}awards_members',
			array('id_award' => 'int', 'id_member' => 'int', 'id_group' => 'int', 'date_received' => 'string', 'favorite' => 'int', 'award_type' => 'int', 'active' => 'int', 'comments' => 'string'),
			array ($id, $user_info['id'], 0, $date, 0, 1, 0, $comments),
			array('id_member', 'id_award')
		);

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

		// Redirect to their awards page.
		redirectexit('action=profile;area=showAwards;u=' . $user_info['id']);
	}
}