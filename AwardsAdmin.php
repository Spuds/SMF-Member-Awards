<?php

/**
 * @name      Awards Modification
 * @license   Mozilla Public License version 2.0 http://mozilla.org/MPL/2.0/.
 *
 * @version   3.0 Alpha
 *
 * This file handles the admin side of Awards.
 *
 * Original Software by:           Juan "JayBachatero" Hernandez
 * Copyright (c) 2006-2009:        YodaOfDarkness (Fustrate)
 * Copyright (c) 2010:             Jason "JBlaze" Clemons
 *
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Main function to handle subActions
 * Loads arrays so menus work
 * Requires awards_admin permission
 * Uses Awards template and language
 *
 * @global type $context
 * @global type $txt
 * @global type $scripturl
 * @global type $sourcedir
 */
function Awards()
{
	// The entrance point for all 'Awards' actions.
	global $context, $txt;

	// subaction array ... function to call, permissions needed (array or permissions)
	$subActions = array(
		'main' => array('AwardsMain',array('manage_awards','assign_awards')),
		'assign' => array('AwardsAssign',array('manage_awards','assign_awards')),
		'assigngroup' => array('AwardsAssignMemberGroup',array('manage_awards')),
		'assignmass' => array('AwardsAssignMass',array('manage_awards')),
		'modify' => array('AwardsModify',array('manage_awards')),
		'delete' => array('AwardsDelete',array('manage_awards')),
		'edit' => array('AwardsModify',array('manage_awards')),
		'settings' => array('AwardsSettings',array('manage_awards')),
		'viewassigned' => array('AwardsViewAssigned',array('manage_awards','assign_awards')),
		'categories' => array('AwardsListCategories',array('manage_awards')),
		'editcategory' => array('AwardsEditCategory',array('manage_awards')),
		'deletecategory' => array('AwardsDeleteCategory',array('manage_awards')),
		'viewcategory' => array('AwardsViewCategory',array('manage_awards')),
		'requests' => array('AwardsRequests',array('manage_awards','assign_awards')),
	);

	// Default to sub action main if nothing else was provided
	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	// Language and template stuff, the usual.
	loadLanguage('AwardsManage');
	loadTemplate('AwardsManage');

	// Setup the admin tabs.
	$context['tabindex'] = 1;
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['awards'],
		'help' => $txt['awards_help'],
		'description' => $txt['awards_description_' . $_REQUEST['sa']],
	);

	// Our award types array, do not mess with these
	$context['award_types'] = array(
		array('id' => 1, 'name' => $txt['awards_manual'], 'desc' => $txt['awards_manual_desc']),
		array('id' => 2, 'name' => $txt['awards_post_count'], 'desc' => $txt['awards_post_count_desc'] ),
		array('id' => 3, 'name' => $txt['awards_top_posters'], 'desc' => $txt['awards_top_posters_desc'] ),
		array('id' => 4, 'name' => $txt['awards_topic_count'], 'desc' => $txt['awards_topic_count_desc'] ),
		array('id' => 5, 'name' => $txt['awards_top_topic_starters'], 'desc' => $txt['awards_top_topic_starters_desc'] ),
		array('id' => 6, 'name' => $txt['awards_time_online'], 'desc' => $txt['awards_time_online_desc'] ),
		array('id' => 7, 'name' => $txt['awards_member_since'], 'desc' => $txt['awards_member_since_desc'] ),
		array('id' => 8, 'name' => $txt['awards_karma_level'], 'desc' => $txt['awards_karma_level_desc']),
	);

	// And our placement array
	$context['award_placements'] = array(
		array('id' => 1, 'name' => $txt['awards_image_placement_below']),
		array('id' => 2, 'name' => $txt['awards_image_placement_above']),
		array('id' => 3, 'name' => $txt['awards_image_placement_sig']),
		array('id' => 4, 'name' => $txt['awards_image_placement_off'])
	);

	// And our format array
	$context['award_formats'] = array(
		array('id' => 1, 'name' => $txt['awards_format_full_frame']),
		array('id' => 2, 'name' => $txt['awards_format_heading']),
		array('id' => 3, 'name' => $txt['awards_format_no_frame']),
	);

	// Call the right function, if they have permission
	isAllowedTo($subActions[$_REQUEST['sa']][1]);
	$subActions[$_REQUEST['sa']][0]();
}

/**
 * Main page for the admin panel
 * Loads the awards and categories that have been added
 */
function AwardsMain()
{
	global $context, $scripturl, $modSettings, $txt, $smcFunc;

	// Count the number of items in the database for create index
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(id_award)
		FROM {db_prefix}awards'
	);

	list($countAwards) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Calculate the number of results to show per page.
	$maxAwards = 25;

	// Construct the page index
	$context['page_index'] = constructPageIndex($scripturl . '?action=admin;area=awards', $_REQUEST['start'], $countAwards, $maxAwards);
	$context['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

	// Select the awards and their categories.
	$request = $smcFunc['db_query']('', '
		SELECT
			a.id_category, a.id_award, a.award_name, a.description, a.time_added, a.filename, a.minifile, a.award_type, a.award_requestable, a.award_assignable,
			c.category_name
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
		// Group our categories
		if (!isset($context['categories'][$row['id_category']]['name']))
			$context['categories'][$row['id_category']] = array(
				'name' => $row['category_name'],
				'view' => $scripturl . '?action=admin;area=awards;sa=viewcategory;a_id=' . $row['id_category'],
				'edit' => $scripturl . '?action=admin;area=awards;sa=editcategory;a_id=' . $row['id_category'],
				'delete' => $scripturl . '?action=admin;area=awards;sa=deletecategory;a_id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
				'awards' => array(),
			);

		// load up the award details
		$context['categories'][$row['id_category']]['awards'][] = array(
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

	// Setup the title and template.
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_main'];
	$context['sub_template'] = 'main';
}

/**
 * Sets up the $context['award'] array for the add/edit page.
 * If it's a new award, inserts a new row if not it updated an existing one.
 * Uses AwardsUpload for files upload.
 * If a new image is uploaded for an existing mod, deletes the old images.
 */
function AwardsModify()
{
	global $smcFunc, $context, $scripturl, $txt, $modSettings, $boarddir, $sourcedir;

	// Load in our helper functions
	include_once($sourcedir . '/AwardsSubs.php');

	// Check if they are saving the changes
	if (isset($_POST['award_save']))
	{
		checkSession('post');

		// Check if any of the key values where left empty, and if so tell them
		if (empty($_POST['award_name']))
			fatal_lang_error('awards_error_empty_badge_name');
		if (empty($_FILES['awardFile']['name']) && $_POST['a_id'] == 0)
			fatal_lang_error('awards_error_no_file');

		// Clean and cast the values
		$id = (int) $_POST['a_id'];
		$award_name = strtr($smcFunc['htmlspecialchars']($_POST['award_name'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => ''));
		$description = strtr($smcFunc['htmlspecialchars']($_POST['description'], ENT_QUOTES), array("\r" => '', "\n" => '', "\t" => ''));
		$category = (int) $_POST['id_category'];
		$time_added = time();
		$award_type = empty($_POST['id_type']) ? 0 : $_POST['id_type'];
		$trigger = empty($_POST['awardTrigger']) ? 0 : (int) $_POST['awardTrigger'];
		$award_location = (int) $_POST['award_location'];
		$award_requestable = (isset($_POST['award_requestable']) ? 1 : 0);
		$award_assignable = (isset($_POST['award_assignable']) ? 1 : 0);

		// New award?
		if ($id < 1)
		{
			// add in a new award
			$smcFunc['db_insert']('replace',
				'{db_prefix}awards',
				array('award_name' => 'string', 'description' => 'string', 'time_added' => 'int', 'id_category' => 'int', 'award_type' => 'int', 'award_trigger' => 'int', 'award_location' => 'int', 'award_requestable' => 'int', 'award_assignable' =>'int'),
				array($award_name, $description, $time_added, $category, $award_type, $trigger, $award_location, $award_requestable, $award_assignable),
				array('id_award')
			);

			// Get the id_award for this new award
			$id = $smcFunc['db_insert_id']('{db_prefix}awards', 'id_award');

			// Now upload the file(s) associated with the award
			AwardsUpload($id);
		} else {
			// Not a new award so lets edit an existing one
			$trigger = empty($_POST['awardTrigger']) ? 0 : (int) $_POST['awardTrigger'];

			// Load the existing award info and see if they changed the trigger value
			AwardsLoadAward($id);
			if (($context['award']['type'] > 1) && ($context['award']['trigger'] != $trigger))
			{
				// Trigger value changed, this invalidates all (auto) awards earned with this award ID, so remove them
				// From the members to which it is assigned
				$smcFunc['db_query']('', '
					DELETE FROM {db_prefix}awards_members
					WHERE id_award = {int:award}',
					array(
						'award' => $id
					)
				);
			}

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
					'awardname' => $_POST['award_name'],
					'award_desc' => $_POST['description'],
					'id_award' => $id,
					'category' => $category,
					'awardtype' => $_POST['id_type'],
					'trigger' => $trigger,
					'awardlocation' => $_POST['award_location'],
					'awardrequestable' => (isset($_POST['award_requestable']) ? 1 : 0),
					'awardassignable' => (isset($_POST['award_assignable']) ? 1 : 0),
				)
			);

			// Are we uploading new images for this award?
			if ($editAward === true && ((isset($_FILES['awardFile']) && $_FILES['awardFile']['error'] == 0) || (isset($_FILES['awardFileMini']) && $_FILES['awardFileMini']['error'] == 0)))
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

				// Delete the old file(s) first.
				if ($_FILES['awardFile']['error'] == 0)
					if (file_exists($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $filename))
						@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $filename);
				if (file_exists($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $minifile))
					@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $minifile);

				// Now add the new one.
				AwardsUpload($id);
			}
		}

		// Awards were changed, flush the cache
		cache_put_data('awards:autoawards', null, 60);
		cache_put_data('awards:autoawardsid', null, 60);

		// back to the admin panel
		redirectexit('action=admin;area=awards;sa=modify;saved=1;a_id='.$id);
	}

	// Not saving so we must be adding or modifying
	$request = $smcFunc['db_query']('', '
		SELECT category_name, id_category
		FROM {db_prefix}awards_categories
		ORDER BY category_name ASC',
		array()
	);

	while($row = $smcFunc['db_fetch_assoc']($request))
		$context['categories'][] = array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
		);

	$smcFunc['db_free_result']($request);

	if (empty($context['settings_post_javascript']))
		$context['settings_post_javascript'] = '';

	// some javascript to disable the trigger text box if the first option e.g. regular is selected
	$context['settings_post_javascript'] .= '
				var award_type = document.getElementById(\'id_type\');
				mod_addEvent(award_type, \'change\', toggleAwardTrigger);
				toggleAwardTrigger();

				function mod_addEvent(control, ev, fn)
				{
					if (control.addEventListener)
					{
						control.addEventListener(ev, fn, false);
					}
					else if (control.attachEvent)
					{
						control.attachEvent(\'on\'+ev, fn);
					}
				}
				function toggleAwardTrigger()
				{
					var select_elem = document.getElementById(\'awardTrigger\');
					select_elem.disabled = award_type.value == 1;

					var desc = document.getElementById(\'trigger_desc_\' + award_type.value + \'\').firstChild.data;
					document.getElementById(\'awardTrigger_desc\').innerHTML = desc;
				}
	';

	// Load the data for editing/viewing an existing award
	if (isset($_REQUEST['a_id']))
	{
		// Check that awards id is clean.
		$id = (int) $_REQUEST['a_id'];
		if (empty($id) || $id <= 0)
			fatal_lang_error('awards_error_no_id');

		// Load single award info for editing.
		$request = $smcFunc['db_query']('', '
			SELECT
				id_award, award_name, description, id_category, time_added, filename, minifile, award_trigger, award_type, award_location, award_requestable, award_assignable
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
			'trigger' => $row['award_trigger'],
			'award_type' => $row['award_type'],
			'award_location' => $row['award_location'],
			'requestable' => $row['award_requestable'],
			'assignable' => $row['award_assignable'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['filename'],
			'small' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
		);

		// Free results
		$smcFunc['db_free_result']($request);

		// Set the page title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_edit_award'];
	} else {
		// Setup some default blank values as we are adding a new award
		$context['editing'] = false;
		$context['award'] = array(
			'id' => 0,
			'award_name' => '',
			'description' => '',
			'category' => 1,
			'trigger' => '',
			'award_type' => 1,
			'award_location' => 1,
			'assignable' => 0,
			'requestable' => 0
		);

		// Set the title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_manage_awards'];
	}

	$context['sub_template'] = 'modify';
}

/**
 * This handles the uploading of award images, regularalr and mini
 * Runs all files though AwardsValidateImage for security
 * To prevent duplicate file; filenames have the awardid prefixed to them
 *
 * @param type $id_award
 */
function AwardsUpload($id_award)
{
	global $smcFunc, $modSettings, $boarddir, $sourcedir;

	// Load in our helper functions
	include_once($sourcedir . '/AwardsSubs.php');

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
	// no mini just just the maxi for it
	elseif (($_FILES['awardFileMini']['error'] == 4) && ($_FILES['awardFile']['error'] != 4))
		copy($newName, $miniName);

	// update the database with this new image so its available.
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}awards
		SET ' . (!empty($newName) ? 'filename = {string:file},' : '') .
				(!empty($miniName) ? 'minifile = {string:mini}' : '') . '
		WHERE id_award = {int:id}',
		array(
			'file' => !empty($newName) ? basename($newName) : '',
			'mini' => !empty($miniName) ? basename($miniName) : '',
			'id' => $id_award
		)
	);
}

/**
 * This function handles deleting an award
 * If the image exists delete it then deletes the row from the database
 * Deletes any trace of the award from the awards_members table.
 *
 */
function AwardsDelete()
{
	global $smcFunc, $boarddir, $modSettings;

	// Check the session
	checkSession('get');

	$id = (int) $_GET['a_id'];

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

/**
 * This is where you assign awards to members.
 * Step 1
 *   - This is where you select the award that you want to assign
 *   - Uses AwardsBuildJavascriptArray to build the form so the correct image displays with the award
 * - Step 2
 *   - Select the members that you want to give this award to.
 *   - Enter the date that the award was given.
 */
function AwardsAssign()
{
	global $smcFunc, $context, $sourcedir, $txt, $user_info;

	// Load in our helper functions
	include_once($sourcedir . '/AwardsSubs.php');

	// First step, select the awards that can be assigned by this member
	if (!isset($_GET['step']) || $_GET['step'] == 1)
	{
		// Select all the non auto awards to populate the menu.
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

		$context['awards'] = array();

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['awards'][$row['id_award']] = array(
				'award_name' => $row['award_name'],
				'filename' => $row['filename'],
				'minifile' => $row['minifile'],
				'description' => $row['description'],
				'assignable' => $row['award_assignable']
			);
		}
		$smcFunc['db_free_result']($request);
		$context['awardsjavasciptarray'] = AwardsBuildJavascriptArray($context['awards'], 'awards');

		// Quick check for mischievous users ;)
		if (!allowedTo('manage_awards') && isset($_REQUEST['a_id']) && empty($context['awards'][$_REQUEST['a_id']]['assignable']))
			fatal_lang_error('awards_error_hack_error');

		// Set the current step.
		$context['step'] = 1;

		// Set the title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_select_badge'];
	}
	// Ah step '2', they selected some bum(s) to get an award :)
	elseif (isset($_GET['step']) && (int) $_GET['step'] == 2)
	{
		// Check session.
		checkSession('post');

		// Make sure that they picked an award and members to assign it to... but not themselfs, that would be wrong
		foreach($_POST['recipient_to'] as $recipient)
		{
			if ($recipient != $user_info['id'])
				$members[] = (int) $recipient;
		}

		if (empty($members) || empty($_POST['award']))
			fatal_lang_error('awards_error_no_members');

		// Set a valid date, award.
		$date_received = (int) $_POST['year'] . '-' . (int) $_POST['month'] . '-' . (int) $_POST['day'];
		$_POST['award'] = (int) $_POST['award'];

		// Prepare the values.
		$values = array();
		foreach ($members as $member)
			$values[] = array($_POST['award'], $member, $date_received, 1);

		// Insert the data
		$smcFunc['db_insert']('ignore',
			'{db_prefix}awards_members',
			array('id_award' => 'int', 'id_member' => 'int', 'date_received' => 'string', 'active' => 'int'),
			$values,
			array('id_member', 'id_award')
		);

		// Redirect to show the members with this award.
		redirectexit('action=admin;area=awards;sa=viewassigned;a_id=' . $_POST['award']);
	}

	$context['sub_template'] = 'assign';
}

function AwardsAssignMemberGroup()
{
	global $smcFunc, $context, $sourcedir, $txt;

	// Load in our helper functions
	include_once($sourcedir . '/AwardsSubs.php');

	// First step, select the memebrgroups and awards
	if (!isset($_REQUEST['step']) || (int) $_REQUEST['step'] == 1)
	{
		// Get all the member groups
		AwardsGetGroups();

		// Done with groups, now on to selecting the non auto awards to populate the menu.
		$context['awards'] = array();
		$request = $smcFunc['db_query']('', '
			SELECT id_award, award_name, filename, minifile, description
			FROM {db_prefix}awards
			WHERE award_type <= {int:type}
			ORDER BY award_name ASC',
			array(
				'type' => 1,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['awards'][$row['id_award']] = array(
				'award_name' => $row['award_name'],
				'filename' => $row['filename'],
				'minifile' => $row['minifile'],
				'description' => $row['description']
			);
		}
		$smcFunc['db_free_result']($request);
		$context['awardsjavasciptarray'] = AwardsBuildJavascriptArray($context['awards'], 'awards');

		// Set the template details
		$context['step'] = 1;
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_mem_group'];
	}
	// Ah step 'duo', they selected some ungrateful group(s) to get an award :P
	elseif (isset($_REQUEST['step']) && (int) $_REQUEST['step'] == 2)
	{
		// Make sure that they picked an award and group to assign it to...
		if (isset($_POST['who']))
			foreach($_POST['who'] as $group)
				$membergroups[] = (int) $group;

		if (empty($membergroups) || empty($_POST['award']))
			fatal_lang_error('awards_error_no_groups');

		// Set the award date
		$date_received = (int) $_POST['year'] . '-' . (int) $_POST['month'] . '-' . (int) $_POST['day'];
		$_POST['award'] = (int) $_POST['award'];

		// Prepare the values.
		$values = array();
		foreach ($membergroups as $group)
			$values[] = array('-1', $_POST['award'], $group, $date_received, 1);

		// Insert the data
		$smcFunc['db_insert']('ignore',
			'{db_prefix}awards_members',
			array('id_member' => int, 'id_award' => 'int', 'id_group' => 'int', 'date_received' => 'string', 'active' => 'int'),
			$values,
			array('id_member', 'id_award')
		);

		// Redirect to show the members with this award.
		redirectexit('action=admin;area=awards;sa=viewassigned;a_id=' . $_POST['award']);
	}

	$context['sub_template'] = 'assign_group';
}

function AwardsAssignMass()
{
	global $smcFunc, $context, $sourcedir, $txt;

	// Load in our helper functions
	include_once($sourcedir . '/AwardsSubs.php');

	// First step, select the memebrgroups and awards
	if (!isset($_REQUEST['step']) || (int) $_REQUEST['step'] < 3)
	{
		// Get all the member groups
		AwardsGetGroups();

		// Done with groups, now on to selecting the non auto awards to populate the menu.
		$context['awards'] = array();
		$request = $smcFunc['db_query']('', '
			SELECT id_award, award_name, filename, minifile, description
			FROM {db_prefix}awards
			WHERE award_type <= {int:type}
			ORDER BY award_name ASC',
			array(
				'type' => 1,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['awards'][$row['id_award']] = array(
				'award_name' => $row['award_name'],
				'filename' => $row['filename'],
				'minifile' => $row['minifile'],
				'description' => $row['description']
			);
		}
		$smcFunc['db_free_result']($request);
		$context['awardsjavasciptarray'] = AwardsBuildJavascriptArray($context['awards'], 'awards');

		// Set the template details
		$context['step'] = 1;
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_select_group'];

		// Good old number 2 ... they have selected some groups, we need to load the members for them
		if (isset($_REQUEST['step']) && (int) $_REQUEST['step'] == 2)
		{
			// Make sure that they checked some groups so we can load them
			if (!empty($_POST['who']))
			{
				AwardsGetGroupMembers();

				// Set the template details
				$context['step'] = 3;
				$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_select_member'];
			} else {
				// they made a mistake, back to step 1 they go!
				$context['step'] = 1;
				$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_select_group'];
			}
		}
	}
	// Ah step 3, they selected mass quantities of members to get a special award
	elseif (isset($_REQUEST['step']) && (int) $_REQUEST['step'] == 3)
	{
		checksession();

		// no members no awards
		if (empty($_POST['member']) || empty($_POST['award']))
			fatal_lang_error('awards_error_no_members');

		// Make sure that they picked an award and group to assign it to...
		foreach($_POST['member'] as $member)
			$members[] = (int) $member;

		// Set a valid date, award.
		$date_received = (int) $_POST['year'] . '-' . (int) $_POST['month'] . '-' . (int) $_POST['day'];
		$_POST['award'] = (int) $_POST['award'];

		// Prepare the values.
		$values = array();
		foreach ($members as $member)
			$values[] = array((int) $_POST['award'], $member, $date_received, 1);

		// Insert the data
		$smcFunc['db_insert']('ignore',
			'{db_prefix}awards_members',
			array('id_award' => 'int', 'id_member' => 'int', 'date_received' => 'string', 'active' => 'int'),
			$values,
			array('id_member', 'id_award')
		);

		// Redirect to show the members with this award.
		redirectexit('action=admin;area=awards;sa=viewassigned;a_id=' . $_POST['award']);
	}

	$context['sub_template'] = 'assign_mass';
}

function AwardsGetGroupMembers()
{
	global $smcFunc, $context;

	$context['members'] = array();
	$postsave = $_POST['who'];

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
			$context['members'][$row['id_member']] = $row['real_name'];

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
			$context['members'][$row['id_member']] = $row['real_name'];

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
			$context['members'][$row['id_member']] = $row['real_name'];

		$smcFunc['db_free_result']($request);
	}
	$_POST['who'] = $postsave;
}

/**
 * This is where you see the members that have been assigned a certain award.
 * Can unassign the award for selected members.
 *
 */
function AwardsViewAssigned()
{
	global $smcFunc, $context, $scripturl, $txt, $sourcedir;

	// An award must be selected.
	$id = (int) $_REQUEST['a_id'];
	if (empty($id) || $id <= 0)
		fatal_lang_error('awards_error_no_award');

	// Removing the award from these members?
	if (isset($_POST['unassign']))
	{
		checkSession('post');

		$members = array();
		// Get all the member id's ....
		foreach ($_POST['member'] as $removeID => $dummy)
			$members[] = (int) $removeID;

		// Delete the rows from the database for the members selected.
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}awards_members
			WHERE id_award = {int:id}
				AND id_member IN (' . implode(', ', $members) . ')',
			array(
				'id' => $id
			)
		);

		// Redirect to the awards
		redirectexit('action=admin;area=awards;sa=viewassigned;a_id=' . $id);
	}

	// Load in our helper functions
	include_once($sourcedir . '/AwardsSubs.php');

	// Load the awards info for this award
	AwardsLoadAward($id);

	// build the listoption array to display the data
	$listOptions = array(
		'id' => 'view_assigned',
		'title' => $txt['awards_showmembers'] . ': ' . $context['award']['name'],
		'items_per_page' => 40,
		'no_items_label' => $txt['awards_no_assigned_members2'],
		'base_href' => $scripturl . '?action=admin;area=awards;sa=viewassigned;a_id='.$id,
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
			'members' => array(
				'header' => array(
					'value' => $txt['members'],
				),
				'data' => array(
					'db' => 'name',
				),
				'sort' => array(
					'default' => 'm.member_name ',
					'reverse' => 'm.member_name DESC',
				),
			),
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
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" id="checkAllMembers" onclick="invertAll(this, this.form);" class="input_check" />',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="member[%1$d]" id="member%1$d" class="input_check" />',
						'params' => array(
							'id' => false,
						),
					),
					'style' => 'text-align: center',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=awards;sa=viewassigned;a_id=' . $context['award']['id'],
			'include_sort' => true,
			'include_start' => true,
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<input type="submit" name="unassign" class="button_submit" value="' . $txt['awards_unassign'] . '" accesskey="s" onclick="return confirm(\'' . $txt['awards_removemember_yn'] . '\');" />',
				'style' => 'text-align: right;',
			),
		),
	);

	// Set the context values
	$context['page_title'] = $txt['awards_title'] . ' - ' . $context['award']['name'];
	$context['sub_template'] = 'view_assigned';

	// Create the list.
	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);
}

/**
 * This is where you handle the settings for the mod
 * awardsDir is the directly in which the badges are saved.
 */
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

		// Now save these in the modSettings array
		updateSettings(
			array(
				'awards_dir' => $smcFunc['htmlspecialchars']($_POST['awards_dir'], ENT_QUOTES),
				'awards_favorites' => isset($_POST['awards_favorites']) ? 1 : 0,
				'awards_in_post' => isset($_POST['awards_in_post']) ? (int) $_POST['awards_in_post'] : 5,
				'awards_aboveavatar_format' => isset($_POST['awards_aboveavatar_format']) ? (int) $_POST['awards_aboveavatar_format'] : 0,
				'awards_aboveavatar_title' => isset($_POST['awards_aboveavatar_title']) ? trim($smcFunc['htmlspecialchars']($_POST['awards_aboveavatar_title'], ENT_QUOTES)) : $txt['awards_title'],
				'awards_belowavatar_format' => isset($_POST['awards_belowavatar_format']) ? (int) $_POST['awards_belowavatar_format'] : 0,
				'awards_belowavatar_title' => isset($_POST['awards_belowavatar_title']) ? trim($smcFunc['htmlspecialchars']($_POST['awards_belowavatar_title'], ENT_QUOTES)) : $txt['awards_title'],
				'awards_signature_format' => isset($_POST['awards_signature_format']) ? (int) $_POST['awards_signature_format'] : 0,
				'awards_signature_title' => isset($_POST['awards_signature_title']) ? trim($smcFunc['htmlspecialchars']($_POST['awards_signature_title'], ENT_QUOTES)) : $txt['awards_title'],
			)
		);
	}
}

/**
 * Edits existing categories
 */
function AwardsEditCategory()
{
	global $smcFunc, $context, $txt;

	if (isset($_REQUEST['a_id']))
	{
		$id = (int) $_REQUEST['a_id'];

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
	} else {
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
			$smcFunc['db_insert']('replace',
				'{db_prefix}awards_categories',
				array('category_name' => 'string'),
				array($name),
				array('id_category')
			);
		} else {
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

/**
 * List all the categories
 * provides option to edit or delete them
 */
function AwardsListCategories()
{
	global $context, $scripturl, $smcFunc, $txt;

	// Define $categories
	$context['categories'] = array();

	// Load all the categories.
	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}awards_categories'
	);

	while($row = $smcFunc['db_fetch_assoc']($request))
		$context['categories'][$row['id_category']] = array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
			'view' => $scripturl . '?action=admin;area=awards;sa=viewcategory;a_id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
			'edit' => $scripturl . '?action=admin;area=awards;sa=editcategory;a_id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
			'delete' => $scripturl . '?action=admin;area=awards;sa=deletecategory;a_id=' . $row['id_category'] . ';' . $context['session_var'] . '=' . $context['session_id'],
		);

	$smcFunc['db_free_result']($request);

	// Count the number of awards in each category
	$request = $smcFunc['db_query']('', '
		SELECT id_category, COUNT(*) AS num_awards
		FROM {db_prefix}awards
		GROUP BY id_category'
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['categories'][$row['id_category']]['awards'] = $row['num_awards'];

	$smcFunc['db_free_result']($request);

	// Set the context values
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_list_categories'];
	$context['sub_template'] = 'list_categories';

	return ;
}

/**
 * List all the categories
 * provides option to edit or delete them
 */
function AwardsDeleteCategory()
{
	global $smcFunc;

	// Before doing anything check the session
	checkSession('get');

	$id = (int) $_REQUEST['a_id'];

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

/**
 * Shows all the awards within a category
 */
function AwardsViewCategory()
{
	global $context, $scripturl, $modSettings, $txt, $smcFunc;

	// Clean up!
	$id_category = (int) $_REQUEST['a_id'];
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

	while($row = $smcFunc['db_fetch_assoc']($request))
		$context['awards'][] = array(
			'id' => $row['id_award'],
			'award_name' => $row['award_name'],
			'description' => $row['description'],
			'img' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['filename'],
			'small' => dirname($scripturl) . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $row['minifile'],
			'edit' => $scripturl . '?action=admin;area=awards;sa=modify;a_id=' . $row['id_award'] . ';' . $context['session_var'] . '=' . $context['session_id'],
		);

	$smcFunc['db_free_result']($request);

	$context['page_index'] = constructPageIndex($scripturl . '?action=admin;area=awards;sa=viewcategory', $context['start'], $count_awards, $max_awards);
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_viewing_category'];
	$context['sub_template'] = 'view_category';
}

/**
 * Shows all the awards that members have requested
 * Groups the requests by category
 * Call request_award template
 */
function AwardsRequests()
{
	global $context, $txt, $scripturl, $modSettings, $settings, $smcFunc;

	// Select all the member requested awards awards to populate the menu.
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

	// Get just the members awaiting approval so we can reject them >:D
	$request = $smcFunc['db_query']('', '
		SELECT mem.real_name, mem.id_member,
			am.id_award, am.comments
		FROM {db_prefix}awards_members AS am
			LEFT JOIN {db_prefix}members As mem ON (mem.id_member = am.id_member)
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

	// Place them in $context for the template
	$context['awards'] = $awards;
	$context['sub_template'] = 'request_award';
	$context['page_title'] =  $txt['awards_requests'];

	// denied or approved ... the choice is yours
	if (isset($_POST['reject_selected']) || isset($_POST['approve_selected']))
		AwardsRequests2();
}

/**
 * Does the actual approval or deny of the request
 * if approved flips the active bit
 * if rejected removes the request
 */
function AwardsRequests2()
{
	global $smcFunc, $modSettings;

	// Check session.
	checkSession('post');

	// Start the counter.
	$requests_count = 0;

	// Lets sanitize these up.
	$awards = array();
	foreach ($_POST['requests'] as $id_award => $members)
	{
		foreach ($members as $member => $id_member)
		{
			$requests_count++;
			$awards[$id_award][] = (int) $id_member;
		}
	}

	// Accept the request
	if (isset($_POST['approve_selected']))
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
	// or the more fun, deny em!
	elseif (isset($_POST['reject_selected']))
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

	// We need to update the requests amount.
	updateSettings(array(
		'awards_request' => (($modSettings['awards_request'] - $requests_count) <= 0 ? 0 : $modSettings['awards_request'] - $requests_count),
	));

	// Redirect.
	redirectexit('action=admin;area=awards;sa=requests');
}