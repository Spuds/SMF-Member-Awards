<?php
if(file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
else if(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php and SSI.php files.');

if((SMF == 'SSI') && !$user_info['is_admin'])
	die('Admin priveleges required.');

db_extend('packages');

######################################################################################

$awards_members_columns[] = array('name' => 'uniq_id', 'type' => 'mediumint', 'size' => 5, 'null' => false, 'auto' => true);
$awards_members_columns[] = array('name' => 'id_award', 'type' => 'bigint', 'size' => 10, 'null' => false, 'default' => '0');
$awards_members_columns[] = array('name' => 'id_member', 'type' => 'int', 'size' => 8, 'null' => false, 'default' => '0');
$awards_members_columns[] = array('name' => 'date_received', 'type' => 'date', 'null' => false, 'default' => '0001-01-01');
$awards_members_columns[] = array('name' => 'favorite', 'type' => 'tinyint', 'size' => 4, 'null' => false, 'default' => '0');

$awards_members_indexes[] = array('type' => 'unique', 'columns' => array('id_award', 'id_member'));
$awards_members_indexes[] = array('type' => 'primary', 'columns' => array('uniq_id'));

$smcFunc['db_create_table']('{db_prefix}awards_members', $awards_members_columns, $awards_members_indexes, array(), 'ignore');

######################################################################################

$awards_columns[] = array('name' => 'id_award', 'type' => 'mediumint', 'size' => 5, 'null' => false, 'auto' => true);
$awards_columns[] = array('name' => 'award_name', 'type' => 'varchar', 'size' => 80, 'null' => false);
$awards_columns[] = array('name' => 'description', 'type' => 'varchar', 'size' => 80, 'null' => false);
$awards_columns[] = array('name' => 'time_added', 'type' => 'int', 'size' => 10, 'null' => false, 'default' => '0');
$awards_columns[] = array('name' => 'filename', 'type' => 'tinytext', 'null' => false);
$awards_columns[] = array('name' => 'minifile', 'type' => 'tinytext', 'null' => false);
$awards_columns[] = array('name' => 'id_category', 'type' => 'mediumint', 'size' => 5, 'null' => false, 'default' => '1');

$awards_indexes[] = array('type' => 'primary', 'columns' => array('id_award'));

$smcFunc['db_create_table']('{db_prefix}awards', $awards_columns, $awards_indexes, array(), 'ignore');

######################################################################################

$awards_categories_columns[] = array('name' => 'id_category', 'type' => 'mediumint', 'size' => 5, 'null' => false, 'auto' => true);
$awards_categories_columns[] = array('name' => 'category_name', 'type' => 'varchar', 'size' => 80, 'null' => false);

$awards_categories_indexes[] = array('type' => 'primary', 'columns' => array('id_category'));

$smcFunc['db_create_table']('{db_prefix}awards_categories', $awards_categories_columns, $awards_categories_indexes, array(), 'ignore');

// And for good measure, here's the default category!
$smcFunc['db_insert']('replace',
	'{db_prefix}awards_categories',
	array('category_name' => 'string'),
	array('Uncategorized'),
	array('id_category')
);

######################################################################################

$smcFunc['db_insert']('replace',
	'{db_prefix}settings',
	array('variable' => 'string', 'value' => 'string'),
	array(
		array('awards_dir', 'awards'),
		array('awards_favorites', '1'),
		array('awards_in_post', '1')
	),
	array('variable')
);

if(SMF == 'SSI')
	echo 'Database changes are complete!';
?>