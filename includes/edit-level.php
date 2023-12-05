<?php
/**
 * Add group account settings to the edit level page.
 *
 * @since TBD
 *
 * @param object $level The level object being edited.
 */
function pmprogroupacct_pmpro_membership_level_before_content_settings( $level ) {
	global $pmpro_currency_symbol;

	$settings = array(
		'child_level_ids'		 => array(),
		'min_seats'				 => 0,
		'max_seats'				 => 0,
		'pricing_model'			 => 'none', // none, fixed
		'pricing_model_settings' => 0,
		'price_application'		 => 'both', // initial, recurring, both
	);

	// Get the group account settings for the level.
	$saved_settings = pmprogroupacct_get_settings_for_level( $level->id );
	if ( ! empty( $saved_settings ) ) {
		$settings = array_merge( $settings, $saved_settings );
	}

	// Build the settings UI.
	?>
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Group Account Settings', 'paid-memberships-pro' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<p>
				<?php esc_html_e( 'Group accounts allow a member to purchase a block of memberships at once. The member will receive a code to distribute to their group for use during registration.', 'pmpro-group-accounts' ); ?>
				<a href="https://www.paidmembershipspro.com/add-ons/group-accounts?utm_source=plugin&utm_medium=pmpro-membershiplevels&utm_campaign=add-ons&utm_content=view-documentation" target="_blank"><?php esc_html_e( 'View documentation', 'pmpro-group-accounts' ); ?></a>
			</p>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top">
							<label for="pmprogroupacct_child_level_ids"><?php esc_html_e( 'Membership Level(s)', 'pmpro-group-accounts' ); ?></label>
						</th>
						<td>
							<select id="pmprogroupacct_child_level_ids" name="pmprogroupacct_child_level_ids[]" multiple="multiple">
								<?php
								// Get all membership levels except for the one being edited.
								$all_levels = pmpro_getAllLevels( true, true );
								unset( $all_levels[ $level->id ] );
								foreach ( $all_levels as $child_level ) { ?>
									<option value="<?php echo esc_attr( $child_level->id ); ?>" <?php selected( in_array( $child_level->id, $settings['child_level_ids'] ) ); ?>><?php echo esc_html( $child_level->name ); ?></option>
								<?php } ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select one or more membership levels that can be claimed by group members. Leave blank if this membership level does not offer child accounts.', 'pmpro-group-accounts' ); ?></p>
					</tr>
					<tr class="pmprogroupacct_setting">
						<th scope="row" valign="top">
							<label for="pmprogroupacct_group_type"><?php esc_html_e( 'Type of Group', 'pmpro-group-accounts' ); ?></label>
						</th>
						<td>
							<select id="pmprogroupacct_group_type" name="pmprogroupacct_group_type">
								<option value="fixed" <?php selected( $settings['min_seats'] === $settings['max_seats'] ); ?>><?php esc_html_e( 'Fixed - Set a specific number of allowed seats.', 'pmpro-group-accounts' ); ?></option>
								<option value="variable" <?php selected( $settings['min_seats'] !== $settings['max_seats'] ); ?>><?php esc_html_e( 'Variable - Member can choose number of seats at checkout.', 'pmpro-group-accounts' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Set a specific number of seats in the group or allow the member to choose the number of seats they need at checkout.', 'pmpro-group-accounts' ); ?></p>
						</td>
					</tr>
					<tr class="pmprogroupacct_setting pmprogroupacct_group_type_setting pmprogroupacct_group_type_setting_fixed">
						<th scope="row" valign="top">
							<label for="pmprogroupacct_total_seats"><?php esc_html_e( 'Total Seats', 'pmpro-group-accounts' ); ?></label>
						</th>
						<td>
							<input id="pmprogroupacct_total_seats" name="pmprogroupacct_total_seats" type="number" min="0" value="<?php echo esc_attr( $settings['min_seats'] ); ?>" />
							<p class="description"><?php esc_html_e( 'The total number of seats that are included in this group. Note: the group account owner does not count toward this total.', 'pmpro-group-accounts' ); ?></p>
						</td>
					</tr>
					<tr class="pmprogroupacct_setting pmprogroupacct_group_type_setting pmprogroupacct_group_type_setting_variable">
						<th scope="row" valign="top">
							<label for="pmprogroupacct_min_seats"><?php esc_html_e( 'Minimum Seats', 'pmpro-group-accounts' ); ?></label>
						</th>
						<td>
							<input id="pmprogroupacct_min_seats" name="pmprogroupacct_min_seats" type="number" min="0" value="<?php echo esc_attr( $settings['min_seats'] ); ?>" />
							<p class="description"><?php esc_html_e( 'The minimum number of seats that can be added at checkout.', 'pmpro-group-accounts' ); ?></p>
						</td>
					</tr>
					<tr class="pmprogroupacct_setting pmprogroupacct_group_type_setting pmprogroupacct_group_type_setting_variable">
						<th scope="row" valign="top">
							<label for="pmprogroupacct_max_seats"><?php esc_html_e( 'Maximum Seats', 'pmpro-group-accounts' ); ?></label>
						</th>
						<td>
							<input id="pmprogroupacct_max_seats" name="pmprogroupacct_max_seats" type="number" min="0" value="<?php echo esc_attr( $settings['max_seats'] ); ?>" />
							<p class="description"><?php esc_html_e( 'The maximum number of seats that can be added at checkout. Note: the group account owner does not count toward this limit.', 'pmpro-group-accounts' ); ?></p>
						</td>
					</tr>
					<tr class="pmprogroupacct_setting">
						<th scope="row" valign="top">
							<label for="pmprogroupacct_pricing_model"><?php esc_html_e( 'Pricing Model', 'pmpro-group-accounts' ); ?></label>
						</th>
						<td>
							<select id="pmprogroupacct_pricing_model" name="pmprogroupacct_pricing_model">
								<option value="none" <?php selected( 'none', $settings['pricing_model'] ); ?>><?php esc_html_e( 'None - Group pricing is built into this membership level.', 'pmpro-group-accounts' ); ?></option>
								<option value="fixed" <?php selected( 'fixed', $settings['pricing_model'] ); ?>><?php esc_html_e( 'Per Seat - Set a specific price per additional seat.', 'pmpro-group-accounts' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'The pricing model to use for purchasing seats.', 'pmpro-group-accounts' ); ?></p>
							<div id="pmprogroupacct_pricing_model_warning_free_level" style="display: none;" class="pmpro_message pmpro_alert">
								<p><?php esc_html_e( 'WARNING: This level does not have any pricing set up. We highly recommend that you set up an initial payment or recurring billing for a better checkout experience.', 'pmpro-group-accounts' ); ?></p>
							</div>
						</td>
					</tr>
					<tr class="pmprogroupacct_setting pmprogroupacct_pricing_setting pmprogroupacct_pricing_setting_fixed">
						<th scope="row" valign="top">
							<label for="pmprogroupacct_pricing_model_settings"><?php esc_html_e( 'Cost Per Seat', 'pmpro-group-accounts' ); ?></label>
						</th>
						<td>
							<?php
							if ( pmpro_getCurrencyPosition() == "left" )
								echo $pmpro_currency_symbol;
							?>
							<input name="pmprogroupacct_pricing_model_settings" type="text" value="<?php echo esc_attr( pmpro_filter_price_for_text_field( $settings['pricing_model_settings'] ) ); ?>" class="regular-text" />
							<?php
							if ( pmpro_getCurrencyPosition() == "right" )
								echo $pmpro_currency_symbol;
							?>
							<p class="description"><?php esc_html_e( 'The additional cost at checkout per seat.', 'pmpro-group-accounts' ); ?></p>
						</td>
					</tr>
					<tr class="pmprogroupacct_setting pmprogroupacct_pricing_setting pmprogroupacct_pricing_setting_paid">
						<th scope="row" valign="top">
							<label for="pmprogroupacct_price_application"><?php esc_html_e( 'Price Application', 'pmpro-group-accounts' ); ?></label>
						</th>
						<td>
							<select id="pmprogroupacct_price_application" name="pmprogroupacct_price_application">
								<option value="both" <?php selected( 'both', $settings['price_application'] ); ?>><?php esc_html_e( 'Initial payment and recurring subscription', 'pmpro-group-accounts' ); ?></option>
								<option value="initial" <?php selected( 'initial', $settings['price_application'] ); ?>><?php esc_html_e( 'Initial payment only', 'pmpro-group-accounts' ); ?></option>
								<option value="recurring" <?php selected( 'recurring', $settings['price_application'] ); ?>><?php esc_html_e( 'Recurring subscription only', 'pmpro-group-accounts' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Define whether the seat cost should be applied for the initial payment, recurring payment, or both.', 'pmpro-group-accounts' ); ?></p>
							<div id="pmprogroupacct_pricing_model_warning_recurring_billing" style="display: none;" class="pmpro_message pmpro_alert">
								<p><?php esc_html_e( 'WARNING: This level does not have a recurring subscription. Child accounts will assume a monthly billing period unless you configure the subscription on this parent level.', 'pmpro-group-accounts' ); ?></p>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- end .pmpro_section_inside -->
	</div> <!-- end .pmpro_section -->
	<?php
}
add_action( 'pmpro_membership_level_before_content_settings', 'pmprogroupacct_pmpro_membership_level_before_content_settings' );

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
	$settings['child_level_ids']		= array_map( 'intval', $_REQUEST['pmprogroupacct_child_level_ids'] );

	// Set the total seats, min, and max based on group type and user selected values.
	if ( ! empty( $_REQUEST['pmprogroupacct_group_type'] ) && $_REQUEST['pmprogroupacct_group_type'] === 'fixed' ) {
		$settings['min_seats'] = intval( $_REQUEST['pmprogroupacct_total_seats'] );
		$settings['max_seats'] = intval( $_REQUEST['pmprogroupacct_total_seats'] );
	} else {
		$settings['min_seats'] = intval( $_REQUEST['pmprogroupacct_min_seats'] );
		$settings['max_seats'] = intval( $_REQUEST['pmprogroupacct_max_seats'] );
	}

	// Settings for seat pricing and price application.
	$settings['pricing_model']			= pmpro_sanitize_with_safelist( $_REQUEST['pmprogroupacct_pricing_model'], array( 'none', 'fixed' ) ) ? $_REQUEST['pmprogroupacct_pricing_model'] : 'none';
	// Set the pricing model setting to 0 if the pricing model is none.
	if ( $settings['pricing_model'] === 'none' ) {
		$settings['pricing_model_settings']	= '0';
	} else {
		$settings['pricing_model_settings']	= sanitize_text_field( $_REQUEST['pmprogroupacct_pricing_model_settings'] );
	}
	$settings['price_application']		= pmpro_sanitize_with_safelist( $_REQUEST['pmprogroupacct_price_application'], array( 'both', 'initial', 'recurring' ) ) ? $_REQUEST['pmprogroupacct_price_application'] : 'both';
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