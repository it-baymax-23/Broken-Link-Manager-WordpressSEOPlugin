<?php

/**
 * Checker options page config
 */
add_action( 'admin_init', 'eblc_register_settings' );

function eblc_register_settings() {
	add_option( 'eblc_options', '' );
	register_setting( 'eblc_options', 'eblc_options' );
	add_option( 'eblc_link_options', '' );
	register_setting( 'eblc_link_options', 'eblc_link_options' );
	add_option( 'eblc_sec_options', '' );
	register_setting( 'eblc_sec_options', 'eblc_sec_options' );
}


/**
 * Broken links check options page
 */
function eblc_check_options()
{
	$eblc_default_options = array(
	    "check_threshold"                   =>  24,
	    "send_email_notifications_control"  =>  true,
	    "send_email_notifications"          =>  true,
	    "send_authors_email_notifications"  =>  false,
	    "notification_email_address"        =>  "",
	    "timeout"                           =>  30,

	    "eblc-external-link-settings"       =>  array(
	    	"target"                =>  false,
	    	"target_overwrite"      =>  false,
	    	"rel_follow"            =>  "dofollow",
	    	"rel_overwrite"         =>  false,
	    	"rel_noopener"          =>  false,
	    	"rel_noreferrer"        =>  false,
	    	"rel_external"          =>  false
	    ),
	    "eblc-internal-link-settings"       =>  array(
	    	"target"                =>  false,
	    	"target_overwrite"      =>  false,
	    	"rel_follow"            =>  "dofollow",
	    	"rel_overwrite"         =>  false,
	    	"rel_noopener"          =>  false,
	    	"rel_noreferrer"        =>  false
	    ),	    
	    'show_link_actions'                 =>  array(
	    	'edit'                  =>  true,
	    	'unlink'                =>  true,
	    	'recheck'               =>  true
	    )
	);

	$eblc_default_link_options = array(
		'internal_link_follow_setting'      =>  'dofollow',
	    'internal_link_target_setting'      =>  false,
	    'external_link_follow_setting'      =>  'dofollow',
	    'external_link_target_setting'      =>  true,
	);

	$eblc_default_sec_options = array(
	    "login_attempt_limit_num"   =>  3,
	    "lockout_time"              =>  3,
	    "lockout_notify_mail"       =>  "",
	    "change_login_url"          =>  "",
	    "404_redirect_url"          =>  "",
	    "google_analytics_code"     =>  "",
	    "custom_javascript_code"    =>  ""
	);

	require "checker-options-page-view.php";
}

/**
 * Get number of internal and external links
 */
function eblc_get_internal_external_broken_links() {
	global $wpdb;

	// Get broken links data from links table
	$link_table_name = $wpdb->prefix . 'eblc_links';
	$broken_links = $wpdb->get_results( "SELECT * FROM {$link_table_name} WHERE http_code >= 400 ;" );

	$internal_broken_links = 0;
	$external_broken_links = 0;

    foreach ( $broken_links as $link ) {
    	$url = $link->url;
    	$server_host = $_SERVER['HTTP_HOST'];
    	if ( strpos( $url, $server_host ) !== false ) {
    		$internal_broken_links ++;
    	} else {
    		$external_broken_links ++;
    	}
	}

	$result = array(
		'internal_broken_links' => $internal_broken_links,
		'external_broken_links' => $external_broken_links
	);

	return $result;
}

/**
 * Get number of total links
 */
function eblc_get_total_links() {
	global $wpdb;
	// Get links data from links table
	$link_table_name = $wpdb->prefix . 'eblc_links';    	
	$links_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$link_table_name};" );
	return $links_count;
}



