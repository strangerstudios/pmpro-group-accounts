<?php
/**
 * Add the invite email to email templates.
 *
 * @since TBD
 *
 * @param array $templates The email templates.
 * @return array The email templates with the invite email added.
 */
function pmprogroupacct_email_templates( $templates ) {
	// Build the email body for later use.
	ob_start();
	?>
	<p><?php esc_html_e( 'You have been invited to !!blog_name!! by !!pmprogroupacct_parent_display_name!!.', 'pmpro-group-accounts' ); ?></p>
	<p><?php esc_html_e( 'To join the group, click the link below and complete the checkout process.', 'pmpro-group-accounts' ); ?></p>
	<p><a href="!!pmprogroupacct_invite_link!!">!!pmprogroupacct_invite_link!!</a></p>
	<?php
	$body = ob_get_clean();
	$templates['pmpgoroupacct_invite'] = array(
		'subject'     => esc_html__( '!!pmprogroupacct_parent_display_name!! has invited you to !!blog_name!!', 'pmpro-group-accounts' ),
		'description' => esc_html__( 'Group Account Invite', 'pmpro-group-accounts' ),
		'body'        => $body,
		'help_text'   => esc_html__( 'This email is sent when a group parent completes the form to invite other users to their group via email.', 'pmpro-group-accounts' )
	);
	return $templates;
}
add_filter( 'pmproet_templates', 'pmprogroupacct_email_templates' );
