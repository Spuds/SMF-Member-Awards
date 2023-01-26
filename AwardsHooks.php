<?php

/**
 * @package   Awards Modification
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
{
	die('Hacking attempt...');
}

/**
 * Profile Menu Hook, integrate_profile_areas, called from profile.php
 * Used to add menu items to the profile area
 * Adds show my & view award options
 *
 * @param array $profile_areas
 */
function member_awards_profile_areas(&$profile_areas)
{
	global $txt, $user_info, $modSettings;

	// No need to show these profile option to guests, perhaps a view_awards permissions should be added?
	if ($user_info['is_guest'])
	{
		return;
	}

	// If Member Awards is disabled, we don't go any further
	if (empty($modSettings['awards_enabled']))
	{
		if (!$user_info['is_admin'] && !isAllowedTo('manage_awards'))
			return;
	}

	member_awards_array_insert($profile_areas, 'info', array(
		'member_awards' => array(
			'title' => $txt['awards'],
			'areas' => array(
				'showAwards' => array(
					'label' => $txt['showAwards'],
					'file' => 'AwardsProfile.php',
					'function' => 'showAwards',
					'permission' => array(
						'own' => 'profile_view_own',
						'any' => 'profile_view_any',
					),
				),
				'membersAwards' => array(
					'file' => 'AwardsProfile.php',
					'function' => 'membersAwards',
					'hidden' => (isset($_GET['area']) && $_GET['area'] !== "membersAwards"),
					'permission' => array(
						'own' => 'profile_view_own',
						'any' => 'profile_view_any',
					),
				),
				'listAwards' => array(
					'label' => $txt['listAwards'],
					'file' => 'AwardsProfile.php',
					'function' => 'listAwards',
					'permission' => array(
						'own' => 'profile_view_own',
						'any' => 'profile_view_any',
					),
				),
				'requestAwards' => array(
					'file' => 'AwardsProfile.php',
					'hidden' => true,
					'function' => 'requestAwards',
					'permission' => array(
						'own' => 'profile_view_own',
						'any' => 'profile_view_any',
					),
				)
			)
		)
	), 'after');
}

/**
 * Admin hook, integrate_admin_areas, called from Admin.php
 * adds the admin menu and all award sub actions as a sub menu
 * hidden to all but admin, accessible via manage_award permission
 *
 * @param array $admin_areas
 */
function member_awards_admin_areas(&$admin_areas)
{
	global $txt, $modSettings;

	// allow members with this permission to access the menu :P
	$admin_areas['members']['permission'][] = 'manage_awards';
	$admin_areas['members']['permission'][] = 'assign_awards';

	// our main awards menu area, under the members tab
	$admin_areas['members']['areas']['awards'] = array(
		'label' => $txt['awards'],
		'file' => 'AwardsAdmin.php',
		'function' => 'Awards',
		'icon' => 'awards.gif',
		'permission' => array('manage_awards', 'assign_awards'),
		'subsections' => array(
			'main' => array($txt['awards_main'], array('assign_awards', 'manage_awards')),
			'categories' => array($txt['awards_categories'], 'manage_awards'),
			'modify' => array(isset($_REQUEST['a_id']) ? $txt['awards_modify'] : $txt['awards_add'], 'manage_awards'),
			'assign' => array($txt['awards_assign'], array('assign_awards', 'manage_awards')),
			'assigngroup' => array($txt['awards_assign_membergroup'], 'manage_awards'),
			'assignmass' => array($txt['awards_assign_mass'], 'manage_awards'),
			'requests' => array($txt['awards_requests'] . (empty($modSettings['awards_request']) ? '' : ' (<b>' . $modSettings['awards_request'] . '</b>)'), array('assign_awards', 'manage_awards')),
			'settings' => array($txt['awards_settings'], 'manage_awards'),
		)
	);
}

/**
 * Permission hook, adds manage_awards permission to the member admin area
 *
 * @param array $permissionGroups
 * @param array $permissionList
 * @param array $leftPermissionGroups
 * @param array $hiddenPermissions
 * @param array $relabelPermissions
 */
function member_awards_load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	global $context;

	// Permissions hook, integrate_load_permissions, called from ManagePermissions.php
	// used to add new permisssions ...
	$permissionList['membergroup']['manage_awards'] = array(false, 'member_admin', 'administrate');
	$permissionList['membergroup']['assign_awards'] = array(false, 'member_admin', 'administrate');

	$context['non_guest_permissions'] = array_merge($context['non_guest_permissions'], array('manage_awards', 'assign_awards'));
}

/**
 * Menu Button hook, integrate_menu_buttons, called from subs.php
 * used to add top menu buttons
 * adds awards menu item below members button
 * visable to anyone with manage_awards permission
 *
 * @param type $buttons
 */
function member_awards_menu_buttons(&$buttons)
{
	global $txt, $scripturl;

	// Bit of a cheat but known to happen
	if (empty($txt['awards']))
	{
		$txt['awards'] = 'Awards';
	}

	// allows members with manage_awards permission to see a menu item since the admin menu is hidden for them
	$buttons['mlist']['sub_buttons']['awards'] = array(
		'title' => $txt['awards'],
		'href' => $scripturl . '?action=admin;area=awards;sa=main',
		'show' => (allowedTo('manage_awards') || allowedto('assign_awards')),
	);
}

/**
 * Helper function to insert a menu
 *
 * @param array $input the array we will insert to
 * @param string $key the key in the array
 * @param array $insert the data to add before or after the above key
 * @param string $where adding before or after
 * @param bool $strict
 */
function member_awards_array_insert(&$input, $key, $insert, $where = 'before', $strict = false)
{
	$position = array_search($key, array_keys($input), $strict);

	// If the key is not found, just insert it at the end
	if ($position === false)
	{
		$input = array_merge($input, $insert);

		return;
	}

	if ($where === 'after')
	{
		++$position;
	}

	// Insert as first
	if ($position === 0)
	{
		$input = array_merge($insert, $input);
	}
	else
	{
		$input = array_merge(array_slice($input, 0, $position), $insert, array_slice($input, $position));
	}
}
