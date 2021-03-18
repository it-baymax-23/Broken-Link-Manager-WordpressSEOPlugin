<?php
/*
Plugin Name: Advance Broken Link Checker - Link Manager
Plugin URI: https://wordpress.org/plugins/easy-broken-link-checker/
Description: Advance Broken Link Checker helps you to check all links in posts, pages, comments and blogs, and detect broken links, send email notification to administrator or author email and fix them.
Version: 5.2.2
Author: jannatqualitybacklinks.com
Author URI: https://jannatqualitybacklinks.com
Text Domain: easy-broken-link-checker
*/


/**
 * Prefix for Easy Broken Link Checker: EBLC_   eblc_
 */

//Path to this file
if ( !defined('EBLC_PLUGIN_FILE') ){
	define('EBLC_PLUGIN_FILE', __FILE__);
}

/** 
 * Initialize the plugin
 */
require 'init-plugin.php';

/** 
 * Broken links page config
 */
include 'broken-links-page.php';

/** 
 * Auto links page config
 */
include 'auto-links-page.php';

/** 
 * Shorten links page config
 */
include 'shorten-links-page.php';

/** 
 * Redirection links page config
 */
include 'redirection-links-page.php';

/** 
 * Checker options page config
 */ 
include 'checker-options-page.php';

/**
 * Check all links
 */
include 'checker-core.php';

/**
 * Login attempts check
 */
include 'login-attempts.php';
