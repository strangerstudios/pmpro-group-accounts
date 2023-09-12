<?php

/**
 * Add a page setting for the Manage Group page.
 *
 * @since TBD
 *
 * @param array $settings Array of settings for the PMPro settings page.
 * @return array $settings Array of settings for the PMPro settings page.
 */
function pmprogroupacct_extra_page_settings( $pages ) {
	$pages['pmprogroupacct_manage_group'] = array(
		'title' => esc_html__( 'Manage Group', 'pmpro-group-accounts' ),
		'content' => '[pmprogroupacct_manage_group]',
		'hint' => esc_html__( 'Include the shortcode [pmprogroupacct_manage_group].', 'pmpro-group-accounts' ),
	);
	return $pages;
}
add_filter( 'pmpro_extra_page_settings', 'pmprogroupacct_extra_page_settings' );

/**
 * Add "Manage Group" as an action link on the frontend Membership Account page if
 * the user has a group for the passed level.
 *
 * @since TBD
 *
 * @param array $action_links Array of action links for the Membership Account page.
 * @param int   $level_id    ID of the level the action links are being shown for.
 */
function pmprogroupacct_member_action_links( $action_links, $level_id ) {
	global $current_user;

	// If the user isn't logged in, return the default action links.
	if ( ! is_user_logged_in() ) {
		return $action_links;
	}

	// Get the group for this user and level.
	$group = PMProGroupAcct_Group::get_group_by_parent_user_id_and_parent_level_id( $current_user->ID, $level_id );
	if ( empty( $group ) ) {
		return $action_links;
	}

	// Get the URL for the Manage Group page.
	$manage_group_url = pmpro_url( 'pmprogroupacct_manage_group' );
	if ( empty( $manage_group_url ) ) {
		return $action_links;
	}

	// Add the "Manage Group" action link.
	$action_links['manage_group'] = '<a href="' . esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, $manage_group_url ) ) . '">' . esc_html__( 'Manage Group', 'pmpro-group-accounts' ) . '</a>';

	return $action_links;
}
add_filter( 'pmpro_member_action_links', 'pmprogroupacct_member_action_links', 10, 2 );

/**
 * Show the content for the [pmprogroupacct_manage_group] shortcode.
 *
 * @since TBD
 * @return string $content Content for the shortcode.
 */
function pmprogroupacct_shortcode_manage_group() {
	// Make sure that a group was passed. If not, show an error.
	if ( empty( $_REQUEST['pmprogroupacct_group_id'] ) ) {
		return '<p>' . esc_html__( 'No group was passed.', 'pmpro-group-accounts' ) . '</p>';
	}

	// Get the group.
	$group = new PMProGroupAcct_Group( intval( $_REQUEST['pmprogroupacct_group_id'] ) );

	// If the group doesn't exist or the current user doesn't own this group, show an error.
	if ( empty( $group->id ) || $group->group_parent_user_id !== get_current_user_id() ) {
		return '<p>' . esc_html__( 'You do not have permission to view this group.', 'pmpro-group-accounts' ) . '</p>';
	}

	// Get the active members in this group.
	$active_members = $group->get_active_members();

	// Show UI for showing seat usage, viewing/removing group members, and inviting new members.
	ob_start();
	?>
	<div id="pmproacct_manage_group">
		<div id="pmproacct_manage_group_members">
			<h2><?php esc_html_e( 'Group Members', 'pmpro-group-accounts' ); ?> (<?php echo count( $active_members ) . '/' . (int)$group->group_total_seats ?>)</h2>
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Username', 'pmpro-group-accounts' ); ?></th>
						<th><?php esc_html_e( 'Level', 'pmpro-group-accounts' ); ?></th>
						<th><?php esc_html_e( 'Remove', 'pmpro-group-accounts' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $active_members as $member ) {
						$user  = get_userdata( $member->group_child_user_id );
						$level = pmpro_getLevel( $member->group_child_level_id );
						?>
						<tr>
							<td><?php echo esc_html( $user->user_login ); ?></td>
							<td><?php echo esc_html( $level->name ); ?></td>
							<td><a href="<?php echo esc_url( add_query_arg( 'pmprogroupacct_remove_member', $member->id, pmpro_url( 'pmprogroupacct_manage_group' ) ) ); ?>"><?php esc_html_e( 'Remove', 'pmpro-group-accounts' ); ?></a></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<div id="pmproacct_invite_new_members">
			<h2><?php esc_html_e( 'Invite New Members', 'pmpro-group-accounts' ); ?></h2>
			<p><?php printf( esc_html__( 'Users can join your group by using the checkout code %s.', 'pmpro-group-accounts' ), '<strong>' . esc_html( $group->group_checkout_code ) . '</strong>' ); ?></p>
		</div>
	</div>
	<?php
	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}
add_shortcode( 'pmprogroupacct_manage_group', 'pmprogroupacct_shortcode_manage_group' );
