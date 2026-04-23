<?php
/**
 * Get the group account settings for a membership level.
 *
 * @since 1.0
 *
 * @param int $level_id The ID of the membership level to get the settings for.
 * @return array|null The group account settings for the membership level or null if there are none.
 */
function pmprogroupacct_get_settings_for_level( $level_id ) {
	// Check if the PMPro plugin is active.
	if ( ! function_exists( 'get_pmpro_membership_level_meta' ) ) {
		return null;
	}

	// Get the group account settings for the level.
	$settings = get_pmpro_membership_level_meta( $level_id, 'pmprogroupacct_settings', true );

	return empty( $settings ) ? null : $settings;
}

/**
 * Check if a level can be claimed using group codes.
 *
 * @since 1.0
 *
 * @param int $level_id The ID of the membership level to check.
 * @return bool True if the level can be claimed using group codes, false otherwise.
 */
function pmprogroupacct_level_can_be_claimed_using_group_codes( $level_id ) {
	static $all_settings = null;

	// Make sure that $level_id is an integer.
	$level_id = intval( $level_id );

	if ( null === $all_settings ) {
		global $wpdb;
		// Get all `pmprogroupacct_settings` metadata for all levels.
		$all_settings = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM $wpdb->pmpro_membership_levelmeta WHERE meta_key = %s",
				'pmprogroupacct_settings'
			)
		);
	}

	// Check if any of the settings have $level_id in their `child_level_ids` array.
	foreach ( $all_settings as $setting ) {
		$setting = maybe_unserialize( $setting );
		if ( ! empty( $setting['child_level_ids'] ) && in_array( $level_id, $setting['child_level_ids'], true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Get the url for a user's edit member or edit user page.
 *
 * @since 1.0.1
 *
 * @param WP_User $user The user object to get the edit URL for.
 * @return string The URL for the user's edit page.
 */
function pmprogroupacct_member_edit_url_for_user( $user ) {
	// Build the parent user link.
	if ( function_exists( 'pmpro_member_edit_get_panels' ) ) {
		$member_edit_url = add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => $user->ID, 'pmpro_member_edit_panel' => 'group-accounts' ), admin_url( 'admin.php' ) );
	} else {
		$member_edit_url = add_query_arg( 'user_id', $user->ID, admin_url( 'user-edit.php' ) );
	}
	// Return the parent user edit URL.
	return $member_edit_url;
}

/**
 * Render a "Generate Group" form for each parent-eligible level the user
 * holds that does not yet have a group associated with it.
 *
 * @since 1.5.3
 *
 * @param WP_User $user The user being edited.
 * @param array   $levels The user's parent-eligible levels without a group.
 */
function pmprogroupacct_render_generate_group_forms( $user, $levels ) {
	?>
	<h4><?php esc_html_e( 'Generate Parent Group', 'pmpro-group-accounts' ); ?></h4>
	<p class="description"><?php esc_html_e( 'Create a group and checkout code for this user so they can invite members.', 'pmpro-group-accounts' ); ?></p>
	<?php
	foreach ( $levels as $level ) {
		$settings      = pmprogroupacct_get_settings_for_level( $level->id );
		$min_seats     = ! empty( $settings['min_seats'] ) ? (int)$settings['min_seats'] : 0;
		$max_seats     = ! empty( $settings['max_seats'] ) ? (int)$settings['max_seats'] : 0;
		$default_seats = $max_seats > 0 ? $max_seats : $min_seats;
		$input_id      = 'pmprogroupacct_generate_seats_' . (int)$level->id;
		?>
		<form method="post" style="margin: 0 0 1em 0;">
			<?php wp_nonce_field( 'pmprogroupacct_generate_group_' . (int)$user->ID . '_' . (int)$level->id, 'pmprogroupacct_generate_group_nonce' ); ?>
			<input type="hidden" name="pmprogroupacct_generate_group_user_id" value="<?php echo esc_attr( (int)$user->ID ); ?>" />
			<input type="hidden" name="pmprogroupacct_generate_group_level_id" value="<?php echo esc_attr( (int)$level->id ); ?>" />
			<p><strong><?php echo esc_html( sprintf( __( 'Parent Level: %s', 'pmpro-group-accounts' ), $level->name ) ); ?></strong></p>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $input_id ); ?>"><?php esc_html_e( 'Number of Seats', 'pmpro-group-accounts' ); ?></label></th>
					<td>
						<input id="<?php echo esc_attr( $input_id ); ?>" type="number" min="0" max="4294967295" name="pmprogroupacct_generate_group_seats" value="<?php echo esc_attr( $default_seats ); ?>" />
						<?php submit_button( __( 'Generate Group', 'pmpro-group-accounts' ), 'secondary', 'pmprogroupacct_generate_group_submit', false ); ?>
						<?php if ( $min_seats || $max_seats ) { ?>
							<p class="description">
								<?php
								/* translators: 1: min seats, 2: max seats. */
								echo esc_html( sprintf( __( 'Level default range: %1$s to %2$s seats.', 'pmpro-group-accounts' ), number_format_i18n( $min_seats ), number_format_i18n( $max_seats ) ) );
								?>
							</p>
						<?php } ?>
					</td>
				</tr>
			</table>
		</form>
		<?php
	}
}
