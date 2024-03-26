<?php
/**
 * Admin functions.
 */

/**
 * Runs only when the plugin is activated.
 *
 * @since 1.0
 */
function pmprogroupacct_admin_notice_activation_hook() {
	// Create transient data.
	set_transient( 'pmpro-group-accounts-admin-notice', true, 5 );
}
register_activation_hook( PMPROGROUPACCT_BASENAME, 'pmprogroupacct_admin_notice_activation_hook' );

/**
 * Admin Notice on Activation.
 *
 * @since 1.0
 */
function pmprogroupacct_admin_notice() {
	// Paid Memberships Pro not activated, let's bail.
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return;
	}

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
 * Show a message if Paid Memberships Pro is inactive or not installed.
 */
function pmprogroupacct_required_installed() {
	// The required plugins for this Add On to work.
	$required_plugins = array(
		'paid-memberships-pro' => __( 'Paid Memberships Pro', 'pmpro-group-accounts' ),
	);

	// Check if the required plugins are installed.
	$missing_plugins = array();
	foreach ( $required_plugins as $plugin => $name ) {
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
			$missing_plugins[$plugin] = $name;
		}
	}

	// If there are missing plugins, show a notice.
	if ( ! empty( $missing_plugins ) ) {
		// Build install links here.
		$install_plugins = array();
		foreach( $missing_plugins as $path => $name ) {
			$install_plugins[] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $path ), 'install-plugin_' . $path ) ), esc_html( $name ) );
		}

		// Show notice with install_plugin links.
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			sprintf(
				/* translators: 1: This plugin's name. 2: Required plugin name(s). */
				esc_html__( 'The following plugin(s) are required for the %1$s plugin to work: %2$s', 'pmpro-group-accounts' ),
				esc_html__( 'Group Accounts', 'pmpro-group-accounts' ),
				implode( ', ', $install_plugins ) // $install_plugins was escaped when built.
			)
		);

		return; // Bail here, so we only show one notice at a time.
	}

	// Check if the required plugins are active and show a notice with activation links if they are not
	$inactive_plugins = array();
	foreach ( $required_plugins as $plugin => $name ) {
		$full_path = $plugin . '/' . $plugin . '.php';
		if ( ! is_plugin_active( $full_path ) ) {
			$inactive_plugins[$plugin] = $name;
		}
	}

	// If there are inactive plugins, show a notice.
	if ( ! empty( $inactive_plugins ) ) {
		// Build activate links here.
		$activate_plugins = array();
		foreach( $inactive_plugins as $path => $name ) {
			$full_path = $path . '/' . $path . '.php';
			$activate_plugins[] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . $full_path ), 'activate-plugin_' . $full_path ) ), esc_html( $name ) );
		}

		// Show notice with activate_plugin links.
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			sprintf(
				/* translators: 1: This plugin's name. 2: Required plugin name(s). */
				esc_html__( 'The following plugin(s) are required for the %1$s plugin to work: %2$s', 'pmpro-group-accounts' ),
				esc_html__( 'Group Accounts', 'pmpro-group-accounts' ),
				implode( ', ', $activate_plugins ) // $activate_plugins was escaped when built.
			)
		);

		return; // Bail here, so we only show one notice at a time.
	}
}
add_action( 'admin_notices', 'pmprogroupacct_required_installed' );

/**
 * Show the group information associated with a child order on the Edit Order page.
 */
function pmprogroupacct_after_order_settings( $order ) {
	// Return early if this is a new order.
	if ( empty( $order->id ) ) {
		return;
	}

	// Get the group ID for this order.
	$group_id = get_pmpro_membership_order_meta( $order->id, 'pmprogroupacct_group_id', true );

	// If there is no group ID, this order is not a child account. Bail.
	if ( empty( $group_id ) ) {
		return;
	}

	$group = new PMProGroupAcct_Group( intval( $group_id ) );
	$parent_user      = get_userdata( $group->group_parent_user_id );
	$parent_user_link = empty( $parent_user ) ? esc_html( $group->group_parent_user_id ) : '<a href="' . esc_url( add_query_arg( 'user_id', $parent_user->ID, admin_url( 'user-edit.php' ) ) ) . '">' . esc_html( $parent_user->user_login ) . '</a>';
	?>
	<tr>
		<th colspan="2"><?php esc_html_e( 'Group Account Information', 'pmpro-group-accounts' ); ?></th>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Group ID', 'pmpro-group-accounts' ); ?></th>
		<td><?php echo esc_html( $group->id ); ?></td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Group Owner', 'pmpro-group-accounts' ); ?></th>
		<td><?php echo $parent_user_link ?></td>
	</tr>
	<?php
	$manage_group_url = pmpro_url( 'pmprogroupacct_manage_group' );
	if ( ! empty( $manage_group_url ) ) {
		?>
		<tr>
			<th><?php esc_html_e( 'Actions', 'pmpro-group-accounts' ); ?></th>
			<td>
				<a href="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, $manage_group_url ) ); ?>"><?php esc_html_e( 'Manage Group', 'pmpro-group-accounts' ); ?></a>
			</td>
		</tr>
		<?php
	}
}
add_action( 'pmpro_after_order_settings', 'pmprogroupacct_after_order_settings', 10, 1 );

/**
 * Show the group information associated with a child order on the Orders list table.
 *
 * @param array $columns The columns for the Orders list table.
 * @return array The columns for the Orders list table.
 * @since 1.0
 */
function pmprogroupacct_manage_orderslist_columns( $columns ) {
	$columns['pmprogroupacct_code'] = __( 'Group Code', 'pmpro-group-accounts' );
	$columns['pmprogroupacct_parent'] = __( 'Parent Account', 'pmpro-group-accounts' );
	return $columns;
}
add_filter( 'pmpro_manage_orderslist_columns',  'pmprogroupacct_manage_orderslist_columns', 10, 1 );

function pmprogroupacct_manage_orderslist_column_body( $column_name, $item ) {
	// Get the group ID for this order.
	$group_id = get_pmpro_membership_order_meta( $item, 'pmprogroupacct_group_id', true );

	// If is a group ID, get the group information.
	if ( ! empty( $group_id ) ) {
		$group = new PMProGroupAcct_Group( intval( $group_id ) );
		$parent_user      = get_userdata( $group->group_parent_user_id );
		$parent_user_link = empty( $parent_user ) ? esc_html( $group->group_parent_user_id ) : '<a href="' . esc_url( add_query_arg( 'user_id', $parent_user->ID, admin_url( 'user-edit.php' ) ) ) . '">' . esc_html( $parent_user->user_login ) . '</a>';
	}

	// Populate the group code column.
	if ( $column_name == 'pmprogroupacct_code' ) {
		echo $group_id ? esc_html( $group->group_checkout_code ) : esc_html__( '&#8212;', 'pmpro-group-accounts' );
	}

	// Populate the parent account column.
	if ( $column_name == 'pmprogroupacct_parent' ) {
		echo $group_id ? wp_kses_post( $parent_user_link ) : esc_html__( '&#8212;', 'pmpro-group-accounts' );
	}

}
add_action( 'pmpro_manage_orderlist_custom_column' , 'pmprogroupacct_manage_orderslist_column_body', 10, 2 );

/**
 * Add links to the plugin row meta
 *
 * @since 1.0
 *
 * @param $links - Links for plugin
 * @param $file - main plugin filename
 * @return array - Array of links
 */
function pmprogroupacct_plugin_row_meta($links, $file) {
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


/**
 * Import group account member associations when using the Import Users From CSV plugin.
 *
 * @param WP_User $user The user object that was imported.
 * @param int $membership_id The membership level ID that was imported.
 * @param MemberOrder|null $order The order object that was created during import. Null if no order is created.
 * @since TBD
 */
function pmprogroupacct_pmproiucsv_post_user_import( $user, $membership_id, $order ) {

	$group_level_settings =  pmprogroupacct_get_settings_for_level( $membership_id );
	$parent_group = PMProGroupAcct_Group::get_group_by_parent_user_id_and_parent_level_id( $user->ID, $membership_id );
	$group_id = $user->pmprogroupacct_group_id; // This is used for child accounts.
	$seats = ! empty( $user->pmprogroupacct_group_total_seats ) ? intval( $user->pmprogroupacct_group_total_seats ) : $group_level_settings['max_seats'];
	
	// Add user to group if their level is a parent level and create the group if it doesn't exist.
	if ( ! empty( $parent_group ) ) {
		//Update seats if the user has a total seats value from CSV.
		$parent_group->update_group_total_seats( $seats );
	} elseif ( empty( $group_id ) ) {
		// There is not already a group for this user and level. Let's create one.
		PMProGroupAcct_Group::create( $user->ID, $membership_id, $seats );
	}
	
	// Add the child account if the level is a child level and passed through a group ID.
	if ( ! empty( $group_id ) && pmprogroupacct_level_can_be_claimed_using_group_codes( $membership_id ) ) {
		PMProGroupAcct_Group_Member::create( $user->ID, $membership_id, $group_id );
	}
}
add_action( 'pmproiucsv_after_member_import', 'pmprogroupacct_pmproiucsv_post_user_import', 10, 3 );