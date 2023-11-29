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
add_filter( 'pmproet_templates', 'pmprogroupacct_email_templates' );
