<?php
/**
 * Plugin Name: Paid Memberships Pro - Group Accounts Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/group-accounts/
 * Description: Sell group memberships where one member pays for a collection of people to access your content individually.
 * Version: 1.3
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-group-accounts
 * Domain Path: /languages
 */

define( 'PMPROGROUPACCT_BASE_FILE', __FILE__ );
define( 'PMPROGROUPACCT_BASENAME', plugin_basename( __FILE__ ) );
define( 'PMPROGROUPACCT_DIR', dirname( __FILE__ ) );
define( 'PMPROGROUPACCT_VERSION', '1.3' );

include_once( PMPROGROUPACCT_DIR . '/classes/class-pmprogroupacct-group.php' );
include_once( PMPROGROUPACCT_DIR . '/classes/class-pmprogroupacct-group-member.php' );

include_once( PMPROGROUPACCT_DIR . '/includes/functions.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/admin.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/scripts.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/emails.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/edit-level.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/parents.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/children.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/edit-member.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/manage-group-page.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/upgradecheck.php' );

// Set up $wpdb tables.
global $wpdb;
$wpdb->pmprogroupacct_groups = $wpdb->prefix . 'pmprogroupacct_groups';
$wpdb->pmprogroupacct_group_members = $wpdb->prefix . 'pmprogroupacct_group_members';

/**
 * Load the languages folder for translations.
 *
 * @since 1.0
 */
function pmprogroupacct_load_textdomain() {
	load_plugin_textdomain( 'pmpro-group-accounts', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmprogroupacct_load_textdomain' );
