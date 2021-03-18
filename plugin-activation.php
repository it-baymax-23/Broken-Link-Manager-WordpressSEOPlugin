<?php

/**
 * Plugin activation
 */

/**
 * Create database for plugin
 */
register_activation_hook( EBLC_PLUGIN_FILE, 'eblc_create_table_for_plugin', 15 );

function eblc_create_table_for_plugin() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$collection_sql = "
		CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}eblc_collections` (
			`collection_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`link_id` int(10) unsigned NOT NULL,
			`element_id` int(10) unsigned NOT NULL,
			`element_type` varchar(40) NOT NULL DEFAULT 'post',
			`link_text` text NOT NULL DEFAULT '',
			`parser_type` varchar(40) NOT NULL DEFAULT 'link',
			`element_field` varchar(250) NOT NULL DEFAULT '',
			`link_context` varchar(250) NOT NULL DEFAULT '',
			`raw_url` text NOT NULL,			  
			PRIMARY KEY (`collection_id`)
		) {$charset_collate};";

	$links_sql = "
		CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}eblc_links` (
			`link_id` int(20) unsigned NOT NULL AUTO_INCREMENT,
			`url` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`first_failure` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			`last_check` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			`last_success` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			`last_check_attempt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			`check_count` int(4) unsigned NOT NULL DEFAULT '0',
			`final_url` text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
			`redirect_count` smallint(5) unsigned NOT NULL DEFAULT '0',
			`log` text NOT NULL,
			`http_code` smallint(6) NOT NULL DEFAULT '0',
			`status_code` varchar(100) DEFAULT 'Not checked',
			`status_text` varchar(250) DEFAULT 'Not checked',
			`request_duration` float NOT NULL DEFAULT '0',
			`timeout` tinyint(1) unsigned NOT NULL DEFAULT '0',
			`broken` tinyint(1) unsigned NOT NULL DEFAULT '0',
			`link_type` varchar(100) DEFAULT 'dofollow',
			`link_target` varchar(100) DEFAULT '_self',
			`clicks_count` smallint(5) unsigned NOT NULL DEFAULT '0',
			`unique_clicks_count` smallint(5) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`link_id`)
		) {$charset_collate};";

	$shorten_links_sql = "
		CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}eblc_shorten_links` (
			`shorten_link_id` int(20) unsigned NOT NULL AUTO_INCREMENT,
			`target_url` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`shorten_url` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`clicks_count` int(4) unsigned NOT NULL DEFAULT '0',
			`unique_clicks_count` int(4) unsigned NOT NULL DEFAULT '0',
			`redirection_type` smallint(6) NOT NULL DEFAULT '0',
			`created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`shorten_link_id`)
		) {$charset_collate};";

	$redirection_links_sql = "
		CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}eblc_redirection_links` (
			`redirection_link_id` int(20) unsigned NOT NULL AUTO_INCREMENT,
			`source_url` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`target_url` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`hits_count` int(4) unsigned NOT NULL DEFAULT '0',
			`unique_hits_count` int(4) unsigned NOT NULL DEFAULT '0',
			`redirection_type` smallint(6) NOT NULL DEFAULT '0',
			`created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`redirection_link_id`)
		) {$charset_collate};";

	$checking_log_sql = "
		CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}eblc_shedule_checking_log` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`last_check` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',			  
			PRIMARY KEY (`id`)
		) {$charset_collate};";

	$login_attempts_sql = "
		CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}eblc_login_attempts_log` (
			`id` int(20) unsigned NOT NULL AUTO_INCREMENT,
			`ip_address` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`attempts_failed_count` int(4) unsigned NOT NULL DEFAULT '0',
			`status` smallint(6) NOT NULL DEFAULT '1',
			`last_failed_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`id`)
		) {$charset_collate};";

	$auto_links_sql = "
		CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}eblc_auto_links` (
			`auto_link_id` int(20) unsigned NOT NULL AUTO_INCREMENT,
			`anchor_text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`target_url` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`clicks_count` int(4) unsigned NOT NULL DEFAULT '0',
			`unique_clicks_count` int(4) unsigned NOT NULL DEFAULT '0',
			`created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`auto_link_id`)
		) {$charset_collate};";

	$link_clicks_sql = "
		CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}eblc_link_clicks` (
			`id` int(20) unsigned NOT NULL AUTO_INCREMENT,
			`ip` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`anchor_text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`target_url` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
			`date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`id`)
		) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	dbDelta( $collection_sql );
	dbDelta( $links_sql );
	dbDelta( $shorten_links_sql );
	dbDelta( $redirection_links_sql );
	dbDelta( $login_attempts_sql );
	dbDelta( $checking_log_sql );
	dbDelta( $auto_links_sql );
	dbDelta( $link_clicks_sql );
}

/*
 * Create schedule function for checking links
 */
register_activation_hook( EBLC_PLUGIN_FILE, 'eblc_register_schedule' );
  
function eblc_register_schedule() {
    if ( ! wp_next_scheduled ( 'eblc_hourly_schedule' ) ) {
        wp_schedule_event( time(), 'hourly', 'eblc_hourly_schedule' );
    }
}


