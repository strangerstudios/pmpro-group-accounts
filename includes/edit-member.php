<?php
/**
 * Functionality for the Edit Member or Edit User page to show group account information.
 * 
 */

/**
 * Add a panel to the Edit Member dashboard page.
 *
 * @since TBD
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
 * @since TBD
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
 * @since TBD
 *
 * @param WP_User $user The user object being viewed.
 */
function pmprogroupacct_show_group_account_info( $user ) {
	global $pmpro_pages;

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

	// Show the UI.
	?>
	<h3><?php esc_html_e( 'Manage Groups', 'pmpro-group-accounts' ); ?></h3>
	<?php
	if ( empty( $groups ) ) {
		echo '<p>' . esc_html__( 'This user does not manage any groups.', 'pmpro-group-accounts' ) . '</p>';
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
									echo '<a href="' . esc_url( pmprogroupacct_get_group_parent_user_edit_url( $parent_user ) ) . '">' . esc_html( $parent_user->user_login ) . '</a>';
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
