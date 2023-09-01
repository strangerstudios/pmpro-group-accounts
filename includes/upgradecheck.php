<?php
/**
 * Run any necessary upgrades to the DB.
 */
function pmprogroupacct_check_for_upgrades() {
	$db_version = get_option( 'pmproacct_db_version' );

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
		update_option( 'pmproacct_db_version', 1 );
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
    $wpdb->pmprogroupacct_members = $wpdb->prefix . 'pmprogroupacct_members';

	// wp_pmprogroupacct_groups
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmprogroupacct_groups . "` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `parent_user_id` bigint(20) unsigned NOT NULL,
            `parent_level_id` int(11) unsigned NOT NULL,
            `code` varchar(32) NOT NULL,
            `seats` int(11) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `parent` (`parent_user_id`,`parent_level_id`),
            UNIQUE KEY `code` (`code`)
		);
	";
	dbDelta( $sqlQuery );

	// wp_pmprogroupacct_members
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmprogroupacct_members . "` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) unsigned NOT NULL,
            `level_id` int(11) unsigned NOT NULL,
            `group_id` bigint(20) unsigned NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'active',
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_group` (`user_id`,`level_id`,`group_id`),
            KEY `status` (`status`)
		);
	";
	dbDelta( $sqlQuery );
}

// Check if the DB needs to be upgraded.
if ( is_admin() || defined('WP_CLI') ) {
	pmprogroupacct_check_for_upgrades();
}
