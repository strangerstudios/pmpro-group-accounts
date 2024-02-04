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
	global $wpdb;

	// Make sure that $level_id is an integer.
	$level_id = intval( $level_id );

	// Get all `pmprogroupacct_settings` metadata for all levels.
	$settings = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT meta_value FROM $wpdb->pmpro_membership_levelmeta WHERE meta_key = %s",
			'pmprogroupacct_settings'
		)
	);

	// Check if any of the settings have $level_id in their `child_level_ids` array.
	foreach ( $settings as $setting ) {
		$setting = maybe_unserialize( $setting );
		if ( ! empty( $setting['child_level_ids'] ) && in_array( $level_id, $setting['child_level_ids'], true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Get the url for the group parent user's edit member or edit user page.
 *
 * @since TBD
 */
function pmprogroupacct_get_group_parent_user_edit_url( $parent_user ) {
	// Build the parent user link.
	if ( function_exists( 'pmpro_member_edit_get_panels' ) ) {
		$parent_user_edit_url = add_query_arg( array( 'page' => 'pmpro-member', 'user_id' => $parent_user->ID, 'pmpro_member_edit_panel' => 'group-accounts' ), admin_url( 'admin.php' ) );
	} else {
		$parent_user_edit_url = add_query_arg( 'user_id', $parent_user->ID, admin_url( 'user-edit.php' ) );
	}
	// Return the parent user edit URL.
	return $parent_user_edit_url;
}
