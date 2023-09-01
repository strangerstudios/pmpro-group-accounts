<?php

/**
 * Add group account settings to the edit level page.
 *
 * @since TBD
 *
 * @param object $level The level object being edited.
 */
function pmprogroupacct_pmpro_membership_level_after_other_settings( $level ) {
	$settings = array(
		'child_level_ids'        => array(),
		'min_seats'              => 0,
		'max_seats'              => 0,
		'pricing_model'          => 'none',
		'pricing_model_settings' => null,
		'price_application'      => 'both', // initial, recurring, both
	);

	// Get the group account settings for the level.
	$saved_settings = pmprogroupacct_get_settings_for_level( $level->id );
	if ( ! empty( $saved_settings ) ) {
		$settings = array_merge( $settings, $saved_settings );
	}

	// Build the settings UI.
	// TODO: Finish implementing the pricing model settings.
	?>
	<h2 class="topborder"><?php esc_html_e( 'Group Account Settings', 'pmpro-group-accounts' ); ?></h2>
	<table>
		<tbody class="form-table">
			<tr>
				<th scope="row" valign="top">
					<label for="pmprogroupacct_child_level_ids"><?php esc_html_e( 'Member Levels', 'pmpro-group-accounts' ); ?></label>
				</th>
				<td>
					<select id="pmprogroupacct_child_level_ids" name="pmprogroupacct_child_level_ids[]" multiple="multiple" style="width:50%">
						<?php
						// Get all membership levels except for the one being edited.
						$all_levels = pmpro_getAllLevels( true, true );
						unset( $all_levels[ $level->id ] );
						foreach ( $all_levels as $child_level ) { ?>
							<option value="<?php echo esc_attr( $child_level->id ); ?>" <?php selected( in_array( $child_level->id, $settings['child_level_ids'] ) ); ?>><?php echo esc_html( $child_level->name ); ?></option>
						<?php } ?>
					</select>
					<p class="description"><?php esc_html_e( 'Select the membership levels that can be claimed by group members.', 'pmpro-group-accounts' ); ?></p>
			</tr>
			<tr class="pmprogroupacct_setting">
				<th scope="row" valign="top">
					<label for="pmprogroupacct_min_seats"><?php esc_html_e( 'Minimum Seats', 'pmpro-group-accounts' ); ?></label>
				</th>
				<td>
					<input id="pmprogroupacct_min_seats" name="pmprogroupacct_min_seats" type="number" min="0" value="<?php echo esc_attr( $settings['min_seats'] ); ?>" />
					<p class="description"><?php esc_html_e( 'The minimum number of seats that can be purchased at checkout.', 'pmpro-group-accounts' ); ?></p>
				</td>
			</tr>
			<tr class="pmprogroupacct_setting">
				<th scope="row" valign="top">
					<label for="pmprogroupacct_max_seats"><?php esc_html_e( 'Maximum Seats', 'pmpro-group-accounts' ); ?></label>
				</th>
				<td>
					<input id="pmprogroupacct_max_seats" name="pmprogroupacct_max_seats" type="number" min="0" value="<?php echo esc_attr( $settings['max_seats'] ); ?>" />
					<p class="description"><?php esc_html_e( 'The maximum number of seats that can be purchased at checkout. If this is different than "Minimum Seats", users will be able to choose how many seats to purchase at checkout.', 'pmpro-group-accounts' ); ?></p>
				</td>
			</tr>
			<tr class="pmprogroupacct_setting">
				<th scope="row" valign="top">
					<label for="pmprogroupacct_pricing_model"><?php esc_html_e( 'Pricing Model', 'pmpro-group-accounts' ); ?></label>
				</th>
				<td>
					<select id="pmprogroupacct_pricing_model" name="pmprogroupacct_pricing_model">
						<option value="none" <?php selected( 'none', $settings['pricing_model'] ); ?>><?php esc_html_e( 'None', 'pmpro-group-accounts' ); ?></option>
						<option value="fixed" <?php selected( 'fixed', $settings['pricing_model'] ); ?>><?php esc_html_e( 'Fixed', 'pmpro-group-accounts' ); ?></option>
						<option value="tiered" <?php selected( 'tiered', $settings['pricing_model'] ); ?>><?php esc_html_e( 'Tiered', 'pmpro-group-accounts' ); ?></option>
						<option value="volume" <?php selected( 'volume', $settings['pricing_model'] ); ?>><?php esc_html_e( 'Volume', 'pmpro-group-accounts' ); ?></option>
						<option value="dropdown" <?php selected( 'dropdown', $settings['pricing_model'] ); ?>><?php esc_html_e( 'Dropdown', 'pmpro-group-accounts' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'The pricing model to use for purchasing seats.', 'pmpro-group-accounts' ); ?></p>
				</td>
			</tr>
			<tr class="pmprogroupacct_setting pmprogroupacct_pricing_setting pmprogroupacct_pricing_setting_fixed">
				<th scope="row" valign="top">
					<label for="pmprogroupacct_pricing_model_settings"><?php esc_html_e( 'Cost Per Seat', 'pmpro-group-accounts' ); ?></label>
				</th>
				<td>
					$ <input name="pmprogroupacct_pricing_model_settings" type="text" value="<?php echo is_numeric( $settings['pricing_model_settings'] ) ? esc_attr( $settings['pricing_model_settings'] ) : ''; ?>" />
					<p class="description"><?php esc_html_e( 'The additional cost at checkout per seat.', 'pmpro-group-accounts' ); ?></p>
				</td>
			</tr>
			<tr class="pmprogroupacct_setting pmprogroupacct_pricing_setting pmprogroupacct_pricing_setting_paid">
				<th scope="row" valign="top">
					<label for="pmprogroupacct_price_application"><?php esc_html_e( 'Price Application', 'pmpro-group-accounts' ); ?></label>
				</th>
				<td>
					<select id="pmprogroupacct_price_application" name="pmprogroupacct_price_application">
						<option value="both" <?php selected( 'both', $settings['price_application'] ); ?>><?php esc_html_e( 'Both Initial and Recurring Payments', 'pmpro-group-accounts' ); ?></option>
						<option value="initial" <?php selected( 'initial', $settings['price_application'] ); ?>><?php esc_html_e( 'Initial Payment', 'pmpro-group-accounts' ); ?></option>
						<option value="recurring" <?php selected( 'recurring', $settings['price_application'] ); ?>><?php esc_html_e( 'Recurring Payment', 'pmpro-group-accounts' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Whether seat costs should be applied for initial payments, recurring payments, or both.', 'pmpro-group-accounts' ); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmprogroupacct_pmpro_membership_level_after_other_settings' );

/**
 * Save group account settings when the level is saved.
 *
 * @since TBD
 *
 * @param int $level_id The ID of the level being saved.
 */
function pmprogroupacct_pmpro_save_membership_level( $level_id ) {
	// Validate the passed data.
	if ( empty( $_REQUEST['pmprogroupacct_child_level_ids'] ) ) {
		// This is not a group account level. Clear any existing settings.
		delete_pmpro_membership_level_meta( $level_id, 'pmprogroupacct_settings' );
		return;
	}

	// Get the group account settings for the level.
	$settings = pmprogroupacct_get_settings_for_level( $level_id );
	if ( empty( $settings ) ) {
		$settings = array();
	}

	// Update the group account settings for the level.
	$settings['child_level_ids']        = array_map( 'intval', $_REQUEST['pmprogroupacct_child_level_ids'] );
	$settings['min_seats']              = intval( $_REQUEST['pmprogroupacct_min_seats'] );
	$settings['max_seats']              = intval( $_REQUEST['pmprogroupacct_max_seats'] );
	$settings['pricing_model']          = pmpro_sanitize_with_safelist( $_REQUEST['pmprogroupacct_pricing_model'], array( 'none', 'fixed', 'tiered', 'volume', 'dropdown' ) ) ? $_REQUEST['pmprogroupacct_pricing_model'] : 'none';
	$settings['pricing_model_settings'] = sanitize_textarea_field( $_REQUEST['pmprogroupacct_pricing_model_settings'] ); // TODO: Validate based on input types.
	$settings['price_application']      = pmpro_sanitize_with_safelist( $_REQUEST['pmprogroupacct_price_application'], array( 'both', 'initial', 'recurring' ) ) ? $_REQUEST['pmprogroupacct_price_application'] : 'both';
	update_pmpro_membership_level_meta( $level_id, 'pmprogroupacct_settings', $settings );
}
add_action( 'pmpro_save_membership_level', 'pmprogroupacct_pmpro_save_membership_level' );

/**
 * Delete group account settings when the level is deleted.
 *
 * @since TBD
 *
 * @param int $level_id The ID of the level being deleted.
 */
function pmprogroupacct_pmpro_delete_membership_level( $level_id ) {
	delete_pmpro_membership_level_meta( $level_id, 'pmprogroupacct_settings' );
}
add_action( 'pmpro_delete_membership_level', 'pmprogroupacct_pmpro_delete_membership_level' );