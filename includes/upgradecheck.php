<?php
/**
 * Run any necessary upgrades to the DB.
 */
function pmprogroupacct_check_for_upgrades() {
	$db_version = get_option( 'pmprogroupacct_db_version' );

	// If we can't find the DB tables, reset db_version to 0
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmprogroupacct_groups = $wpdb->prefix . 'pmprogroupacct_groups';
	$table_exists = $wpdb->query("SHOW TABLES LIKE '" . $wpdb->pmprogroupacct_groups . "'");
	if(!$table_exists)
		$db_version = 0;

	// Default options.
	if ( ! $db_version ) {
		pmprogroupacct_db_delta();
		update_option( 'pmprogroupacct_db_version', 1 );
	}
}

/**
 * Make sure the DB is set up correctly.
 */
function pmprogroupacct_db_delta() {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmprogroupacct_groups = $wpdb->prefix . 'pmprogroupacct_groups';
	$wpdb->pmprogroupacct_group_members = $wpdb->prefix . 'pmprogroupacct_group_members';

	// pmprogroupacct_groups
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmprogroupacct_groups . "` (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`group_parent_user_id` bigint(20) unsigned NOT NULL,
			`group_parent_level_id` int(11) unsigned NOT NULL,
			`group_checkout_code` varchar(32) NOT NULL,
			`group_total_seats` int(11) unsigned NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `parent` (`group_parent_user_id`,`group_parent_level_id`),
			UNIQUE KEY `group_checkout_code` (`group_checkout_code`)
		);
	";
	dbDelta( $sqlQuery );

	// pmprogroupacct_group_members
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmprogroupacct_group_members . "` (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`group_child_user_id` bigint(20) unsigned NOT NULL,
			`group_child_level_id` int(11) unsigned NOT NULL,
			`group_id` bigint(20) unsigned NOT NULL,
			`group_child_status` varchar(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY (`id`),
			UNIQUE KEY `user_group` (`group_child_user_id`,`group_child_level_id`,`group_id`),
			KEY `group_child_status` (`group_child_status`)
		);
	";
	dbDelta( $sqlQuery );
}

// Check if the DB needs to be upgraded.
if ( is_admin() || defined('WP_CLI') ) {
	pmprogroupacct_check_for_upgrades();
}
