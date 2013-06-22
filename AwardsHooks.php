<?php

/**
 * @name      Awards Modification
 * @license   Mozilla Public License version 2.0 http://mozilla.org/MPL/2.0/.
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
 * profile menu hook
 * adds show my & view award options
 *
 * @param array $profile_areas
 */
function member_awards_profile_areas(&$profile_areas)
{
	// Profile Menu Hook, integrate_profile_areas, called from profile.php
	// used to add menu items to the profile area
	global $txt;

	$profile_areas['info']['areas'] = array_merge($profile_areas['info']['areas'],array(
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
			'hidden' => (isset($_GET['area']) && $_GET['area'] != "membersAwards"),
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
	));
}

/**
 * admin hook
 * adds the admin menu and all award sub actions as a sub menu
 * hidden to all but admin, accessable via manage_award permission

 * @param array $admin_areas
 */
function member_awards_admin_areas(&$admin_areas)
{
	// Admin Hook, integrate_admin_areas, called from Admin.php
	// used to add/modify admin menu areas
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
		'permission' => array('manage_awards','assign_awards'),
		'subsections' => array(
			'main' => array($txt['awards_main'],array('assign_awards','manage_awards')),
			'categories' => array($txt['awards_categories'],'manage_awards'),
			'modify' => array($txt['awards_modify'],'manage_awards'),
			'assign' => array($txt['awards_assign'],array('assign_awards','manage_awards')),
			'assigngroup' => array($txt['awards_assign_membergroup'],'manage_awards'),
			'assignmass' => array($txt['awards_assign_mass'],'manage_awards'),
			'requests' => array($txt['awards_requests'] . (empty($modSettings['awards_request']) ? '' : ' (<b>' . $modSettings['awards_request'] . '</b>)'),array('assign_awards','manage_awards')),
			'settings' => array($txt['awards_settings'],'manage_awards'),
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
	// Permissions hook, integrate_load_permissions, called from ManagePermissions.php
	// used to add new permisssions ... We need more permission hooks  .... e.g. loadIllegalGuestPermissions and setPermissionLevel !!
	$permissionList['membergroup']['manage_awards'] = array(false, 'member_admin', 'administrate');
	$permissionList['membergroup']['assign_awards'] = array(false, 'member_admin', 'administrate');
}

/**
 * menu button hook
 * adds awards menu item below members button
 * visable to anyone with manage_awards permission
 *
 * @param type $buttons
 */
function member_awards_menu_buttons(&$buttons)
{
	// Menu Button hook, integrate_menu_buttons, called from subs.php
	// used to add top menu buttons

	global $txt, $scripturl;

	// allows members with manage_awards permission to see a menu item since the admin menu is hidden for them
	$buttons['mlist']['sub_buttons']['awards'] = array(
		'title' => $txt['awards'],
		'href' => $scripturl . '?action=admin;area=awards;sa=main',
		'show' => (allowedTo('manage_awards') || allowedto('assign_awards')),
	);
}