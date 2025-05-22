<?php

class PMPro_Email_Template_PMProGroupAcct_Invite extends PMPro_Email_Template {

	/**
	 * The parent user.
	 *
	 * @var WP_User
	 */
	protected $parent_user;

	/**
	 * The level object.
	 *
	 * @var int
	 */
	protected $level_id;

	/**
	 * The group object.
	 *
	 * @var PMProGroupAcct_Group
	 */
	protected $group;

	/**
	 * A valid email address to send the invite to.
	 *
	 * @var string
	 */
	protected $invite_new_member_email;

	/**
	 * Constructor.
	 *
	 * @since 1.3
	 *
	 * @param WP_User $member The user applying for membership.
	 * @param int $level_id The level id.
	 * @param PMProGroupAcct_Group $group The group object.
	 * @param string $invite_new_member_email A valid email address to send the invite to.
	 */
	public function __construct( WP_User $parent_user, int $level_id, PMProGroupAcct_Group $group, String $invite_new_member_email ) {
		$this->parent_user = $parent_user;
		$this->level_id = $level_id;
		$this->group = $group;
		$this->invite_new_member_email = $invite_new_member_email;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since 1.3
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'pmprogroupacct_invite';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since 1.3
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Group Accounts - Invite Member', 'pmpro-group-accounts' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since 1.3
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return  esc_html__( 'This email is sent when a group account owner completes the form to invite other users to their group via email.', 'pmpro-group-accounts' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since 1.3
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( '!!pmprogroupacct_parent_display_name!! has invited you to !!blog_name!!', 'pmpro-group-accounts' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since 1.3
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>You have been invited to !!blog_name!! by !!pmprogroupacct_parent_display_name!!.</p>

<p>To join the group, click the link below and complete the checkout process.</p>

<p>!!pmprogroupacct_invite_link!!</p>', 'pmpro-group-accounts' ) );
	}

	/**
	 * Get the email template variables for the email paired with a description of the variable.
	 *
	 * @since 1.3
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public static function get_email_template_variables_with_description() {
		return array(
			'!!pmprogroupacct_parent_display_name!!' => esc_html__( 'The display name of the parent user.', 'pmpro-group-accounts' ),
			'!!pmprogroupacct_invite_link!!' => esc_html__( 'The link to the checkout page for the group.', 'pmpro-group-accounts' ),
			'!!blog_name!!' => esc_html__( 'The name of the site.', 'pmpro-group-accounts' ),
		);
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since 1.3
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		$parent_user = $this->parent_user;
		$group = $this->group;

		$email_template_variables = array(
			'pmprogroupacct_parent_display_name' => empty( $parent_user ) ? '[' . esc_html__( 'User Not Found' ) . ']' : $parent_user->display_name,
			'pmprogroupacct_invite_link' => esc_url( add_query_arg( array( 'level' => $this->level_id, 'pmprogroupacct_group_code' => $group->group_checkout_code ), pmpro_url( 'checkout' ) ) ),
			'blog_name' => get_bloginfo( 'name' ),
		);

		return $email_template_variables;
	}

	/**
	 * Get the email address to send the email to.
	 *
	 * @since 1.3
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		return $this->invite_new_member_email;
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since 1.3
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		$email_user = get_user_by( 'email', $this->invite_new_member_email );
		return empty( $email_user->display_name ) ? esc_html__( 'User', 'pmpro-group-accounts' ) : $email_user->display_name;
	}

	/**
	 * Returns the arguments to send the test email from the abstract class.
	 *
	 * @since TBD
	 *
	 * @return array The arguments to send the test email from the abstract class.
	 */
	public static function get_test_email_constructor_args() {
		global $current_user;
		$groupacct = new PMProGroupAcct_Group(1);
		return array( $current_user, "1", $groupacct->get_test_group(), $current_user->user_email );
	}
}
/**
 * Register the email template.
 *
 * @since 1.3
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_template_pmpro_group_account_invite( $email_templates ) {
	$email_templates['pmprogroupacct_invite'] = 'PMPro_Email_Template_PMProGroupAcct_Invite';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_template_pmpro_group_account_invite' );
