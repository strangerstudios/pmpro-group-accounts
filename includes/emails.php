<?php
/**
 * Set up email templates.
 *
 * @since TBD
 */
function pmprogroupacct_init_email_templates() {
	if ( class_exists( 'PMPro_Email_Template' ) ) {
		// Using PMPro v3.4+. Include the email template class.
		include_once( PMPROGROUPACCT_DIR . '/classes/email-templates/class-pmpro-email-template-pmpro-group-accounts-invite.php' );
	} else {
		// Using PMPro version under v3.4. Use the old filter.
		add_filter( 'pmproet_templates', 'pmprogroupacct_email_templates' );
	}
}
add_action( 'init', 'pmprogroupacct_init_email_templates', 8 ); // Priority 8 to ensure the pmproet_templates hook is added before PMPro loads email templates.

/**
 * Add the invite email to email templates.
 *
 * @since 1.0
 *
 * @param array $templates The email templates.
 * @return array The email templates with the invite email added.
 */
function pmprogroupacct_email_templates( $templates ) {
	$templates['pmprogroupacct_invite'] = array(
		'subject'     => esc_html__( '!!pmprogroupacct_parent_display_name!! has invited you to !!blog_name!!', 'pmpro-group-accounts' ),
		'description' => esc_html__( 'Group Accounts - Invite Member', 'pmpro-group-accounts' ),
		'body'        => __( '<p>You have been invited to !!blog_name!! by !!pmprogroupacct_parent_display_name!!.</p>

<p>To join the group, click the link below and complete the checkout process.</p>

<p>!!pmprogroupacct_invite_link!!</p>', 'pmpro-group-accounts' ),
		'help_text'   => esc_html__( 'This email is sent when a group account owner completes the form to invite other users to their group via email.', 'pmpro-group-accounts' )
	);
	return $templates;
}

/**
 * Add an email template variable to show group data when a parent purchases a level with a group.
 *
 * @since 1.0
 *
 * @param array $data The email template data.
 * @param PMProEmail $email The email object.
 * @return array The email template data.
 */
function pmprogroupacct_email_data( $data, $email ) {
	// Set the group data in case we bail early.
	$data['pmprogroupacct_group_info'] = '';

	// If 'checkout' is not in the email template name, bail.
	if (  empty( $email->template ) || strpos( $email->template, 'checkout' ) === false ) {
		return $data; 
	}

	// Get the login for the user who this checkout is for.
	if ( empty( $email->data['user_login'] ) ) {
		return $data;
	}

	// Get the user object.
	$user = get_user_by( 'login', $email->data['user_login'] );
	if ( empty( $user ) ) {
		return $data;
	}

	// Get the level ID that was purchased.
	$level_id = empty( (int)$data['membership_id'] ) ? null : (int)$data['membership_id'];
	if ( empty( $level_id ) ) {
		return $data;
	}

	// Check if there is a group for this user and level.
	$group = PMProGroupAcct_Group::get_group_by_parent_user_id_and_parent_level_id( $user->ID, $level_id );
	if ( empty( $group ) ) {
		return $data;
	}

	// If this group doesn't have any open seats, bail.
	if ( ! $group->is_accepting_signups() ) {
		return $data;
	}

	// Get the group settings for the purchased level.
	$settings = pmprogroupacct_get_settings_for_level( $level_id );
	if ( empty( $settings ) ) {
		return $data;
	}

	// Get the level IDs that can be purchased using this group.
	$child_level_ids = empty( $settings['child_level_ids'] ) ? array() : $settings['child_level_ids'];

	// Build the group info section.
	ob_start();
	?>
	<p><?php esc_html_e( 'Your Group Code is:', 'pmpro-group-accounts' ); ?> <strong><?php echo esc_html( $group->group_checkout_code ); ?></strong></p>
	<p><?php esc_html_e( 'New members can use this code to join your group at no additional cost.', 'pmpro-group-accounts' ); ?></p>
	<ul>
		<?php
		foreach ( $child_level_ids as $child_level_id ) {
			$child_level = pmpro_getLevel( $child_level_id );
			?>
			<li>
				<?php
					/* translators: 1: membership level name, 2: membership level checkout link */
					printf(
						esc_html__( '%1$s membership: %2$s', 'pmpro-group-accounts' ),
						esc_html( $child_level->name ),
						esc_url( add_query_arg( array( 'level' => $child_level->id, 'pmprogroupacct_group_code' => $group->group_checkout_code ), pmpro_url( 'checkout' ) ) )
					);
				?>
			</li>
			<?php
		}
		?>
	</ul>
	<?php
	// Set the group data.
	$data['pmprogroupacct_group_info'] = ob_get_clean();

	return $data;
}
add_filter( 'pmpro_email_data', 'pmprogroupacct_email_data', 10, 2 );