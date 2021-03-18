<?php

/**
 * Initialize the plugin
 */

/** 
 * Activation configuration for plugin
 */
include 'plugin-activation.php';

/** 
 * Deactivation configuration for plugin
 */
include 'plugin-deactivation.php';

/** 
 * Add admin menu for plugin
 */
add_action( 'admin_menu', 'eblc_menu_pages' );
function eblc_menu_pages() {
	// Admin main menu: "Broken Links", Page title: "Easy Broken Link Checker"
    $eblc_menu = add_menu_page( 'Link Manager', 'Link Manager', 'manage_options', 'eblc_check_options', 'eblc_full_page', 'dashicons-admin-links' );
    add_action( 'admin_print_styles-' . $eblc_menu, 'eblc_custom_css' );
    add_action( 'admin_print_scripts-' . $eblc_menu, 'eblc_custom_js' );
}
// Add custom style to page
function eblc_custom_css() {
	wp_enqueue_style( 'eblc_bootstrap_css', plugins_url( '/css/bootstrap.min.css', EBLC_PLUGIN_FILE ) );
    wp_enqueue_style( 'eblc_bootstrap_toggle_css', "https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" );
	wp_enqueue_style( 'eblc_custom_css', plugins_url( '/css/eblc_custom_style.css', EBLC_PLUGIN_FILE ) );
}
// Add custom js to page
function eblc_custom_js() {
    wp_enqueue_script( 'eblc_font_scripts',  'https://use.fontawesome.com/releases/v5.3.1/js/all.js', array(), '2018928', true );
	wp_enqueue_script( 'eblc_bootstrap_js', plugins_url( '/js/bootstrap.min.js', EBLC_PLUGIN_FILE ) );
    wp_enqueue_script( 'eblc_bootstrap_toggle_js', "https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js" );
    wp_enqueue_script( 'eblc_custom_js', plugins_url( '/js/eblc_custom_js.js', EBLC_PLUGIN_FILE ) );
    // Create nonce for ajax
    wp_localize_script('eblc_custom_js', 'eblc_ajax_var', array(
        'eblc_ajax_url' => admin_url('admin-ajax.php'),
        'eblc_ajax_nonce' => wp_create_nonce('eblc-ajax-nonce')
    ));
}

// Add front custom js
add_action('wp_enqueue_scripts', 'eblc_front_scripts', 15);

function eblc_front_scripts() { 
    wp_enqueue_script( 'eblc_front_custom_js', plugins_url( '/js/eblc_front_custom_js.js', EBLC_PLUGIN_FILE ) );
    // Create nonce for ajax
    wp_localize_script('eblc_front_custom_js', 'eblc_ajax_var_front', array(
        'eblc_ajax_url_front' => admin_url('admin-ajax.php'),
        'eblc_ajax_nonce_front' => wp_create_nonce('eblc-ajax-nonce-front')
    ));
}

/** 
 * Callback function for eblc plugin menu
 */
function eblc_full_page() {
	// Check option display function
	eblc_check_options();
	// Broken links data display function
	// eblc_broken_links();
}







