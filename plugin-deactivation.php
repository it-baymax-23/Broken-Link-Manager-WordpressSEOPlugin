<?php

/**
 * Plugin deactivation
 */

/**
 * Drop database for plugin
 */
register_deactivation_hook( EBLC_PLUGIN_FILE, 'eblc_drop_table_for_plugin');
function eblc_drop_table_for_plugin() {
    global $wpdb;
    $tableArray = array(   
        $wpdb->prefix . "eblc_collections",
        $wpdb->prefix . "eblc_links",
        $wpdb->prefix . "eblc_shorten_links",
        $wpdb->prefix . "eblc_redirection_links",
        $wpdb->prefix . "eblc_login_attempts_log",
        $wpdb->prefix . "eblc_shedule_checking_log",
        $wpdb->prefix . "eblc_auto_links",
        $wpdb->prefix . "eblc_link_clicks",
    );
    foreach ($tableArray as $tablename) {
        $wpdb->query("DROP TABLE IF EXISTS `{$tablename}`;");
    }
}

/**
 * Remove schedule for plugin
 */
register_deactivation_hook( EBLC_PLUGIN_FILE, 'eblc_remove_schedule_for_plugin' );
function eblc_remove_schedule_for_plugin() {
	wp_clear_scheduled_hook( 'eblc_hourly_schedule' );
}

/**
 * Remove option for plugin
 */
register_deactivation_hook( EBLC_PLUGIN_FILE, 'eblc_remove_option_for_plugin' );
function eblc_remove_option_for_plugin() {
	delete_option( 'eblc_options' );
    delete_option( 'eblc_link_options' );
    delete_option( 'eblc_sec_options' );
}

