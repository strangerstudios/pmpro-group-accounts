<?php

/**
 * If the user is checking out for a group parent level, we want to let them
 * choose the number of seats to purchase if there is a variable amount. We
 * also want to show them the price per seat if there is one set and the levels
 * that group members will be able to claim.
 */
function pmprogroupacct_pmpro_checkout_boxes_parent() {
	// Get the level being checked out for.
	$level = pmpro_getLevelAtCheckout();

	// Get the group settings for this level.
	$settings = null;
	if ( ! empty( $level->id ) ) {
		$settings = pmprogroupacct_get_settings_for_level( $level->id );
	}

	// If there are no settings, then this is not a group parent level. Bail.
	if ( empty( $settings ) ) {
		return;
	}

	// Build the checkout box.
	// We can check if there are a variable amount of seats by checking if min_seats is equal to max_seats.
	// The seats option should be a number input defaulting to the minimum seats.
	?>
	<div class="pmpro_checkout">
		<hr />
		<h2>
			<?php esc_html_e( 'Group Account Information', 'pmpro-group-accounts' ); ?>
		</h2>
		<div class="pmpro_checkout-fields">
			<?php
			// Show seats.
			if ( $settings['min_seats'] === $settings['max_seats'] ) {
				?>
				<input type="hidden" name="pmprogroupacct_seats" value="<?php echo esc_attr( $settings['min_seats'] ); ?>" />
				<p class="pmpro_checkout-field pmpro_checkout-field-seats">
					<?php printf( esc_html__( 'You are purchasing %d seats.', 'pmpro-group-accounts' ), (int)$settings['min_seats'] ); ?>
				</p>
				<?php
			} else {
				?>
				<label for="pmprogroupacct_seats"><?php esc_html_e( 'Number of Seats', 'pmpro-group-accounts' ); ?></label>
				<input id="pmprogroupacct_seats" name="pmprogroupacct_seats" type="number" min="<?php echo esc_attr( $settings['min_seats'] ); ?>" max="<?php echo esc_attr( $settings['max_seats'] ); ?>" value="<?php echo esc_attr( $settings['min_seats'] ); ?>" />
				<p class="description"><?php printf( esc_html__( 'Choose the number of seats to purchase. You can purchase between %d and %d seats.', 'pmpro-group-accounts' ), (int)$settings['min_seats'], (int)$settings['max_seats'] ); ?></p>
				<?php
			}

			// Show pricing.
			switch ( $settings['pricing_model'] ) {
				case 'none':
					break;
				case 'fixed':
					?>
					<p class="pmpro_checkout-field pmpro_checkout-field-pricing">
						<?php
						switch ( $settings['price_application'] ) {
							case 'both':
								printf( esc_html__( 'The price per seat is %s.', 'pmpro-group-accounts' ), esc_html( pmpro_formatPrice( $settings['pricing_model_settings'] ) ) );
								break;
							case 'initial':
								printf( esc_html__( 'The price per seat is %s for the initial payment.', 'pmpro-group-accounts' ), esc_html( pmpro_formatPrice( $settings['pricing_model_settings'] ) ) );
								break;
							case 'recurring':
								printf( esc_html__( 'The price per seat is %s for recurring payments.', 'pmpro-group-accounts' ), esc_html( pmpro_formatPrice( $settings['pricing_model_settings'] ) ) );
								break;
						}
						?>
					</p>
					<?php
					break;
				case 'tiered':
					break;
				case 'volume':
					break;
				case 'dropdown':
					break;
			}

			// Show child levels.
			?>
			<p class="pmpro_checkout-field pmpro_checkout-field-child-levels">
				<?php
				$all_levels = pmpro_getAllLevels( true, true );
				$child_level_names = array();
				foreach ( $all_levels as $child_level ) {
					if ( in_array( $child_level->id, $settings['child_level_ids'] ) ) {
						$child_level_names[] = $child_level->name;
					}
				}
				echo esc_html( sprintf( _n( 'Group members will be able to claim the %s membership level.', 'Group members will be able to claim the following membership levels: %s', count( $child_level_names ) ,'pmpro-group-accounts' ), implode( ', ', $child_level_names ) ) );
				?>
		</div>
	</div>
	<?php
}
add_action( 'pmpro_checkout_boxes', 'pmprogroupacct_pmpro_checkout_boxes_parent' );

/**
 * If the user is checking out for a group parent level, we need to make sure
 * that their checkout selections are valid.
 *
 * @param bool $continue_checkout Whether or not to continue with checkout.
 * @return bool Whether or not to continue with checkout.
 */
function pmprogroupacct_pmpro_registration_checks_parent( $continue_checkout ) {
	// If there is already a checkout error, bail.
	if ( ! $continue_checkout ) {
		return $continue_checkout;
	}

	// Get the level being checked out for.
	$level = pmpro_getLevelAtCheckout();

	// Get the group settings for this level.
	$settings = null;
	if ( ! empty( $level->id ) ) {
		$settings = pmprogroupacct_get_settings_for_level( $level->id );
	}

	// If there are no settings, then this is not a group parent level. Bail.
	if ( empty( $settings ) ) {
		return $continue_checkout;
	}

	// Get the number of seats being purchased.
	$seats = isset( $_REQUEST['pmprogroupacct_seats'] ) ? intval( $_REQUEST['pmprogroupacct_seats'] ) : 0;

	// If the number of seats entered is not an integer, show an error.
	if ( $seats !== $_REQUEST['pmprogroupacct_seats'] ) {
		$continue_checkout = false;
		pmpro_setMessage( esc_html__( 'The number of seats must be a whole number.', 'pmpro-group-accounts' ), 'pmpro_error' );
	}

	// If the number of seats is less than the minimum, show an error.
	if ( $seats < $settings['min_seats'] ) {
		$continue_checkout = false;
		pmpro_setMessage( sprintf( esc_html__( 'You must purchase at least %d seats.', 'pmpro-group-accounts' ), (int)$settings['min_seats'] ), 'pmpro_error' );
	}

	// If the number of seats is greater than the maximum, show an error.
	if ( $seats > $settings['max_seats'] ) {
		$continue_checkout = false;
		pmpro_setMessage( sprintf( esc_html__( 'You cannot purchase more than %d seats.', 'pmpro-group-accounts' ), (int)$settings['max_seats'] ), 'pmpro_error' );
	}

	// Check if this parent already has a group for this level. If so, check if $seats is greater than the number of seats in the group.
	$group_search_params = array(
		'parent_user_id'  => get_current_user_id(),
		'parent_level_id' => $level->id,
	);
	$groups = PMProGroupAcct_Group::get_groups( $group_search_params );
	if ( ! empty( $groups ) ) {
		// Get the number of active members in this group.
		$member_search_params = array(
			'group_id' => $groups[0]->id,
			'status'   => 'active',
		);
		$members = PMProGroupAcct_Member::get_members( $member_search_params );

		// If there are not enough seats for all the active members, show an error.
		if ( $seats < count( $members ) ) {
			$continue_checkout = false;
			pmpro_setMessage( sprintf( esc_html__( 'There are currently %d members in your group. You must purchase at least that many seats.', 'pmpro-group-accounts' ), count( $members ) ), 'pmpro_error' );
		}
	}

	return $continue_checkout;
}

/**
 * If the user is checking out for a group parent level, we need to
 * add the seat price to the checkout level.
 *
 * @param object $level The level being checked out for.
 * @return object The level being checked out for.
 */
function pmprogroupacct_pmpro_checkout_level_parent( $level ) {
	// Get the group settings for this level.
	$settings = null;
	if ( ! empty( $level->id ) ) {
		$settings = pmprogroupacct_get_settings_for_level( $level->id );
	}

	// If there are no settings, then this is not a group parent level. Bail.
	if ( empty( $settings ) ) {
		return $level;
	}

	// Get the number of seats being purchased.
	$seats = isset( $_REQUEST['pmprogroupacct_seats'] ) ? intval( $_REQUEST['pmprogroupacct_seats'] ) : 0;

	// If the number of seats is not an integer, bail.
	if ( empty( $_REQUEST['pmprogroupacct_seats'] ) || $seats !== $_REQUEST['pmprogroupacct_seats'] ) {
		return $level;
	}

	// Get the price per seat.
	$seat_cost = 0;
	switch ( $settings['pricing_model'] ) {
		case 'none':
			break;
		case 'fixed':
			$seat_cost = $seats * (float)$settings['pricing_model_settings'];
			break;
		case 'tiered':
			break;
		case 'volume':
			break;
		case 'dropdown':
			break;
	}

	// If the price per seat is not a number or negative, bail.
	if ( ! is_numeric( $seat_cost ) || $seat_cost < 0 ) {
		return $level;
	}

	// Add the price per seat to the level based on the price application setting.
	switch ( $settings['price_application'] ) {
		case 'both':
			$level->initial_payment += $seat_cost;
			$level->billing_amount += $seat_cost;
			break;
		case 'initial':
			$level->initial_payment += $seat_cost;
			break;
		case 'recurring':
			$level->billing_amount += $seat_cost;
			break;
	}

	return $level;
}
add_filter( 'pmpro_checkout_level', 'pmprogroupacct_pmpro_checkout_level_parent' );

/**
 * If the user just completed checkout for a group parent level, we need to
 * create the group.
 *
 * @param int $user_id The ID of the user who just completed checkout.
 */
function pmprogroupacct_pmpro_after_checkout_parent( $user_id ) {
	// Get the level being checked out for.
	$level = pmpro_getLevelAtCheckout();

	// Get the group settings for this level.
	$settings = null;
	if ( ! empty( $level->id ) ) {
		$settings = pmprogroupacct_get_settings_for_level( $level->id );
	}

	// If there are no settings, then this is not a group parent level. Bail.
	if ( empty( $settings ) ) {
		return;
	}

	// Get the number of seats being purchased.
	$seats = isset( $_REQUEST['pmprogroupacct_seats'] ) ? intval( $_REQUEST['pmprogroupacct_seats'] ) : 0;

	// If the number of seats is not an integer, bail.
	if ( $seats !== $_REQUEST['pmprogroupacct_seats'] ) {
		return;
	}

	// Check if there is already a group for this user and level.
	$group_search_params = array(
		'parent_user_id'  => $user_id,
		'parent_level_id' => $level->id,
	);
	$groups = PMProGroupAcct_Group::get_groups( $group_search_params );
	if ( ! empty( $groups ) ) {
		// There is already a group for this user and level. Let's update the number of seats.
		$groups[0]->update_seats( $seats );
		return;
	} else {
		// There is not already a group for this user and level. Let's create one.
		PMProGroupAcct_Group::create( $user_id, $level->id, $seats );
	}
}
add_action( 'pmpro_after_checkout', 'pmprogroupacct_pmpro_after_checkout_parent' );
