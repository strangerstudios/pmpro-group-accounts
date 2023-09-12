<?php

/**
 * Enqueue admin scripts.
 *
 * @since TBD
 */
function pmprogroupacct_admin_enqueue_scripts() {
    // Enqueue the admin script.
    wp_enqueue_script( 'pmprogroupacct-admin', plugins_url( 'js/pmprogroupacct-admin.js', dirname(__FILE__) ), array( 'jquery' ), PMPROGROUPACCT_VERSION );

    // Enqueue select2 JS and CSS.
    wp_enqueue_script( 'select2', plugins_url( 'js/select2.min.js', dirname(__FILE__) ), array( 'jquery' ), '4.0.13' );
    wp_enqueue_style( 'select2', plugins_url( 'css/select2.min.css', dirname(__FILE__) ), array(), '4.0.13' );
}
add_action( 'admin_enqueue_scripts', 'pmprogroupacct_admin_enqueue_scripts' );

/**
 * Enqueue frontend scripts.
 *
 * @since TBD
 */
function pmprogroupacct_wp_enqueue_scripts() {
    // Enqueue the checkout script.
    wp_enqueue_script( 'pmprogroupacct-checkout', plugins_url( 'js/pmprogroupacct-checkout.js', dirname(__FILE__) ), array( 'jquery' ), PMPROGROUPACCT_VERSION );
}
add_action( 'wp_enqueue_scripts', 'pmprogroupacct_wp_enqueue_scripts' );
