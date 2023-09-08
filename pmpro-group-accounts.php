<?php
/**
 * Plugin Name: Paid Memberships Pro - Group Accounts Add On
 * Plugin URI: ttps://www.paidmembershipspro.com/add-ons/pmpro-group-accounts/
 * Description: [Short description of the plugin]
 * Version: TBD
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-group-accounts
 * Domain Path: /languages
 */

define( 'PMPROGROUPACCT_BASE_FILE', __FILE__ );
define( 'PMPROGROUPACCT_DIR', dirname( __FILE__ ) );
define( 'PMPROGROUPACCT_VERSION', 'TBD' );

include_once( PMPROGROUPACCT_DIR . '/classes/class-pmprogroupacct-group.php' );
include_once( PMPROGROUPACCT_DIR . '/classes/class-pmprogroupacct-group-member.php' );

include_once( PMPROGROUPACCT_DIR . '/includes/functions.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/scripts.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/edit-level.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/checkout-parent.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/upgradecheck.php' );

/**
 * Load the languages folder for translations.
 */
function pmprogroupacct_load_textdomain() {
	load_plugin_textdomain( 'pmpro-group-accounts', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmprogroupacct_load_textdomain' );

/**
 * Add links to the plugin row meta
 *
 * @param $links - Links for plugin
 * @param $file - main plugin filename
 * @return array - Array of links
 */
function pmprogroupacct_plugin_row_meta($links, $file)
{
	if (strpos($file, 'pmpro-group-accounts.php') !== false) {
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/pmpro-group-accounts/') . '" title="' . esc_attr(__('View Documentation', 'pmpro-group-accounts')) . '">' . __('Docs', 'pmpro-group-accounts') . '</a>',
			'<a href="' . esc_url('https://www.paidmembershipspro.com/support/') . '" title="' . esc_attr(__('Visit Customer Support Forum', 'pmpro-group-accounts')) . '">' . __('Support', 'pmpro-group-accounts') . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprogroupacct_plugin_row_meta', 10, 2);
