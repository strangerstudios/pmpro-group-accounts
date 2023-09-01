<?php

/**
 * Get the group account settings for a membesrhip level.
 *
 * @since TBD
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
