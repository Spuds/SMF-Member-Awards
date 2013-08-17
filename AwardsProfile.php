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

if (!defined('SMF'))
	die('Hacking attempt...');

function showAwards($memID)
{
	global $context, $txt, $scripturl, $sourcedir;

	require_once($sourcedir . '/AwardsSubs.php');

	// Do they want to make a award thier favorite?
	if (isset($_GET['makeFavorite']) && allowedTo(array('profile_extra_any', 'profile_extra_own')))
	{
		// Check session
		checkSession('get');

		// Clean
		$award_id = (int) $_GET['id'];
		$makefav = $_GET['makeFavorite'] > 0 ? 1 : 0;

		// Make it a favorite
		AwardsSetFavorite($memID, $award_id, $makefav);

		// To make changes appear redirect back to that page
		redirectexit('action=profile;area=showAwards;u=' . $memID);
	}

	// Load language & template
	loadLanguage('AwardsManage');
	loadTemplate('AwardsProfile');

	// Count the number of items in the database for create index
	$context['count_awards'] = AwardsCountMembersAwards($memID);

	// Calculate the number of results to pull up.
	$max_awards = 25;

	// Construct the page index
	$context['page_index'] = constructPageIndex($scripturl . '?action=profile;area=showAwards;u=' . $memID, $_REQUEST['start'], $context['count_awards'], $max_awards);
	$start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

	// Load the individual and group awards
	$context['categories'] = AwardsLoadMembersAwards($start, $max_awards, $memID);

	$context['page_title'] = $txt['profile'] . ' - ' . $txt['awards_title'];
	$context['sub_template'] = 'awards';
	$context['allowed_fav'] = ($context['user']['is_owner'] && allowedTo('profile_view_own')) || allowedTo('profile_extra_any');
}

/**
 * Shows all members that have received an award
 * Action from profile when viewing available user awards
 */
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
			'function' => 'AwardsLoadMembers',
			'params' => array(
				$id,
			),
		),
		'get_count' => array(
			'function' => 'AwardsLoadMembersCount',
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

/**
 * Shows all available awards that they can acheive / request
 */
function listAwards()
{
	global $context, $txt, $scripturl, $sourcedir, $user_info, $user_profile;

	loadLanguage('AwardsManage');
	loadTemplate('AwardsProfile');
	require_once($sourcedir . '/AwardsSubs.php');

	// Number of awards in the system
	$countAwards = AwardsCount();

	// Calculate the number of results to pull up.
	$maxAwards = 20;

	// Construct the page index
	$context['page_index'] = constructPageIndex($scripturl . '?action=profile;area=listAwards', $_REQUEST['start'], $countAwards, $maxAwards);
	$start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

	// Array of this members awards to prevent a request for something they have
	$awardcheck = array();
	$awards = $user_profile[$user_info['id']]['awards'];
	foreach ($awards as $award)
		$awardcheck[$award['id']] = 1;

	// Select the awards and their categories.
	$context['categories'] = AwardsListAll($start, $maxAwards, $awardcheck);

	$context['page_title'] = $txt['profile'] . ' - ' . $txt['awards_title'];
	$context['sub_template'] = 'awards_list';
}

/**
 * Allow a member to request an award and add it to the approval queue
 */
function requestAwards()
{
	global $context, $txt, $smcFunc, $sourcedir, $user_info, $user_profile;

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
		AwardsMakeRequest($id, $date, $comments, true);

		updateSettings(array(
			'awards_request' => $modSettings['awards_request'],
		));

		// Redirect to their awards page.
		redirectexit('action=profile;area=showAwards;u=' . $user_info['id']);
	}
}