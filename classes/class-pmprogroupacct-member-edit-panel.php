<?php

class PMProgroupacct_Member_Edit_Panel extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug        = 'group-accounts';
		$this->title       = __( 'Group Accounts', 'pmpro-group-accounts' );
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		pmprogroupacct_show_group_account_info( self::get_user() );
	}
}
