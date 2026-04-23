<?php
/**
 * Functionality for the Edit Member or Edit User page to show group account information.
 * 
 */

/**
 * Add a panel to the Edit Member dashboard page.
 *
 * @since 1.0.1
 *
 * @param array $panels Array of panels.
 * @return array
 */
function pmprogroupacct_pmpro_member_edit_panels( $panels ) {
	// If the class doesn't exist and the abstract class does, require the class.
	if ( ! class_exists( 'PMProgroupacct_Member_Edit_Panel' ) && class_exists( 'PMPro_Member_Edit_Panel' ) ) {
		require_once( PMPROGROUPACCT_DIR . '/classes/class-pmprogroupacct-member-edit-panel.php' );
	}

	// If the class exists, add a panel.
	if ( class_exists( 'PMProgroupacct_Member_Edit_Panel' ) ) {
		$panels[] = new PMProgroupacct_Member_Edit_Panel();
	}

	return $panels;
}

/**
 * Hook the correct function for admins editing a member's profile.
 *
 * @since 1.0.1
 */
function pmprogroupacct_hook_edit_member_profile() {
	// If the `pmpro_member_edit_get_panels()` function exists, add a panel.
	// Otherwise, use the legacy hook.
	if ( function_exists( 'pmpro_member_edit_get_panels' ) ) {
		add_filter( 'pmpro_member_edit_panels', 'pmprogroupacct_pmpro_member_edit_panels' );
	} else {
		add_action( 'pmpro_after_membership_level_profile_fields', 'pmprogroupacct_show_group_account_info', 10, 1 );
	}
}
add_action( 'admin_init', 'pmprogroupacct_hook_edit_member_profile', 0 );

/**
 * When administrators edit a member or user, we want to show all groups that they manage,
 * including showing the group ID, the level ID for the group, the number of seats in the group,
 * and a link to manage the group if the "Manage Group" page is set.
 *
 * We also want to show a table of all groups that the user is a member of, including
 * links to the group owner, the level that they claimed with the group, and the group member status.
 *
 * @since 1.0.1
 *
 * @param WP_User $user The user object being viewed.
 */
function pmprogroupacct_show_group_account_info( $user ) {
	global $pmpro_pages;

	// Show any notice set by the generate-group handler on the previous request.
	pmprogroupacct_maybe_render_generate_group_notice();

	// Get all groups that the user manages.
	$group_query_args = array(
		'group_parent_user_id' => (int)$user->ID,
	);
	$groups = PMProGroupAcct_Group::get_groups( $group_query_args );

	// Get all groups that the user is a member of.
	$group_member_query_args = array(
		'group_child_user_id' => (int)$user->ID,
	);
	$group_members = PMProGroupAcct_Group_Member::get_group_members( $group_member_query_args );

	// Figure out which of the user's parent-eligible levels do not yet have a group
	// so we can offer an inline "Generate Group" form for each one. This supports
	// users who hold multiple parent-level memberships at once.
	$levels_without_groups = array();
	if ( function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
		$user_levels = pmpro_getMembershipLevelsForUser( $user->ID );
		if ( ! empty( $user_levels ) ) {
			$existing_parent_level_ids = wp_list_pluck( $groups, 'group_parent_level_id' );
			foreach ( $user_levels as $user_level ) {
				$level_settings = pmprogroupacct_get_settings_for_level( $user_level->id );
				if ( empty( $level_settings ) || empty( $level_settings['child_level_ids'] ) ) {
					continue;
				}
				if ( in_array( (int)$user_level->id, array_map( 'intval', $existing_parent_level_ids ), true ) ) {
					continue;
				}
				$levels_without_groups[] = $user_level;
			}
		}
	}

	// Show the UI.
	?>
	<h3><?php esc_html_e( 'Manage Groups', 'pmpro-group-accounts' ); ?></h3>
	<?php
	if ( empty( $groups ) ) {
		if ( empty( $levels_without_groups ) ) {
			echo '<p>' . esc_html__( 'This user does not manage any groups.', 'pmpro-group-accounts' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'This user has a parent-eligible membership level but does not yet have a group. Generate a group below to provide them with a checkout code.', 'pmpro-group-accounts' ) . '</p>';
		}
	} else {
		// Show the groups that the user manages.
		?>
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Group ID', 'pmpro-group-accounts' ); ?></th>
					<th><?php esc_html_e( 'Parent Level', 'pmpro-group-accounts' ); ?></th>
					<th><?php esc_html_e( 'Group Code', 'pmpro-group-accounts' ); ?></th>
					<th><?php esc_html_e( 'Group Levels', 'pmpro-group-accounts' ); ?></th>
					<th><?php esc_html_e( 'Seats', 'pmpro-group-accounts' ); ?></th>
					<th><?php esc_html_e( 'Manage Group', 'pmpro-group-accounts' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $groups as $group ) {
					$parent_level = pmpro_getLevel( $group->group_parent_level_id );
					// If the parent level is not found, skip this group.
					if ( empty( $parent_level ) ) {
						continue;
					}
					?>
					<tr>
						<th><?php echo esc_html( $group->id ); ?></th>
						<td><?php echo esc_html( $parent_level->name ); ?></td>
						<td><?php echo esc_html( $group->group_checkout_code ); ?></td>
						<td>
						<?php
							$group_settings = pmprogroupacct_get_settings_for_level( $group->group_parent_level_id );
							$child_level_links = array();
							foreach ( $group_settings['child_level_ids'] as $child_level_id ) {
								if ( ! empty( $pmpro_pages['checkout'] ) ) {
									$child_level = pmpro_getLevel( $child_level_id );
									$child_level_links[] = '<a target="_blank" href="' . esc_url( add_query_arg( array( 'level' => $child_level->id, 'pmprogroupacct_group_code' => $group->group_checkout_code ), pmpro_url( 'checkout' ) ) ) . '">' . esc_html( $child_level->name ) . '</a>';
								}
							}
							if ( $child_level_links ) {
								// Echo imploded level names and escape allowing links.
								echo wp_kses( implode( ', ', $child_level_links ), array( 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) );
							} else {
								esc_html_e( 'None', 'pmpro-group-accounts' );
							}
						?>
						</td>
						<td><?php echo esc_html( number_format_i18n( $group->get_active_members( true ) ) ) . '/' . esc_html( number_format_i18n( $group->group_total_seats ) ); ?></td>
						<td>
							<?php
							$manage_group_url = pmpro_url( 'pmprogroupacct_manage_group' );
							if ( ! empty( $manage_group_url ) ) {
								?>
								<a href="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, $manage_group_url ) ); ?>"><?php esc_html_e( 'Manage Group', 'pmpro-group-accounts' ); ?></a>
								<?php
							} else {
								esc_html_e( 'Page not set.', 'pmpro-group-accounts' );
							}
							?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}

	// Show a "Generate Group" form for each parent-eligible level the user holds that has no group yet.
	if ( ! empty( $levels_without_groups ) ) {
		pmprogroupacct_render_generate_group_forms( $user, $levels_without_groups );
	}
	?>
	<h3><?php esc_html_e( 'Manage Child Memberships', 'pmpro-group-accounts' ); ?></h3>
	<?php
	if ( empty( $group_members ) ) {
		echo '<p>' . esc_html__( 'This user has not been a member of any groups.', 'pmpro-group-accounts' ) . '</p>';
	} else {
		// Show the groups that the user is a member of.
		?>
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Group ID', 'pmpro-group-accounts' ); ?></th>
					<th><?php esc_html_e( 'Group Owner', 'pmpro-group-accounts' ); ?></th>
					<th><?php esc_html_e( 'Level ID', 'pmpro-group-accounts' ); ?></th>
					<th><?php esc_html_e( 'Status', 'pmpro-group-accounts' ); ?></th>
					<th><?php esc_html_e( 'Manage Group', 'pmpro-group-accounts' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $group_members as $group_member ) {
					$group            = new PMProGroupAcct_Group( (int)$group_member->group_id );
					$parent_user      = get_userdata( $group->group_parent_user_id );
					?>
					<tr>
						<th><?php echo esc_html( $group->id ); ?></th>
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
						<td><?php echo esc_html( $group_member->group_child_level_id ); ?></td>
						<td><?php echo esc_html( $group_member->group_child_status ); ?></td>
						<td>
							<?php
							$manage_group_url = pmpro_url( 'pmprogroupacct_manage_group' );
							if ( ! empty( $manage_group_url ) ) {
								?>
								<a href="<?php echo esc_url( add_query_arg( 'pmprogroupacct_group_id', $group->id, $manage_group_url ) ); ?>"><?php esc_html_e( 'Manage Group', 'pmpro-group-accounts' ); ?></a>
								<?php
							} else {
								esc_html_e( 'Page not set.', 'pmpro-group-accounts' );
							}
							?>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table> 
		<?php
	}
}

/**
 * Handle the "Generate Group" form submission on the Edit Member panel.
 *
 * Runs early on admin_init so that we can process the POST before PMPro's
 * main member-save flow runs — otherwise auto-created "free groups" can
 * appear before our handler and swallow the admin-entered seat count.
 *
 * If a group already exists for the user/level (e.g. was just auto-created
 * with 0 seats), we update its seat count instead of creating a new one.
 *
 * @since 1.5.3
 */
function pmprogroupacct_maybe_handle_generate_group_form() {
	if ( empty( $_POST['pmprogroupacct_generate_group_submit'] ) || empty( $_POST['pmprogroupacct_generate_group_level_id'] ) ) {
		return;
	}

	$level_id = (int) $_POST['pmprogroupacct_generate_group_level_id'];
	$user_id  = isset( $_POST['pmprogroupacct_generate_group_user_id'] ) ? (int) $_POST['pmprogroupacct_generate_group_user_id'] : 0;

	if ( empty( $user_id ) || empty( $level_id ) ) {
		return;
	}

	if ( ! current_user_can( 'pmpro_edit_members' ) && ! current_user_can( 'edit_users' ) ) {
		return;
	}

	if ( empty( $_POST['pmprogroupacct_generate_group_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['pmprogroupacct_generate_group_nonce'] ), 'pmprogroupacct_generate_group_' . $user_id . '_' . $level_id ) ) {
		pmprogroupacct_set_generate_group_notice( 'error', __( 'Unable to generate group: security check failed.', 'pmpro-group-accounts' ) );
		return;
	}

	// Confirm the user still has this level and that the level is a parent level.
	if ( ! pmpro_hasMembershipLevel( $level_id, $user_id ) ) {
		pmprogroupacct_set_generate_group_notice( 'error', __( 'Unable to generate group: this user does not have that membership level.', 'pmpro-group-accounts' ) );
		return;
	}
	$settings = pmprogroupacct_get_settings_for_level( $level_id );
	if ( empty( $settings ) || empty( $settings['child_level_ids'] ) ) {
		pmprogroupacct_set_generate_group_notice( 'error', __( 'Unable to generate group: that level is not configured for group accounts.', 'pmpro-group-accounts' ) );
		return;
	}

	$seats = isset( $_POST['pmprogroupacct_generate_group_seats'] ) ? max( 0, (int) $_POST['pmprogroupacct_generate_group_seats'] ) : 0;

	// If a group already exists (e.g. auto-created as an empty free group), update its seats.
	$existing_group = PMProGroupAcct_Group::get_group_by_parent_user_id_and_parent_level_id( $user_id, $level_id );
	if ( ! empty( $existing_group ) ) {
		$existing_group->update_group_total_seats( $seats );
		pmprogroupacct_set_generate_group_notice( 'success', __( 'Group seats updated successfully.', 'pmpro-group-accounts' ) );
	} else {
		$group = PMProGroupAcct_Group::create( $user_id, $level_id, $seats );
		if ( empty( $group ) ) {
			pmprogroupacct_set_generate_group_notice( 'error', __( 'Unable to generate group. Please try again.', 'pmpro-group-accounts' ) );
			return;
		}
		pmprogroupacct_set_generate_group_notice( 'success', __( 'Group generated successfully.', 'pmpro-group-accounts' ) );
	}

	// Redirect to avoid the outer Edit Member form reprocessing the submission.
	$redirect_url = remove_query_arg( array( 'pmprogroupacct_generated' ) );
	$redirect_url = wp_get_referer() ? wp_get_referer() : $redirect_url;
	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_init', 'pmprogroupacct_maybe_handle_generate_group_form', 5 );

/**
 * Store a transient notice for the current user about the generate-group action.
 *
 * @since 1.5.3
 *
 * @param string $type    Either 'success' or 'error'.
 * @param string $message The message to display.
 */
function pmprogroupacct_set_generate_group_notice( $type, $message ) {
	set_transient( 'pmprogroupacct_generate_notice_' . get_current_user_id(), array( 'type' => $type, 'message' => $message ), 30 );
}

/**
 * Render (and clear) any pending notice set by the generate-group handler.
 *
 * @since 1.5.3
 */
function pmprogroupacct_maybe_render_generate_group_notice() {
	$key    = 'pmprogroupacct_generate_notice_' . get_current_user_id();
	$notice = get_transient( $key );
	if ( empty( $notice ) || empty( $notice['message'] ) ) {
		return;
	}
	delete_transient( $key );
	$class = $notice['type'] === 'success' ? 'notice-success' : 'notice-error';
	echo '<div class="notice ' . esc_attr( $class ) . '"><p>' . esc_html( $notice['message'] ) . '</p></div>';
}
