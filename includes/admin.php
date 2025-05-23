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
	$parent_user = get_userdata( $group->group_parent_user_id );
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
		<td>
			<?php
				// If the parent user is not found, show the user ID.
				if ( empty( $parent_user ) ) {
					echo esc_html( $group->group_parent_user_id );
				} else {
					// Otherwise, link to the user edit page.
					echo '<a href="' . esc_url( pmprogroupacct_member_edit_url_for_user( $parent_user ) ) . '">' . esc_html( $parent_user->user_login ) . '</a>';
				}
			?>
		</td>
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
		$parent_user_link = empty( $parent_user ) ? esc_html( $group->group_parent_user_id ) : '<a href="' . esc_url( pmprogroupacct_member_edit_url_for_user( $parent_user ) ) . '">' . esc_html( $parent_user->user_login ) . '</a>';
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
 * Add group information to the Orders CSV export.
 * 
 * @since TBD
 *
 * @param array $columns The columns to add to the CSV export as $heading => $callback.
 * @return array The columns for the Orders list CSV export.
 */
function pmprogroupacct_orders_csv_extra_columns( $columns ) {
	$columns['pmprogroupacct_code'] = 'pmprogroupacct_orders_csv_extra_columns_group_code';
	$columns['pmprogroupacct_parent'] = 'pmprogroupacct_orders_csv_extra_columns_group_parent';
	return $columns;
}
add_filter( 'pmpro_orders_csv_extra_columns', 'pmprogroupacct_orders_csv_extra_columns' );

/**
 * Callback function to add the Group Code to the Orders CSV export.
 * 
 * @since TBD
 *
 * @param MemberOrder $order The Paid Memberships Pro order object.
 * @return string The group code.
 */
function pmprogroupacct_orders_csv_extra_columns_group_code( $order ) {
	// Get the group ID for this order.
	$group_id = get_pmpro_membership_order_meta( $order->id, 'pmprogroupacct_group_id', true );

	// If there is a group ID, get and return the group code.
	if ( ! empty( $group_id ) ) {
		$group = new PMProGroupAcct_Group( intval( $group_id ) );
		return $group->group_checkout_code;
	} else {
		return '';
	}
}

/**
 * Callback unction to add the Group Parent to the Orders CSV export.
 * 
 * @since TBD
 *
 * @param MemberOrder $order The Paid Memberships Pro order object.
 * @return string The group parent's username.
 */
function pmprogroupacct_orders_csv_extra_columns_group_parent( $order ) {
	// Get the group ID for this order.
	$group_id = get_pmpro_membership_order_meta( $order->id, 'pmprogroupacct_group_id', true );

	// If there is a group ID, get and return the group parent's username.
	if ( ! empty( $group_id ) ) {
		$group = new PMProGroupAcct_Group( intval( $group_id ) );
		$parent_user = get_userdata( $group->group_parent_user_id );
		return ! empty( $parent_user ) ? $parent_user->user_login : '';
	} else {
		return '';
	}
}

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
 * @since 1.3
 */
function pmprogroupacct_pmproiucsv_post_user_import( $user, $membership_id, $order ) {
	global $wpdb;

	$group_id = empty( $user->pmprogroupacct_group_id ) ? '' : $user->pmprogroupacct_group_id;
	$seats = ! empty( $user->pmprogroupacct_group_total_seats ) ? intval( $user->pmprogroupacct_group_total_seats ) : '';

	// Bail if we don't have seats and we don't have a group ID. We aren't creating / updating a parent group account or
	// adding a user to a child group account
	if ( empty( $seats ) && empty( $group_id ) ) {
		return;
	}

	if ( ! empty ( $seats ) ) {
		$parent_group = PMProGroupAcct_Group::get_group_by_parent_user_id_and_parent_level_id( $user->ID, $membership_id );
		if ( empty( $parent_group ) ) {
			PMProGroupAcct_Group::create( $user->ID, $membership_id, $seats );
		} else {
			$parent_group->update_group_total_seats( $seats );
		}
	}

	// Add the child account if the level is a child level and passed through a group ID.
	if ( ! empty( $group_id ) && pmprogroupacct_level_can_be_claimed_using_group_codes( $membership_id ) ) {
		
		// Let's set all previous instances to "inactive" before trying to insert the child record.
		$wpdb->query( 
			$wpdb->prepare( 
				"UPDATE $wpdb->pmprogroupacct_group_members SET group_child_status = 'inactive' WHERE group_child_user_id = %d AND group_child_level_id = %d",
				$user->ID,
				$membership_id
			)
		);
		
		// Add them back.
		PMProGroupAcct_Group_Member::create( $user->ID, $membership_id, $group_id );
	}
}
add_action( 'pmproiucsv_after_member_import', 'pmprogroupacct_pmproiucsv_post_user_import', 10, 3 );

/**
 * Adds a Parent Account column to the Members List.
 * 
 * @since TBD
 *
 * @param array $columns The columns to display in the Members List.
 * @return array The updated columns to display in the Members List.
 */
function pmprogroupacct_manage_memberslist_columns( $columns ) {
	$columns['pmprogroupacct_parent'] = esc_html__( 'Parent Account', 'pmpro-group-accounts' );
	return $columns;
}
add_filter( 'pmpro_manage_memberslist_columns', 'pmprogroupacct_manage_memberslist_columns' );

/**
 * Display the group parent in the Members List.
 * 
 * @since TBD
 * 
 * @param string $column_name The name of the column to display.
 * @param int    $user_id     The ID of the user to display the column for.
 * @param array  $item        The membership data being shown.
 */
function pmprogroupacct_manage_memberslist_column_body( $column_name, $user_id, $item ) {
	// Populate the parent account column.
	if ( 'pmprogroupacct_parent' === $column_name ) {
		// Get the user's group member object for the membership level being shown.
		$group_member_query_args = array(
			'group_child_user_id'  => $user_id,
			'group_child_level_id' => $item['membership_id'],
			'group_child_status'   => 'active',
		);
		$group_members = PMProGroupAcct_Group_Member::get_group_members( $group_member_query_args );

		// If the membership is a group membership, get the group information.
		if ( ! empty( $group_members) ) {
			$group_id = $group_members[0]->group_id;	
			$group = new PMProGroupAcct_Group( $group_id );
			$parent_user = get_userdata( $group->group_parent_user_id );
			$parent_user_info = empty( $parent_user ) ? esc_html( $group->group_parent_user_id ) : '<a href="' . esc_url( pmprogroupacct_member_edit_url_for_user( $parent_user ) ) . '">' . esc_html( $parent_user->user_login ) . '</a>';
		} else {
			$parent_user_info = esc_html__( '&#8212;', 'pmpro-group-accounts' );
		}

		// Echo the data for this column.
		echo $parent_user_info;
	}
}
add_filter( 'pmpro_manage_memberslist_custom_column', 'pmprogroupacct_manage_memberslist_column_body', 10, 3 );

/**
 * Add a Parent Account column to the Members List CSV export
 * 
 * @since TBD
 *
 * @param array $columns The columns to add to the CSV export as $heading => $callback.
 * @return array The columns for the Members List CSV export.
 */
function pmprogroupacct_members_list_csv_extra_columns( $columns ) {
	$columns['pmprogroupacct_parent'] = 'pmprogroupacct_members_list_csv_extra_columns_parent_account';
	return $columns;
}
add_filter( 'pmpro_members_list_csv_extra_columns', 'pmprogroupacct_members_list_csv_extra_columns' );

/**
 * Callback function to add the Parent Account to the Members List CSV export.
 * 
 * @since TBD
 *
 * @param object $user The user object for the row with some additional membership data.
 * @return string The group parent's username.
 */
function pmprogroupacct_members_list_csv_extra_columns_parent_account( $user ) {
	// Get the user's group member object for the membership level in this row.
	$group_member_query_args = array(
		'group_child_user_id'  => $user->ID,
		'group_child_level_id' => $user->membership_id,
		'group_child_status'   => 'active',
	);
	$group_members = PMProGroupAcct_Group_Member::get_group_members( $group_member_query_args );

	// If the membership is a group membership, get the group information.
	if ( ! empty( $group_members) ) {
		$group_id = $group_members[0]->group_id;
		$group = new PMProGroupAcct_Group( $group_id );
		$parent_user = get_userdata( $group->group_parent_user_id );
		return ! empty( $parent_user ) ? $parent_user->user_login : '';
	} else {
		return '';
	}
  
  // If we make it here, lets just return nothing.
  return '';
}
