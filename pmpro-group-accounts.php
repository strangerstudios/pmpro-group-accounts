<?php
/**
 * Plugin Name: Paid Memberships Pro - Group Accounts Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/group-accounts/
 * Description: [Short description of the plugin]
 * Version: TBD
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-group-accounts
 * Domain Path: /languages
 */

define( 'PMPROGROUPACCT_BASE_FILE', __FILE__ );
define( 'PMPROGROUPACCT_BASENAME', plugin_basename( __FILE__ ) );
define( 'PMPROGROUPACCT_DIR', dirname( __FILE__ ) );
define( 'PMPROGROUPACCT_VERSION', 'TBD' );

include_once( PMPROGROUPACCT_DIR . '/classes/class-pmprogroupacct-group.php' );
include_once( PMPROGROUPACCT_DIR . '/classes/class-pmprogroupacct-group-member.php' );

include_once( PMPROGROUPACCT_DIR . '/includes/functions.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/scripts.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/emails.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/edit-level.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/parents.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/children.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/edit-user.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/manage-group-page.php' );
include_once( PMPROGROUPACCT_DIR . '/includes/upgradecheck.php' );

// Set up $wpdb tables.
global $wpdb;
$wpdb->pmprogroupacct_groups = $wpdb->prefix . 'pmprogroupacct_groups';
$wpdb->pmprogroupacct_group_members = $wpdb->prefix . 'pmprogroupacct_group_members';

/**
 * Load the languages folder for translations.
 *
 * @since TBD
 */
function pmprogroupacct_load_textdomain() {
	load_plugin_textdomain( 'pmpro-group-accounts', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmprogroupacct_load_textdomain' );

/**
 * Runs only when the plugin is activated.
 *
 * @since TBD
 */
function pmprogroupacct_admin_notice_activation_hook() {
	// Create transient data.
	set_transient( 'pmpro-group-accounts-admin-notice', true, 5 );
}
register_activation_hook( PMPROGROUPACCT_BASENAME, 'pmprogroupacct_admin_notice_activation_hook' );

/**
 * Admin Notice on Activation.
 *
 * @since TBD
 */
function pmprogroupacct_admin_notice() {
	// Check transient, if available display notice.
	if ( get_transient( 'pmpro-group-accounts-admin-notice' ) ) { ?>
		<div class="updated notice is-dismissible">
			<p><?php printf( __( 'Thank you for activating. <a href="%s">Create a new membership level or update an existing level</a> to add group account features.', 'pmpro-group-accounts' ), add_query_arg( array( 'page' => 'pmpro-membershiplevels' ), admin_url( 'admin.php' ) ) ); ?></p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'pmpro-group-accounts-admin-notice' );
	}
}
add_action( 'admin_notices', 'pmprogroupacct_admin_notice' );

/**
 * Add links to the plugin row meta
 *
 * @since TBD
 *
 * @param $links - Links for plugin
 * @param $file - main plugin filename
 * @return array - Array of links
 */
function pmprogroupacct_plugin_row_meta($links, $file)
{
	if (strpos($file, 'pmpro-group-accounts.php') !== false) {
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/group-accounts/') . '" title="' . esc_attr(__('View Documentation', 'pmpro-group-accounts')) . '">' . __('Docs', 'pmpro-group-accounts') . '</a>',
			'<a href="' . esc_url('https://www.paidmembershipspro.com/support/') . '" title="' . esc_attr(__('Visit Customer Support Forum', 'pmpro-group-accounts')) . '">' . __('Support', 'pmpro-group-accounts') . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprogroupacct_plugin_row_meta', 10, 2);
