<?php

/**
 * @name      Awards Modification
 * @license   Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version   3.0
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
 */
function Awards()
{
	// The entrance point for all 'Awards' actions.
	global $context, $txt, $sourcedir;

	// We will need this
	require_once($sourcedir . '/AwardsSubs.php');

	// subaction array ... function to call, permissions needed (array or permissions)
	$subActions = array(
		'main' => array('AwardsMain', array('manage_awards','assign_awards')),
		'assign' => array('AwardsAssign', array('manage_awards','assign_awards')),
		'assigngroup' => array('AwardsAssignMemberGroup', array('manage_awards')),
		'assignmass' => array('AwardsAssignMass', array('manage_awards')),
		'modify' => array('AwardsModify', array('manage_awards')),
		'delete' => array('AwardsDelete', array('manage_awards')),
		'edit' => array('AwardsModify', array('manage_awards')),
		'settings' => array('AwardsSettings', array('manage_awards')),
		'viewassigned' => array('AwardsViewAssigned', array('manage_awards','assign_awards')),
		'categories' => array('AwardsListCategories', array('manage_awards')),
		'editcategory' => array('AwardsEditCategory', array('manage_awards')),
		'deletecategory' => array('AwardsRemoveCategory', array('manage_awards')),
		'viewcategory' => array('AwardsViewCategory', array('manage_awards')),
		'requests' => array('AwardsRequests', array('manage_awards','assign_awards')),
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

	// Our placement array
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
	global $context, $scripturl, $txt, $sourcedir;

	require_once($sourcedir . '/Subs-List.php');

	// Load all the categories.
	$categories = AwardsLoadCategories();

	// Now build an award list for each category
	$count = 0;
	foreach ($categories as $name => $cat)
	{
		$listOptions = array(
			'id' => 'awards_cat_list_' . $count,
			'title' => $name,
			'items_per_page' => 25,
			'default_sort_col' => 'award_name',
			'no_items_label' => $txt['awards_error_no_badges'],
			'base_href' => $scripturl . '?action=admin;area=awards' . (isset($_REQUEST['sort' . $count]) ? ';sort' . $count . '=' . urlencode($_REQUEST['sort' . $count]) : ''),
			'request_vars' => array(
				'sort' => 'sort' . $count,
				'desc' => 'desc' . $count,
			),
			'get_items' => array(
				'file' => 'AwardsSubs.php',
				'function' => 'AwardsLoadCategoryAwards',
				'params' => array(
					$cat,
				),
			),
			'get_count' => array(
				'file' => 'AwardsSubs.php',
				'function' => 'AwardsCountCategoryAwards',
				'params' => array(
					$cat,
				),
			),
			'columns' => array(
				'img' => array(
					'header' => array(
						'value' => $txt['awards_image'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<img src="%1$s" alt="%2$s" />',
							'params' => array(
								'img' => false,
								'award_name' => false,
							),
						),
						'style' => "width: 15%",
						'class' => "centertext",
					),
				),
				'small' => array(
					'header' => array(
						'value' => $txt['awards_mini'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<img src="%1$s" alt="%2$s" />',
							'params' => array(
								'small' => false,
								'award_name' => false,
							),
						),
						'style' => "width: 15%",
						'class' => "centertext",
					),
				),
				'award_name' => array(
					'header' => array(
						'value' => $txt['awards_name'],
					),
					'data' => array(
						'db' => 'award_name',
						'style' => "width: 25%",
					),
					'sort' => array(
						'default' => 'award_name',
						'reverse' => 'award_name DESC',
					),
				),
                'description' => array(
                    'header' => array(
                        'value' => $txt['awards_desc'],
                    ),
                    'data' => array(
                        'function' => create_function('$row', '
							 return parse_bbc($row[\'description\']);'
                        ),
                        'style' => "width: 35%",
                    ),
                    'sort' => array(
                        'default' => 'description',
                        'reverse' => 'description DESC',
                    ),
                ),
				'action' => array(
					'header' => array(
						'value' => $txt['awards_actions'],
					),
					'data' => array(
						'function' => create_function('$row', '
							global $txt, $settings;

							$result = ((allowedTo(\'manage_awards\')) ? \'<a href="\' . $row[\'edit\'] . \'" title="\' . $txt[\'awards_button_edit\'] . \'"><img src="\' . $settings[\'images_url\'] . \'/awards/modify.png" alt="" /></a>
										<a href="\'  . $row[\'delete\'] . \'" onclick="return confirm(\\\'\' . $txt[\'awards_confirm_delete_award\'] . \'\\\');" title="\' . $txt[\'awards_button_delete\'] . \'"><img src="\' . $settings[\'images_url\'] . \'/awards/delete.png" alt="" /></a>
										<br />\' : \'\');

							if (($row[\'award_type\'] <= 1) && (allowedTo(\'manage_awards\') || (allowedTo(\'assign_awards\') && !empty($row[\'assignable\']))))
								$result .= \'
										<a href="\' . $row[\'assign\'] . \'" title="\' . $txt[\'awards_button_assign\'] . \'"><img src="\' . $settings[\'images_url\'] . \'/awards/assign.png" alt="" /></a>\';

							$result .= \'
										<a href="\' . $row[\'view_assigned\'] . \'" title="\' . $txt[\'awards_button_members\'] . \'"><img src="\' . $settings[\'images_url\'] . \'/awards/user.png" alt="" /></a>\';

							return $result;'
						),
						'class' => "centertext",
						'style' => "width: 10%",
					),
				),
			),
		);

		createList($listOptions);
		$count++;
	}

	// Set up for the template display
	$context['count'] = $count;
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_main'];
}

/**
 * Sets up the $context['award'] array for the add/edit page.
 * If it's a new award, inserts a new row if not it updates an existing one.
 * Uses AwardsUpload for files upload.
 * If a new image is uploaded for an existing award, deletes the old images.
 */
function AwardsModify()
{
	global $smcFunc, $context, $txt, $modSettings, $boarddir;

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
			// Add in a new award and get the id
			$id = AwardsAddAward($award_name, $description, $time_added, $category, $award_type, $trigger, $award_location, $award_requestable, $award_assignable);

			// Now upload the file(s) associated with the award
			AwardsUpload($id);
		}
		else
		{
			// Not a new award so lets edit an existing one
			$trigger = empty($_POST['awardTrigger']) ? 0 : (int) $_POST['awardTrigger'];

			// Load the existing award info and see if they changed the trigger value
			$context['award'] = AwardsLoadAward($id);

			// Trigger value changed on an auto award, this invalidates all (auto) awards earned with this award ID
			if (($context['award']['type'] > 1) && ($context['award']['trigger'] != $trigger))
				AwardsRemoveMembers($id);

			// Make the updates to the award
			$editAward = AwardsUpdateAward($id, $award_name, $description, $category, $award_type, $trigger, $award_location, $award_requestable, $award_assignable);

			// Are we uploading new images for this award?
			if ($editAward == true && ((isset($_FILES['awardFile']) && $_FILES['awardFile']['error'] == 0) || (isset($_FILES['awardFileMini']) && $_FILES['awardFileMini']['error'] == 0)))
			{
				// Lets make sure that we delete the file that we are supposed to and not something harmful
				list ($filename, $minifile) = AwardLoadFiles($id);

				// Delete the old file(s) first.
				if ($_FILES['awardFile']['error'] == 0)
				{
					if (file_exists($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $filename))
						@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $filename);
				}

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
	$context['categories'] = AwardsLoadCategories('ASC', true);

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

		// Load a single award in for for editing.
		$context['award'] = AwardsLoadAward($id);
		$context['editing'] = true;

		// Set the page title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_edit_award'];
	}
	else
	{
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
 * This function handles deleting an award
 * If the image exists delete it then deletes the row from the database
 * Deletes any trace of the award from the awards_members table.
 */
function AwardsDelete()
{
	global $boarddir, $modSettings;

	// Check the session
	checkSession('get');

	$id = (int) $_GET['a_id'];

	// Select the file name to delete
	list ($filename, $minifile) = AwardLoadFiles($id);

	// Now delete the award from the server
	@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $filename);
	@unlink($boarddir . '/' . (empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'] . '/') . $minifile);

	// Now delete the entry from the database and remove it from the members
	AwardsDeleteAward($id);
	AwardsRemoveMembers($id);

	// Redirect the exit
	redirectexit('action=admin;area=awards');
}

/**
 * This is where you assign awards to members.
 * Step 1
 *   - Select the award that you want to assign
 *   - Uses AwardsBuildJavascriptObject to build the form so the correct image displays with the award
 *
 * - Step 2
 *   - Select the members that you want to give this award to.
 *   - Enter the date that the award was given.
 */
function AwardsAssign()
{
	global $context, $txt, $user_info;

	// First step, select the awards that can be assigned by this member
	if (!isset($_GET['step']) || $_GET['step'] == 1)
	{
		// Select all the non auto awards to populate the menu.
		$context['awards'] = AwardsLoadAssignableAwards();
		$context['awardsjavasciptarray'] = AwardsBuildJavascriptObject($context['awards'], 'awards');

		// Quick check for mischievous users ;)
		if (!allowedTo('manage_awards') && isset($_REQUEST['a_id']) && empty($context['awards'][$_REQUEST['a_id']]['assignable']))
			fatal_lang_error('awards_error_hack_error');

		// Set the current step.
		$context['step'] = 1;

		// Set the title
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_select_badge'];
	}
	// Ah step '2', they selected some bum(s) to get an award :)
	elseif (isset($_GET['step']) && $_GET['step'] == 2)
	{
		// Check session.
		checkSession('post');

		// Make sure that they picked an award and members to assign it to... but not themselfs, that would be wrong
		foreach($_POST['recipient_to'] as $recipient)
		{
			if ($recipient != $user_info['id'] || $user_info['is_admin'])
				$members[] = (int) $recipient;
		}

		if (empty($members) || empty($_POST['award']))
			fatal_lang_error('awards_error_no_members');

		// Set a valid date, award.
		$date_received = (int) $_POST['year'] . '-' . (int) $_POST['month'] . '-' . (int) $_POST['day'];
		$_POST['award'] = (int) $_POST['award'];

		// Prepare the values and add them
		$values = array();
		foreach ($members as $member)
			$values[] = array($_POST['award'], $member, $date_received, 1);

		AwardsAddMembers($values);

		// Redirect to show the members with this award.
		redirectexit('action=admin;area=awards;sa=viewassigned;a_id=' . $_POST['award']);
	}

	$context['sub_template'] = 'assign';
}

/**
 * This is where you assign awards to member groups.
 * Step 1
 *   - Select the award that you want to assign
 *   - Uses AwardsBuildJavascriptObject to build the form so the correct image displays with the award
 *
 * - Step 2
 *   - Select the members that you want to give this award to.
 *   - Enter the date that the award was given.
 */
function AwardsAssignMemberGroup()
{
	global $context, $txt;

	// First step, select the memebrgroups and awards
	if (!isset($_REQUEST['step']) || (int) $_REQUEST['step'] == 1)
	{
		// Load all the member groups
		$context['groups'] = AwardsLoadGroups();

		// Done with groups, now on to selecting the non auto awards to populate the menu.
		$context['awards'] = AwardsLoadAssignableAwards();
		$context['awardsjavasciptarray'] = AwardsBuildJavascriptObject($context['awards'], 'awards');

		// Set the template details
		$context['step'] = 1;
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_mem_group'];
	}
	// Ah step 'duo', they selected some ungrateful group(s) to get an award :P
	elseif (isset($_REQUEST['step']) && (int) $_REQUEST['step'] == 2)
	{
		// Make sure that they picked an award and group to assign it to...
		if (isset($_POST['who']))
		{
			foreach($_POST['who'] as $group)
				$membergroups[] = (int) $group;
		}

		if (empty($membergroups) || empty($_POST['award']))
			fatal_lang_error('awards_error_no_groups');

		// Set the award date
		$date_received = (int) $_POST['year'] . '-' . (int) $_POST['month'] . '-' . (int) $_POST['day'];
		$award = (int) $_POST['award'];

		// Prepare the values.
		$values = array();
		foreach ($membergroups as $group)
			$values[] = array($award, -$group, $group, $date_received, 1);

		// Add the awards, group style
		AwardsAddMembers($values, true);

		// Redirect to show the members with this award.
		redirectexit('action=admin;area=awards;sa=viewassigned;a_id=' . $_POST['award']);
	}

	$context['sub_template'] = 'assign_group';
}

function AwardsAssignMass()
{
	global $context, $txt;

	// First step, select the membergroups and awards
	if (!isset($_REQUEST['step']) || (int) $_REQUEST['step'] < 3)
	{
		// Load all the member groups
		$context['groups'] = AwardsLoadGroups();

		// Done with groups, now on to selecting the non auto awards to populate the menu.
		$context['awards'] = AwardsLoadAssignableAwards();
		$context['awardsjavasciptarray'] = AwardsBuildJavascriptObject($context['awards'], 'awards');

		// Set the template details
		$context['step'] = 1;
		$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_select_group'];

		// Something to check
		$_SESSION['allowed_groups'] = array_keys($context['groups']);

		// Good old number 2 ... they have selected some groups, we need to load the members for them
		if (isset($_REQUEST['step']) && (int) $_REQUEST['step'] == 2)
		{
			// Make sure that they checked some groups so we can load them
			if (!empty($_POST['who']))
			{
				$context['members'] = AwardsLoadGroupMembers();

				// Set the template details
				$context['step'] = 3;
				$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_select_member'];
			}
			else
			{
				// they made a mistake, back to step 1 they go!
				$context['step'] = 1;
				$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_select_group'];
			}
		}
	}
	// Ah step 3, they selected mass quantities of members to get a special award
	elseif (isset($_REQUEST['step']) && (int) $_REQUEST['step'] == 3)
	{
		checkSession();

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

		AwardsAddMembers($values);

		// Redirect to show the members with this award.
		redirectexit('action=admin;area=awards;sa=viewassigned;a_id=' . $_POST['award']);
	}

	$context['sub_template'] = 'assign_mass';
}

/**
 * This is where you see the members that have been assigned a certain award.
 * Can unassign the award for selected members.
 */
function AwardsViewAssigned()
{
	global $sourcedir, $context, $scripturl, $txt, $modSettings;

	// An award must be selected.
	$id = (int) $_REQUEST['a_id'];
	if (empty($id) || $id <= 0)
		fatal_lang_error('awards_error_no_award');

	// Removing the award from some members?
	if (isset($_POST['unassign']))
	{
		checkSession('post');

		// Get all the id's selected in the form
		$ids = array();
		foreach ($_POST['member'] as $remove_id => $dummy)
			$ids[] = (int) $remove_id;

		// Delete the rows from the database for the ids selected.
		AwardsRemoveMembers($id, $ids);

		// Redirect to the awards
		redirectexit('action=admin;area=awards;sa=viewassigned;a_id=' . $id);
	}

	// Load the awards info for this award
	$context['award'] = AwardsLoadAward($id);

	// build the listoption array to display the data
	$listOptions = array(
		'id' => 'view_assigned',
		'title' => $txt['awards_showmembers'] . ': ' . $context['award']['award_name'],
		'items_per_page' => $modSettings['defaultMaxMessages'],
		'no_items_label' => $txt['awards_no_assigned_members2'],
		'base_href' => $scripturl . '?action=admin;area=awards;sa=viewassigned;a_id=' . $id,
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
			'members' => array(
				'header' => array(
					'value' => $txt['members'],
				),
				'data' => array(
					'db' => 'member_name',
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
					'db' => 'real_name',
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
					'db' => 'date_received',
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
							'uniq_id' => false,
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
				'value' => '<input type="submit" name="unassign" class="button_submit" value="' . $txt['awards_unassign'] . '" accesskey="u" onclick="return confirm(\'' . $txt['awards_removemember_yn'] . '\');" />',
				'style' => 'text-align: right;',
			),
		),
	);

	// Set the context values
	$context['page_title'] = $txt['awards_title'] . ' - ' . $context['award']['award_name'];
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
	global $smcFunc, $context, $txt, $boarddir;

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
		$context['category'] = AwardsLoadCategory($id);

		$context['editing'] = true;
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

		// Add a new or Update and existing
		if ($_POST['id_category'] == 0)
			AwardsSaveCategory($name);
		else
		{
			$id_category = (int) $_POST['id_category'];
			AwardsSaveCategory($name, $id_category);
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
	global $context, $txt;

	// Define $categories
	$context['categories'] = AwardsLoadAllCategories();

	// Count the number of awards in each category
	$counts = AwardsInCategories();

	foreach ($counts as $id => $count )
		$context['categories'][$id]['awards'] = $count['awards'];

	// Set the context values
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_list_categories'];
	$context['sub_template'] = 'list_categories';

	return;
}

/**
 * List all the categories
 * provides option to edit or delete them
 */
function AwardsRemoveCategory()
{
	// Before doing anything check the session
	checkSession('get');

	$id = (int) $_REQUEST['a_id'];

	if ($id == 1)
		fatal_lang_error('awards_error_delete_main_category');

	AwardsDeleteCategory($id);

	// Redirect back to the mod.
	redirectexit('action=admin;area=awards;sa=categories');
}

/**
 * Shows all the awards within a category
 */
function AwardsViewCategory()
{
	global $context, $scripturl, $txt;

	// Clean up!
	$id_category = (int) $_REQUEST['a_id'];
	$max_awards = 15;
	$start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;

	// Count the number of items in the database for create index
	$count_awards = AwardsInCategories($id_category);

	// And find the category name
	$category = AwardsLoadCategory($id_category);
	$context['category'] = $category['name'];

	// Grab all qualifying awards
	$context['awards'] = AwardsLoadCategoryAwards($start, $max_awards, 'award_name DESC', $id_category);

	$context['page_index'] = constructPageIndex($scripturl . '?action=admin;area=awards;sa=viewcategory', $context['start'], $count_awards, $max_awards);
	$context['page_title'] = $txt['awards_title'] . ' - ' . $txt['awards_viewing_category'];
	$context['sub_template'] = 'view_category';
}

/**
 * Shows all the awards that members have requested
 * Groups the requests by category
 * Calls request_award template
 */
function AwardsRequests()
{
	global $context, $txt;

	// Get just the members awaiting approval so we can reject them >:D
	$awards = AwardsLoadRequestedAwards();

	// Place them in context for the template
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
	global $modSettings;

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
		AwardsApproveDenyRequests($awards, true);
	// or the more fun, deny em!
	elseif (isset($_POST['reject_selected']))
		AwardsApproveDenyRequests($awards, false);

	// We need to update the requests amount.
	updateSettings(array(
		'awards_request' => (($modSettings['awards_request'] - $requests_count) <= 0 ? 0 : $modSettings['awards_request'] - $requests_count),
	));

	// Redirect.
	redirectexit('action=admin;area=awards;sa=requests');
}