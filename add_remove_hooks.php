<?php

/**
 * This file is a simplified hook installer
 */

// If we have found SSI.php and we are outside of SMF, then we are running standalone.
if (file_exists(__DIR__ . '/SSI.php') && !defined('SMF'))
	require_once(__DIR__ . '/SSI.php');
elseif (!defined('SMF')) // If we are outside SMF and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');

global $context;

// Define the hooks
$hook_functions = array(
	'integrate_pre_include' => '$sourcedir/AwardsHooks.php',
	'integrate_admin_areas' => 'member_awards_admin_areas',
	'integrate_profile_areas' => 'member_awards_profile_areas',
	'integrate_load_permissions' => 'member_awards_load_permissions',
	'integrate_menu_buttons' => 'member_awards_menu_buttons',
	'integrate_user_info' => 'member_awards_load_user_info',
);

// Adding or removing them?
if (!empty($context['uninstalling']))
	$call = 'remove_integration_function';
else
	$call = 'add_integration_function';

// Do the deed
foreach ($hook_functions as $hook => $function)
	$call($hook, $function);

if (SMF === 'SSI')
   echo 'Congratulations! You have successfully installed the integration hooks for Member Awards';