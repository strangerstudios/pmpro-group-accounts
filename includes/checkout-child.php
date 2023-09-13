<?php

/**
 * If a group code is passed and no other checkout messages are being shown, show a message that the group code has been applied if
 * the code is valid or an error message if not.
 *
 * @since TBD
 */
function pmprogroupacct_checkout_before_form_child() {
	// Get the level being checked out for.
	$checkout_level = pmpro_getLevelAtCheckout();
	if ( empty( $checkout_level ) ) {
		return;
	}

	// Check if this level can be claimed with a group code.
	if ( ! pmprogroupacct_level_can_be_claimed_using_group_codes( $checkout_level->id ) ) {
		return;
	}

	// Check if a group code was already passed.
	$group_code = isset( $_REQUEST['pmprogroupacct_group_code'] ) ? sanitize_text_field( $_REQUEST['pmprogroupacct_group_code'] ) : '';
	if ( ! empty( $group_code ) ) {
		// Check if the group code is valid.
		$group = PMProGroupAcct_Group::get_group_by_checkout_code( $group_code );
		if ( ! empty( $group ) && $group->is_accepting_signups() ) {
			// Show a message that the group code has been applied.
			pmpro_setMessage( esc_html__( 'Group code applied.', 'pmpro-group-accounts' ), 'pmpro_success' );
		} elseif ( ! empty( $group ) ) {
			// Show a message that the group code is not accepting signups.
			pmpro_setMessage( esc_html__( 'This group is no longer accepting signups.', 'pmpro-group-accounts' ), 'pmpro_error' );
		} else {
			// Show a message that the group code is invalid.
			pmpro_setMessage( esc_html__( 'Invalid group code.', 'pmpro-group-accounts' ), 'pmpro_error' );
		}
	}
}
add_action( 'pmpro_checkout_before_form', 'pmprogroupacct_checkout_before_form_child' );

/**
 * If this level can be claimed with a group code and one is not being passed via URL, show a field for the user to enter one.
 * When submitted, we will redirect to the checkout page with the group code in the URL.
 *
 * If a group code is already being passed via URL, we will check if it is valid and show a message to the user accordingly.
 *
 * @since TBD
 */
function pmprogroupacct_checkout_after_level_cost_child() {
	// Get the level being checked out for.
	$checkout_level = pmpro_getLevelAtCheckout();
	if ( empty( $checkout_level ) ) {
		return;
	}
	// Check if this level can be claimed with a group code.
	if ( ! pmprogroupacct_level_can_be_claimed_using_group_codes( $checkout_level->id ) ) {
		return;
	}

	// Check if a valid group code was already passed.
	$group_code = isset( $_REQUEST['pmprogroupacct_group_code'] ) ? sanitize_text_field( $_REQUEST['pmprogroupacct_group_code'] ) : '';
	if ( ! empty( $group_code ) ) {
		// Check if the group code is valid.
		$group = PMProGroupAcct_Group::get_group_by_checkout_code( $group_code );
		if ( ! empty( $group ) && $group->is_accepting_signups() ) {
			// Add a hidden field to the checkout form with the group code and return so that we don't show the group code field.
			?>
			<p><?php esc_html_e( 'You have applied the following group code', 'pmpro-group-accounts' ); ?>: <?php echo esc_html( $group_code ); ?></p>
			<input type="hidden" name="pmprogroupacct_group_code" value="<?php echo esc_attr( $group_code ); ?>" />
			<?php
			return;
		}
	}

	// Show the group code field along with a button to apply the code by redirecting to this page with the code in the URL.
	?>
	<p>
		<label for="pmprogroupacct_group_code"><?php esc_html_e( 'Group Code', 'pmpro-group-accounts' ); ?></label>
		<input id="pmprogroupacct_group_code" name="pmprogroupacct_group_code" type="text" />
		<button type="button" id="pmprogroupacct_apply_group_code" class="pmpro_btn"><?php esc_html_e( 'Apply', 'pmpro-group-accounts' ); ?></button>
	</p>
	<?php
}
add_action( 'pmpro_checkout_after_level_cost', 'pmprogroupacct_checkout_after_level_cost_child' );

/**
 * If a valid group code is being used, we need to reduce the level cost to 0.
 *
 * @since TBD
 *
 * @param object $level The level being checked out for.
 * @return object The level being checked out for.
 */
function pmprogroupacct_pmpro_checkout_level_child( $level ) {
	// Check if this level can be claimed with a group code.
	if ( ! pmprogroupacct_level_can_be_claimed_using_group_codes( $level->id ) ) {
		return $level;
	}

	// Check if a valid group code was already passed.
	$group_code = isset( $_REQUEST['pmprogroupacct_group_code'] ) ? sanitize_text_field( $_REQUEST['pmprogroupacct_group_code'] ) : '';
	if ( ! empty( $group_code ) ) {
		// Check if the group code is valid.
		$group = PMProGroupAcct_Group::get_group_by_checkout_code( $group_code );
		if ( ! empty( $group ) && $group->is_accepting_signups() ) {
			// Set the level cost to 0.
			$level->initial_payment = 0;
			$level->billing_amount  = 0;
		}
	}

	return $level;
}
add_filter( 'pmpro_checkout_level', 'pmprogroupacct_pmpro_checkout_level_child' );

/**
 * If a group code is being used, we need to make sure that the code is valid and
 * that the current user is allowed to use this code before letting checkouts go through.
 *
 * @since TBD
 *
 * @param bool $pmpro_continue_registration Whether or not to continue with the checkout.
 * @return bool Whether or not to continue with the checkout.
 */
function pmprogroupacct_pmpro_registration_checks_child( $pmpro_continue_registration ) {
	// If there is already a checkout error, bail.
	if ( ! $pmpro_continue_registration ) {
		return $pmpro_continue_registration;
	}

	// Get the level being checked out for.
	$checkout_level = pmpro_getLevelAtCheckout();
	if ( empty( $checkout_level ) ) {
		return $pmpro_continue_registration;
	}

	// Check if this level can be claimed with a group code.
	if ( ! pmprogroupacct_level_can_be_claimed_using_group_codes( $checkout_level->id ) ) {
		return $pmpro_continue_registration;
	}

	// Check if a valid group code is being used.
	$group_code = isset( $_REQUEST['pmprogroupacct_group_code'] ) ? sanitize_text_field( $_REQUEST['pmprogroupacct_group_code'] ) : '';
	if ( empty( $group_code ) ) {
		// No group code is being used, so we're good.
		return $pmpro_continue_registration;
	}

	// Check if this code is a valid group code.
	$group = PMProGroupAcct_Group::get_group_by_checkout_code( $group_code );
	if ( empty( $group ) ) {
		// This is not a valid group code. Show an error message.
		pmpro_setMessage( esc_html__( 'Invalid group code.', 'pmpro-group-accounts' ), 'pmpro_error' );
		return false;
	}

	// Check if this group is accepting signups.
	if ( ! $group->is_accepting_signups() ) {
		// This group is not accepting signups. Show an error message.
		pmpro_setMessage( esc_html__( 'This group is no longer accepting signups.', 'pmpro-group-accounts' ), 'pmpro_error' );
		return false;
	}

	// Check if the current user is the parent user of this group. If so, they can't use this code.
	if ( $group->group_parent_user_id === get_current_user_id() ) {
		// This user is the parent user of this group. Show an error message.
		pmpro_setMessage( esc_html__( 'You cannot use your own group code.', 'pmpro-group-accounts' ), 'pmpro_error' );
		return false;
	}

	// Check if this user is already a member of this group in active status.
	$group_member_query_args = array(
		'group_id'            => $group->id,
		'group_child_user_id' => get_current_user_id(),
		'group_child_status'  => 'active',
	);
	$group_members = PMProGroupAcct_Group_Member::get_group_members( $group_member_query_args );
	if ( ! empty( $group_members ) ) {
		// This user is already a member of this group in active status. Show an error message.
		pmpro_setMessage( esc_html__( 'You are already a member of this group.', 'pmpro-group-accounts' ), 'pmpro_error' );
		return false;
	}

	return $pmpro_continue_registration;
}
add_filter( 'pmpro_registration_checks', 'pmprogroupacct_pmpro_registration_checks_child' );

/**
 * If a group code was used during checkout, we need to add the user to the group afterwards.
 *
 * Similarly, if the user was already a member of a group using this level, we need to remove them from that group.
 *
 * @since TBD
 *
 * @param int $user_id The ID of the user being checked out for.
 * @param MemberOrder $morder The order being checked out for.
 */
function pmprogroupacct_pmpro_after_checkout_child( $user_id, $morder ) {
	// Get the level that was purchased from the order.
	$checkout_level_id = $morder->membership_id;

	// Check if the current user was already a member of a group using this level.
	// If so, remove them from that group.
	$group_member_query_args = array(
		'group_child_user_id'  => $user_id,
		'group_child_level_id' => $checkout_level_id,
		'group_child_status'   => 'active',
	);
	$group_members = PMProGroupAcct_Group_Member::get_group_members( $group_member_query_args );
	if ( ! empty( $group_members ) ) {
		// This user was already a member of a group using this level. Remove them from that group.
		foreach ( $group_members as $group_member ) {
			$group_member->update_group_child_status( 'inactive' );
		}
	}

	// Check if this level can be claimed with a group code.
	if ( ! pmprogroupacct_level_can_be_claimed_using_group_codes( $checkout_level_id ) ) {
		return;
	}

	// Check if a valid group code was used.
	$group_code = isset( $_REQUEST['pmprogroupacct_group_code'] ) ? sanitize_text_field( $_REQUEST['pmprogroupacct_group_code'] ) : '';
	if ( empty( $group_code ) ) {
		// No group code was used, so we're good.
		return;
	}

	// Check if this code is a valid group code.
	$group = PMProGroupAcct_Group::get_group_by_checkout_code( $group_code );
	if ( empty( $group ) ) {
		// This is not a valid group code. Bail.
		return;
	}

	// Check if the user had previously been a member of this group.
	$group_member_query_args = array(
		'group_id'           => $group->id,
		'group_child_user_id' => $user_id,
	);
	$group_members = PMProGroupAcct_Group_Member::get_group_members( $group_member_query_args );
	if ( ! empty( $group_members ) ) {
		// This user was previously a member of this group. Update their status to active.
		foreach ( $group_members as $group_member ) {
			$group_member->update_group_child_status( 'active' );
		}
		return;
	} else {
		// This user was not previously a member of this group. Add them to the group.
		PMProGroupAcct_Group_Member::create( $user_id, $checkout_level_id, $group->id );
	}
}
add_action( 'pmpro_after_checkout', 'pmprogroupacct_pmpro_after_checkout_child', 10, 2 );

/**
 * If a child loses a membership level associated with a group,
 * we need to remove them from that group.
 *
 * @since TBD
 *
 * @param array $old_user_levels The old levels the users had.
 */
function pmprogroupacct_pmpro_after_all_membership_level_changes_child( $old_user_levels ) {
	// Loop through all users who have had changed levels.
	foreach ( $old_user_levels as $user_id => $old_levels ) {
		// Get the IDs of the user's old levels.
		$old_level_ids = wp_list_pluck( $old_levels, 'id' );

		// Get the new level for this user.
		$new_levels    = pmpro_getMembershipLevelsForUser( $user_id );
		$new_level_ids = wp_list_pluck( $new_levels, 'id' );

		// Get the levels that the user lost.
		$lost_level_ids = array_diff( $old_level_ids, $new_level_ids );

		// Check if the lost level is associated with a group.
		foreach ( $lost_level_ids as $lost_level_id ) {
			$member_query_args = array(
				'group_child_user_id'  => (int)$user_id,
				'group_child_level_id' => (int)$lost_level_id,
				'group_child_status'   => 'active',
			);
			$group_members = PMProGroupAcct_Group_Member::get_group_members( $member_query_args );
			foreach ( $group_members as $group_member ) {
				$group_member->update_group_child_status( 'inactive' );
			}
		}
	}
}
add_action( 'pmpro_after_all_membership_level_changes', 'pmprogroupacct_pmpro_after_all_membership_level_changes_child' );
