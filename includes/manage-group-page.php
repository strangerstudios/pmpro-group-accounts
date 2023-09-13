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

	// If the user is trying to remove a group member, remove them.
	$removal_message = '';
	if ( ! empty( $_REQUEST['pmprogroupacct_remove_group_member'] ) ) {
		// Make sure that the nonce is valid.
		if ( ! wp_verify_nonce( $_REQUEST['pmprogroupacct_remove_group_member_nonce'], 'pmprogroupacct_remove_group_member' ) ) {
			$removal_message = '<div class="pmpro_error"><p>' . esc_html__( 'Invalid nonce.', 'pmpro-group-accounts' ) . '</p></div>';
		}

		// Get the group member.
		$group_member = new PMProGroupAcct_Group_Member( intval( $_REQUEST['pmprogroupacct_remove_group_member'] ) );

		// If the group member doesn't exist or the current user doesn't own this group, show an error.
		if ( empty( $group_member->id ) || $group_member->group_id !== $group->id ) {
			$removal_message = '<div class="pmpro_error"><p>' . esc_html__( 'You do not have permission to remove this group member.', 'pmpro-group-accounts' ) . '</p></div>';
		}

		// If there wasn't an error, cancel the group member's membership, which will remove them from the group.
		if ( empty( $removal_message ) ) {
			if ( pmpro_cancelMembershipLevel( $group_member->group_child_level_id, $group_member->group_child_user_id ) ) {
				// Membership cancelled. Force the group removal to happen now.
				pmpro_do_action_after_all_membership_level_changes();
			} else {
				// User must not have had this membership level. Remove them from the group.
				$group_member->update_group_child_status( 'inactive' );
			}
			$removal_message = '<div class="pmpro_success"><p>' . esc_html__( 'Group member removed.', 'pmpro-group-accounts' ) . '</p></div>';
		}
	}

	// Get the active members in this group.
	$active_members = $group->get_active_members();

	// Show UI for showing seat usage, viewing/removing group members, and inviting new members.
	$nonce = wp_create_nonce( 'pmprogroupacct_remove_group_member' );
	ob_start();
	?>
	<div id="pmproacct_manage_group">
		<div id="pmproacct_manage_group_members">
			<h2><?php esc_html_e( 'Group Members', 'pmpro-group-accounts' ); ?> (<?php echo count( $active_members ) . '/' . (int)$group->group_total_seats ?>)</h2>
			<?php echo $removal_message; ?>
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
							<td><a href="<?php echo esc_url( add_query_arg( array( 'pmprogroupacct_grooup_id' => $group->id, 'pmprogroupacct_remove_group_member' => $member->id, 'pmprogroupacct_remove_group_member_nonce' => $nonce ) ) ); ?>" onclick="return confirm('<?php printf( esc_html__( 'Are you sure that you would like to remove the user %s from your group?', 'pmpro-group-accounts' ), esc_html( esc_html( $user->user_login ) ) );?>');"><?php esc_html_e( 'Remove', 'pmpro-group-accounts' ); ?></a></td>
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
