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

	// If the group doesn't exist, show an error.
	if ( empty( $group->id ) ) {
		return '<p>' . esc_html__( 'You do not have permission to view this group.', 'pmpro-group-accounts' ) . '</p>';
	}

	// Check if the current user can view this page.
	$is_admin = current_user_can( apply_filters( 'pmpro_edit_member_capability', 'manage_options' ) );
	if ( ! $is_admin && $group->group_parent_user_id !== get_current_user_id() ) {
		// The current user doesn't have permission to view this group.
		return '<p>' . esc_html__( 'You do not have permission to view this group.', 'pmpro-group-accounts' ) . '</p>';
	}

	// If the user is trying to remove a group member, remove them.
	$removal_message = '';
	if ( ! empty( $_REQUEST['pmprogroupacct_remove_group_members'] ) ) {
		// Make sure that the nonce is valid.
		if ( ! wp_verify_nonce( $_REQUEST['pmprogroupacct_remove_group_members_nonce'], 'pmprogroupacct_remove_group_members' ) ) {
			$removal_message = '<div class="pmpro_error"><p>' . esc_html__( 'Invalid nonce.', 'pmpro-group-accounts' ) . '</p></div>';
		}

		// Get the group members.
		$group_members = array();
		foreach ( $_REQUEST['pmprogroupacct_remove_group_members'] as $group_member_id ) {
			$group_members[] = new PMProGroupAcct_Group_Member( intval( $group_member_id ) );
		}

		// If the group member doesn't exist or the current user doesn't own this group, show an error.
		foreach ( $group_members as $group_member ) {
			if ( empty( $group_member->id ) || $group_member->group_id !== $group->id ) {
				$removal_message = '<div class="pmpro_error"><p>' . esc_html__( 'You do not have permission to remove this group member.', 'pmpro-group-accounts' ) . '</p></div>';
			}
		}

		// If there wasn't an error, cancel the group member's membership, which will remove them from the group.
		if ( empty( $removal_message ) ) {
			foreach ( $group_members as $group_member ) {
				if ( pmpro_cancelMembershipLevel( $group_member->group_child_level_id, $group_member->group_child_user_id ) ) {
					// Membership cancelled. Force the group removal to happen now.
					pmpro_do_action_after_all_membership_level_changes();
				} else {
					// User must not have had this membership level. Remove them from the group.
					$group_member->update_group_child_status( 'inactive' );
				}
			}
			$removal_message = '<div class="pmpro_success"><p>' . esc_html__( 'Group members removed.', 'pmpro-group-accounts' ) . '</p></div>';
		}
	}

	// If the user is trying to update the group settings, update them.
	$update_message = '';
	if ( isset( $_REQUEST['pmprogroupacct_group_total_seats'] ) ) {
		// Make sure that the current user has permission to update this group.
		if ( ! $is_admin ) {
			$update_message = '<div class="pmpro_error"><p>' . esc_html__( 'You do not have permission to update this group.', 'pmpro-group-accounts' ) . '</p></div>';
		}

		// Make sure that the nonce is valid.
		if ( ! wp_verify_nonce( $_REQUEST['pmprogroupacct_update_group_settings_nonce'], 'pmprogroupacct_update_group_settings' ) ) {
			$update_message = '<div class="pmpro_error"><p>' . esc_html__( 'Invalid nonce.', 'pmpro-group-accounts' ) . '</p></div>';
		}

		// Make sure that the total seats is a number.
		if ( ! is_numeric( $_REQUEST['pmprogroupacct_group_total_seats'] ) ) {
			$update_message = '<div class="pmpro_error"><p>' . esc_html__( 'Total seats must be a number.', 'pmpro-group-accounts' ) . '</p></div>';
		}

		// Update the group settings.
		$group->update_group_total_seats( (int)$_REQUEST['pmprogroupacct_group_total_seats'] );

		// Show a success message.
		$update_message = '<div class="pmpro_success"><p>' . esc_html__( 'Group settings updated.', 'pmpro-group-accounts' ) . '</p></div>';
	}

	// Get the active members in this group.
	$active_members = $group->get_active_members();

	// Get the old members in this group.
	$old_member_query_args = array(
		'group_id' => $group->id,
		'group_child_status' => 'inactive',
	);
	$old_members = PMProGroupAcct_Group_Member::get_group_members( $old_member_query_args );

	// Create UI.
	ob_start();
	?>
	<div id="pmproacct_manage_group">
		<?php
		// We want admins to have more settings, like the ability to change the number of seats.
		if ( $is_admin ) {
			?>
			<div id="pmproacct_manage_group_settings">
				<h2><?php esc_html_e( 'Group Settings', 'pmpro-group-accounts' ); ?></h2>
				<?php echo $update_message; ?>
				<form action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) ) ) ?>" method="post">
					<label for="pmprogroupacct_group_total_seats"><?php esc_html_e( 'Total Seats', 'pmpro-group-accounts' ); ?></label>
					<input type="number" name="pmprogroupacct_group_total_seats" id="pmprogroupacct_group_total_seats" value="<?php echo esc_attr( $group->group_total_seats ); ?>">
					<input type="hidden" name="pmprogroupacct_update_group_settings_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pmprogroupacct_update_group_settings' ) ); ?>">
					<input type="submit" value="<?php esc_attr_e( 'Update Settings', 'pmpro-group-accounts' ); ?>">
				</form>
			</div>
			<?php
		}
		?>
		<div id="pmproacct_manage_group_members">
			<h2><?php esc_html_e( 'Group Members', 'pmpro-group-accounts' ); ?> (<?php echo count( $active_members ) . '/' . (int)$group->group_total_seats ?>)</h2>
			<?php echo $removal_message; ?>
			<form action="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, pmpro_url( 'pmprogroupacct_manage_group' ) ) ) ?>" method="post">
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
								<td><input type="checkbox" name="pmprogroupacct_remove_group_members[]" value="<?php echo esc_attr( $member->id ); ?>"></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<?php wp_nonce_field( 'pmprogroupacct_remove_group_members', 'pmprogroupacct_remove_group_members_nonce' ); ?>
				<input type="submit" value="<?php esc_attr_e( 'Remove Selected Members', 'pmpro-group-accounts' ); ?>" onclick="return confirm( '<?php esc_html_e( 'Are you sure that you would like to remove these users from your group?', 'pmpro-group-accounts' ); ?>' );">
			</form>
		</div>
		<div id="pmproacct_invite_new_members">
			<h2><?php esc_html_e( 'Invite New Members', 'pmpro-group-accounts' ); ?></h2>
			<p><?php printf( esc_html__( 'Users can join your group by using the checkout code %s.', 'pmpro-group-accounts' ), '<strong>' . esc_html( $group->group_checkout_code ) . '</strong>' ); ?></p>
		</div>
		<?php
		if ( ! empty( $old_members ) ) {
			?>
			<div id="pmproacct_manage_group_old_members">
				<h2><?php esc_html_e( 'Old Members', 'pmpro-group-accounts' ); ?></h2>
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Username', 'pmpro-group-accounts' ); ?></th>
							<th><?php esc_html_e( 'Level', 'pmpro-group-accounts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $old_members as $member ) {
							$user  = get_userdata( $member->group_child_user_id );
							$level = pmpro_getLevel( $member->group_child_level_id );
							?>
							<tr>
								<td><?php echo esc_html( $user->user_login ); ?></td>
								<td><?php echo esc_html( $level->name ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div> 
			<?php
		}
		?>
	</div>
	<?php
	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}
add_shortcode( 'pmprogroupacct_manage_group', 'pmprogroupacct_shortcode_manage_group' );