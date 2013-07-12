<?php

/** ******************************************************************************
 * ManageAwards.php                                                              *
 * ----------------------------------------------------------------------------- *
 * This file handles the admin side of Awards.                                   *
 * ********************************************************************************
 * Software version:               2.2.3                                         *
 * Original Software by:           Juan "JayBachatero" Hernandez                 *
 * Copyright (c) 2006-2009:        YodaOfDarkness (Fustrate)                     *
 * Copyright (c) 2010:             Jason "JBlaze" Clemons                        *
 * Contact:                        jclemons@jblaze.net                           *
 * Website:                        http://www.jblaze.net                         *
 * ============================================================================= *
 * This mod is free software; you may redistribute it and/or modify it as long   *
 * as you credit me for the original mod. This mod is distributed in the hope    *
 * that it is and will be useful, but WITHOUT ANY WARRANTIES; without even any   *
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.      *
 *                                                                               *
 * All SMF copyrights are still in effect. Anything not mine is theirs. Enjoy!   *
 * Some code found in here is copy written code by SMF, therefore it can not be  *
 * redistributed without official consent from myself or SMF.                    *
 * *******************************************************************************/

if (!defined('SMF'))
	die('Hacking attempt...');

// The entrance point for all 'Manage Awards' actions.
function Awards()
{
	global $context, $txt;

	$subActions = array(
		'main' => array('AwardsMain'),
		'assign' => array('AwardsAssign'),
		'modify' => array('AwardsModify'),
		'delete' => array('AwardsDelete'),
		'edit' => array('AwardsModify'),
		'settings' => array('AwardsSettings'),
		'viewassigned' => array('AwardsViewAssigned'),
		'categories' => array('ListCategories'),
		'editcategory' => array('EditCategory'),
		'deletecategory' => array('DeleteCategory'),
		'viewcategory' => array('ViewCategory'),
	);

	// Default to sub action 'index' or 'settings' depending on permissions.
	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	// Do the permission check, you might not be allowed here.
	isAllowedTo('manage_awards');

	// Language and template stuff, the usual.
	loadLanguage('ManageAwards');
	loadTemplate('ManageAwards');

	// Setup the admin tabs.
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['awards'],
		'help' => 'awards',
		'description' => $txt['awards_description'],
	);

	$context['tabindex'] = 1;

	// Call the right function.
	$subActions[$_REQUEST['sa']][0]();
}

function AwardsMain()
{
	global $context, $scripturl, $modSettings, $txt, $smcFunc;

	// Count the number of items in the database for create index
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}awards'
	);

	list($countAwards) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Calculate the number of results to pull up.
	$maxAwards = 20;

	// Construct the page index
	$context['page_index'] = constructPageIndex($scripturl . '?action=admin;area=awards', $_REQUEST['start'], $countAwards, $maxAwards);
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

	$context['categories'] = array();

	// Loop through the results.
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($context['categories'][$row['id_category']]['name']))
			$context['categories'][$row['id_category']] = array(
				'name' => $row['category_name'],
				'view' => $scripturl . '?action=admin;area=awards;sa=viewcategory;id=' . $row['id_category'],
				'edit' => $scripturl . '?action=admin;area=awards;sa=editcategory;id=' . $row['id_category'],
				'delete' => $scripturl . '?action=admin;area=awards;sa=deletecategory;id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
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
			'small' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
			'edit' => $scripturl . '?action=admin;area=awards;sa=modify;id=' . $row['id_award'],
			'delete' => $scripturl . '?action=admin;area=awards;sa=delete;id=' . $row['id_award'] . ';' . $context['session_var'] . '=' . $context['session_id'],
			'assign' => $scripturl . '?action=admin;area=awards;sa=assign;step=1;id=' . $row['id_award'],
			'view_assigned' => $scripturl . '?action=admin;area=awards;sa=viewassigned;id=' . $row['id_award'],
		);
	}

	$smcFunc['db_free_result']($request);

	// Setup the title and template.
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_main'];
	$context['sub_template'] = 'main';
}

function AwardsModify()
{
	global $smcFunc, $context, $scripturl, $txt, $modSettings, $boarddir;

	// Check if they are saving the changes
	if (isset($_POST['award_save']))
	{
		checkSession('post');

		// Check if any of the values where left empty
		if (empty($_POST['award_name']))
			fatal_lang_error('awards_error_empty_badge_name');

		if (empty($_FILES['awardFile']['name']) && $_POST['id'] == 0)
			fatal_lang_error('awards_error_no_file');

		$id = (int) $_POST['id'];

		// Clean the values
		$award_name = strtr($smcFunc['htmlspecialchars']($_POST['award_name'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => ''));
		$description = strtr($smcFunc['htmlspecialchars']($_POST['description'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => ''));
		$category = (int) $_REQUEST['id_category'];
		$time_added = time();

		// Now to insert the data for this new award.
		if ($id < 1)
		{
			$smcFunc['db_insert']('replace', '
				{db_prefix}awards',
				array('award_name' => 'string', 'description' => 'string', 'time_added' => 'int', 'id_category' => 'int'),
				array($award_name, $description, $time_added, $category),
				array('id_award')
			);

			// Get the id_award
			$id_award = $smcFunc['db_insert_id']('{db_prefix}awards', 'id_award');

			// Now upload the file
			AwardsUpload($id_award);
		}
		else
		{
			// Edit the award
			$editAward = $smcFunc['db_query']('', '
				UPDATE {db_prefix}awards
				SET
					award_name = {string:awardname},
					description = {string:gamename},
					id_category = {int:category}
				WHERE id_award = {int:id_award}',
				array(
					'awardname' => $_REQUEST['award_name'],
					'gamename' => $_POST['description'],
					'id_award' => $id,
					'category' => $category
				)
			);

			// Are we uploading a new image for this award?
			if (isset($_FILES['awardFile']) && $_FILES['awardFile']['error'] === 0 && $editAward === true)
			{
				// Lets make sure that we delete the file that we are supposed to and not something harmful
				$request = $smcFunc['db_query']('', '
					SELECT filename, minifile
					FROM {db_prefix}awards
					WHERE id_award = {int:id}',
					array(
						'id' => $id
					)
				);

				list ($filename, $minifile) = $smcFunc['db_fetch_row']($request);
				$smcFunc['db_free_result']($request);

				// Delete the file first.
				if (file_exists($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $filename))
					@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $filename);
				if (file_exists($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $minifile))
					@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $minifile);

				// Now add the new one.
				AwardsUpload($id_award);
			}
		}

		redirectexit('action=admin;area=awards;sa=modify;saved=1');
	}

	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}awards_categories
		ORDER BY category_name ASC',
		array()
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['categories'][] = array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
		);

	$smcFunc['db_free_result']($request);

	// Load the data for editing
	if (isset($_REQUEST['id']))
	{
		$id = (int) $_REQUEST['id'];

		// Check if awards is clean.
		if (empty($id) || $id <= 0)
			fatal_lang_error('awards_error_no_id');

		// Load single award info for editing.
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

		// Check if that award exists
		if (count($row['id_award']) != 1)
			fatal_lang_error('awards_error_no_award');

		$context['editing'] = true;
		$context['award'] = array(
			'id' => $row['id_award'],
			'award_name' => $row['award_name'],
			'description' => $row['description'],
			'category' => $row['id_category'],
			'time' => timeformat($row['time_added']),
			'filename' => $row['filename'],
			'minifile' => $row['minifile'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['filename'],
			'small' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
		);

		// Free results
		$smcFunc['db_free_result']($request);

		// Set the title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_edit_award'];
	}
	else
	{
		// Setup place holders.
		$context['editing'] = false;
		$context['award'] = array(
			'id' => 0,
			'award_name' => '',
			'description' => '',
			'category' => 1,
		);

		// Set the title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_manage_awards'];
	}

	$context['sub_template'] = 'modify';
}

function AwardsUpload($id_award)
{
	global $smcFunc, $modSettings, $boarddir;

	// Check if $_FILE was set.
	if (empty($_FILES['awardFile']) || !isset($id_award))
		fatal_lang_error('awards_error_no_file');

	// Lets try to CHMOD the awards dir.
	if (!is_writable($boarddir . '/' . $modSettings['awards_dir']))
		@chmod($boarddir . '/' . $modSettings['awards_dir'], 0755);

	// Define $award
	$award = $_FILES['awardFile'];

	// Check if file was uploaded.
	if ($award['error'] === 1 || $award['error'] === 2)
		fatal_lang_error('awards_error_upload_size');
	elseif ($award['error'] !== 0)
		fatal_lang_error('awards_error_upload_failed');

	// Check the extensions
	$goodExtensions = array('jpg', 'jpeg', 'gif', 'png');
	if (!in_array(strtolower(substr(strrchr($award['name'], '.'), 1)), $goodExtensions))
		fatal_lang_error('awards_error_wrong_extension');
	else
	{
		$newName = $boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $id_award . '.' . strtolower(substr(strrchr($award['name'], '.'), 1));
		$miniName = $boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $id_award . '-mini.' . strtolower(substr(strrchr($award['name'], '.'), 1));
	}

	// Now move the file to the right directory
	move_uploaded_file($award['tmp_name'], $newName);

	// Try to CHMOD the uploaded file
	@chmod($newName, 0755);

	if ($_FILES['awardFileMini']['error'] != 4)
	{
		// Define $award
		$award = $_FILES['awardFileMini'];

		// Check if file was uploaded.
		if ($award['error'] === 1 || $award['error'] === 2)
			fatal_lang_error('awards_error_upload_size');
		elseif ($award['error'] !== 0)
			fatal_lang_error('awards_error_upload_failed');

		// Check the extensions
		$goodExtensions = array('jpg', 'jpeg', 'gif', 'png');
		if (!in_array(strtolower(substr(strrchr($award['name'], '.'), 1)), $goodExtensions))
			fatal_lang_error('awards_error_wrong_extension');
		else
			$miniName = $boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $id_award . '-mini.' . strtolower(substr(strrchr($award['name'], '.'), 1));

		// Now move the file to the right directory
		move_uploaded_file($award['tmp_name'], $miniName);

		// Try to CHMOD the uploaded file
		@chmod($miniName, 0755);
	}
	else
		copy($newName, $miniName);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}awards
		SET
			filename = {string:file},
			minifile = {string:mini}
		WHERE id_award = {int:id}', array(
		'file' => basename($newName),
		'mini' => basename($miniName),
		'id' => $id_award
			)
	);
}

function AwardsDelete()
{
	global $smcFunc, $boarddir, $modSettings;

	// Check the session
	checkSession('get');

	$id = (int) $_GET['id'];

	// Select the file name to delete
	$request = $smcFunc['db_query']('', '
		SELECT filename, minifile
		FROM {db_prefix}awards
		WHERE id_award = {int:award}',
		array(
			'award' => $id
		)
	);
	list($filename, $minifile) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Now delete the award from the server
	@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $filename);
	@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $minifile);

	// Now delete the entry from the database.
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}awards
		WHERE id_award = {int:award}
		LIMIT 1',
		array(
			'award' => $id
		)
	);

	// Ok since this award doesn't exists any more lets remove it from the member
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}awards_members
		WHERE id_award = {int:award}',
		array(
			'award' => $id
		)
	);

	// Redirect the exit
	redirectexit('action=admin;area=awards');
}

function AwardsAssign()
{
	global $smcFunc, $context, $txt;

	// First step, select the member and awards
	if (!isset($_REQUEST['step']) || $_REQUEST['step'] == 1)
	{
		// Select the awards for the drop down.
		$request = $smcFunc['db_query']('', '
			SELECT id_award, award_name, filename
			FROM {db_prefix}awards
			ORDER BY award_name ASC', array()
		);

		$context['awards'] = array();

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['awards'][$row['id_award']] = array(
				'award_name' => $row['award_name'],
				'filename' => $row['filename'],
			);
		}

		$smcFunc['db_free_result']($request);

		// Set the current step.
		$context['step'] = 1;

		// Set the title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_select_badge'];
	}
	elseif (isset($_REQUEST['step']) && $_REQUEST['step'] == 2)
	{

		// Make sure that they picked an award and members to assign it to...
		foreach ($_POST['recipient_to'] as $recipient)
			if ($recipient != '{MEMBER_ID}')
				$members[] = (int) $recipient;

		if (empty($members) || empty($_POST['award']))
			fatal_lang_error('awards_error_no_members');

		// Set a valid date, award.
		$date_received = (int) $_POST['year'] . '-' . (int) $_POST['month'] . '-' . (int) $_POST['day'];
		$_POST['award'] = (int) $_POST['award'];

		$values = array();

		// Prepare the values.
		foreach ($members as $member)
			$values[] = array($_POST['award'], $member, $date_received);

		// Insert the data
		$smcFunc['db_insert']('ignore', '
			{db_prefix}awards_members',
			array('id_award' => 'int', 'id_member' => 'int', 'date_received' => 'string'),
			$values,
			array('id_member', 'id_award')
		);

		// Redirect to show the members with this award.
		redirectexit('action=admin;area=awards;sa=viewassigned;id=' . $_POST['award']);
	}

	$context['sub_template'] = 'assign';
}

function AwardsViewAssigned()
{
	global $smcFunc, $context, $scripturl, $modSettings, $txt;

	$id = (int) $_REQUEST['id'];

	// An award must be selected.
	if (empty($id) || $id <= 0)
		fatal_lang_error('awards_error_no_award');

	// Remove the badge from these members
	if (isset($_POST['unassign']))
	{
		checkSession('post');

		// Delete the rows from the database for the members selected.
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}awards_members
			WHERE id_award = {int:id}
				AND id_member IN (' . implode(', ', $_POST['member']) . ')',
			array(
				'id' => $id
			)
		);

		// Redirect to the badges
		redirectexit('action=admin;area=awards;sa=viewassigned;id=' . $id);
	}

	// Load the award info
	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}awards
		WHERE id_award = {int:id}
		LIMIT 1',
		array(
			'id' => $id
		)
	);

	// Check if ths award actually exists
	if ($smcFunc['db_num_rows']($request) < 1)
		fatal_lang_error('awards_error_no_award');

	// Fetch the award info just once
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['award'] = array(
			'id' => $row['id_award'],
			'name' => $row['award_name'],
			'description' => $row['description'],
			'filename' => $row['filename'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['filename'],
			'members' => array(),
		);

	$smcFunc['db_free_result']($request);

	// Now load the members' info
	$request = $smcFunc['db_query']('', '
		SELECT
			m.member_name, m.real_name, a.id_member, a.date_received
		FROM {db_prefix}awards_members AS a
			LEFT JOIN {db_prefix}members AS m ON (m.id_member = a.id_member)
		WHERE a.id_award = {int:id}',
		array(
			'id' => $id
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['award']['members'][] = array(
			'id' => $row['id_member'],
			'name' => $row['member_name'],
			'date' => $row['date_received'],
			'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
		);

	$smcFunc['db_free_result']($request);

	// Set the context values
	$context['page_title'] = $txt['awards_title'] . ' - ' . $context['award']['name'];
	$context['sub_template'] = 'view_assigned';
}

function AwardsSettings()
{
	global $smcFunc, $context, $txt, $boarddir, $smcFunc;

	$context['sub_template'] = 'settings';
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_settings'];

	// Save the settings
	if (isset($_POST['save_settings']))
	{
		// Check the session
		checkSession('post');

		// Strip any slashes from the awards dir
		$_POST['awards_dir'] = str_replace(array('\\', '/'), '', $_POST['awards_dir']);

		// Try to create a new dir if it doesn't exists.
		if (!is_dir($boarddir . '/' . $_POST['awards_dir']) && trim($_POST['awards_dir']) != '')
			if (!mkdir($boarddir . '/' . $_POST['awards_dir'], 0755))
				$context['awards_mkdir_fail'] = true;

		// Now save
		updateSettings(array(
			'awards_dir' => $smcFunc['htmlspecialchars']($_POST['awards_dir'], ENT_QUOTES),
			'awards_favorites' => isset($_POST['awards_favorites']) ? 1 : 0,
			'awards_in_post' => isset($_POST['awards_in_post']) ? (int) $_POST['awards_in_post'] : 4,
		));
	}
}

function EditCategory()
{
	global $smcFunc, $context, $txt;

	if (isset($_REQUEST['id']))
	{
		$id = (int) $_REQUEST['id'];

		// Needs to be an int!
		if (empty($id) || $id <= 0)
			fatal_lang_error('awards_error_no_id_category');

		// Load single award info for editing.
		$request = $smcFunc['db_query']('', '
			SELECT *
			FROM {db_prefix}awards_categories
			WHERE id_category = {int:id}
			LIMIT 1',
			array(
				'id' => $id
			)
		);
		$row = $smcFunc['db_fetch_assoc']($request);

		// Check if that award exists
		if (count($row['id_category']) != 1)
			fatal_lang_error('awards_error_no_category');

		$context['editing'] = true;
		$context['category'] = array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
		);

		$smcFunc['db_free_result']($request);

		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_edit_category'];
	}
	else
	{
		// Setup place holders.
		$context['editing'] = false;
		$context['category'] = array(
			'id' => 0,
			'name' => '',
		);

		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_manage_categories'];
	}

	// Check if they are saving the changes
	if (isset($_POST['category_save']))
	{
		checkSession('post');

		$name = trim(strtr($smcFunc['htmlspecialchars']($_REQUEST['category_name'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => '')));

		// Check if any of the values were left empty
		if (empty($name))
			fatal_lang_error('awards_error_empty_category_name');

		// Now to insert the data for this new award.
		if ($_POST['id_category'] == 0)
		{
			$smcFunc['db_insert']('replace', '
				{db_prefix}awards_categories',
				array('category_name' => 'string'),
				array($name),
				array('id_category')
			);
		}
		else
		{
			// Set $id_award
			$id_category = (int) $_POST['id_category'];

			// Edit the award
			$request = $smcFunc['db_query']('', '
				UPDATE {db_prefix}awards_categories
				SET category_name = {string:category}
				WHERE id_category = {int:id}',
				array(
					'category' => $name,
					'id' => $id_category
				)
			);
		}

		// Redirect back to the mod.
		redirectexit('action=admin;area=awards;sa=editcategory;saved=1');
	}

	$context['sub_template'] = 'edit_category';
}

function ListCategories()
{
	global $context, $scripturl, $txt, $smcFunc;

	// Define $categories
	$context['categories'] = array();

	// Count the number of items in the database for the index
	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}awards_categories'
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['categories'][$row['id_category']] = array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
			'edit' => $scripturl . '?action=admin;area=awards;sa=editcategory;id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
			'delete' => $scripturl . '?action=admin;area=awards;sa=deletecategory;id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
		);

	$smcFunc['db_free_result']($request);

	// Select the categories.
	$request = $smcFunc['db_query']('', '
		SELECT id_category, COUNT(*) AS num_awards
		FROM {db_prefix}awards
		GROUP BY id_category'
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['categories'][$row['id_category']]['awards'] = $row['num_awards'];

	$smcFunc['db_free_result']($request);

	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_list_categories'];
	$context['sub_template'] = 'list_categories';
}

function DeleteCategory()
{
	global $txt, $smcFunc;

	// Before doing anything check the session
	checkSession('get');

	$id = (int) $_REQUEST['id'];

	if ($id == 1)
		fatal_lang_error('awards_error_delete_main_category');

	// Will any awards go astray after we delete their category?
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

	// Redirect back to the mod.
	redirectexit('action=admin;area=awards;sa=categories');
}

function ViewCategory()
{
	global $context, $scripturl, $modSettings, $txt, $smcFunc;

	// Clean up!
	$id_category = (int) $_REQUEST['id'];
	$max_awards = 15;
	$context['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

	// Count the number of items in the database for create index
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}awards
		WHERE id_category = {int:id}',
		array(
			'id' => $id_category
		)
	);

	list($count_awards) = $smcFunc['db_fetch_row']($request);

	$smcFunc['db_free_result']($request);

	// And find the category name
	$request = $smcFunc['db_query']('', '
		SELECT category_name
		FROM {db_prefix}awards_categories
		WHERE id_category = {int:id}
		LIMIT 1',
		array(
			'id' => $id_category
		)
	);

	list($context['category']) = $smcFunc['db_fetch_row']($request);

	$smcFunc['db_free_result']($request);

	// Grab all qualifying awards
	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}awards
		WHERE id_category = {int:id}
		ORDER BY award_name DESC
		LIMIT {int:start}, {int:end}',
		array(
			'id' => $id_category,
			'start' => $context['start'],
			'end' => $max_awards,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['awards'][] = array(
			'id' => $row['id_award'],
			'name' => $row['award_name'],
			'description' => $row['description'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['filename'],
			'small' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
			'edit' => $scripturl . '?action=admin;area=awards;sa=modify;id=' . $row['id_award'] . ';' . $context['session_var'] . '=' . $context['session_id'],
		);

	$smcFunc['db_free_result']($request);

	$context['page_index'] = constructPageIndex($scripturl . '?action=admin;area=awards;sa=viewcategory', $context['start'], $count_awards, $max_awards);
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_viewing_category'];
	$context['sub_template'] = 'view_category';
}