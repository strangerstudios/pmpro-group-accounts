<?php
/**
 * When administrators edit a user, we want to show all groups that they manage,
 * including showing the group ID, the level ID for the group, the number of seats in the group,
 * and a link to manage the group if the "Manage Group" page is set.
 *
 * We also want to show a table of all groups that the user is a member of, including
 * links to the group owner, the level that they claimed with the group, and the group member status.
 *
 * @since 1.0
 *
 * @param WP_User $user The user object being edited.
 */
function pmprogroupacct_after_membership_level_profile_fields( $user ) {
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
	<h2><?php esc_html_e( 'Group Accounts', 'pmpro-group-accounts' ); ?></h2>
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
					$parent_user_link = empty( $parent_user ) ? esc_html( $group->group_parent_user_id ) : '<a href="' . esc_url( add_query_arg( 'user_id', $parent_user->ID, admin_url( 'user-edit.php' ) ) ) . '">' . esc_html( $parent_user->user_login ) . '</a>';
					?>
					<tr>
						<th><?php echo esc_html( $group->id ); ?></th>
						<td><?php echo $parent_user_link ?></td>
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
add_action( 'pmpro_after_membership_level_profile_fields', 'pmprogroupacct_after_membership_level_profile_fields' );
