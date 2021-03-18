<?php

/**
 * Login attempts check
 */

add_action( 'wp_login_failed', 'eblc_register_login_failed' );
add_action( 'login_init', 'eblc_check_login_attempts' );
add_action( 'wp_login', 'eblc_check_login' );

/**
 * Register login failed
 */
function eblc_register_login_failed() {
	//whether ip is from share internet
	if (!empty($_SERVER['HTTP_CLIENT_IP']))
  	{
    	$ip_address = $_SERVER['HTTP_CLIENT_IP'];
  	}
	//whether ip is from proxy
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
  	{
    	$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
 	}
	//whether ip is from remote address
	else
  	{
    	$ip_address = $_SERVER['REMOTE_ADDR'];
  	}


	global $wpdb;

	$login_attempts_log_table_name = $wpdb->prefix . 'eblc_login_attempts_log';
	// Get last check time
	$checking_log = $wpdb->get_row( "SELECT * FROM {$login_attempts_log_table_name} WHERE ip_address = '{$ip_address}' ;" );

	// Load setting options.
    if ( get_option('eblc_sec_options') != '' ) {
    	$eblc_sec_options = get_option('eblc_sec_options');
    	if ( $eblc_sec_options['login_attempt_limit_num'] == '' || !is_numeric( $eblc_sec_options['login_attempt_limit_num'] )  ) {
    		$login_attempt_limit_num = 3;
    	} else {
    		$login_attempt_limit_num = (int) $eblc_sec_options['login_attempt_limit_num'];
    		if ( $login_attempt_limit_num == 0 ) $login_attempt_limit_num = 3;
    	}

    	if ( $eblc_sec_options['lockout_time'] == '' || !is_numeric( $eblc_sec_options['lockout_time'] )  ) {
    		$lockout_time = 3;
    	} else {
    		$lockout_time = (int) $eblc_sec_options['lockout_time'];
    		if ( $lockout_time == 0 ) $lockout_time = 3;
    	}

    } else {
    	$login_attempt_limit_num = 3;
    	$lockout_time = 3;
    }


	if( $checking_log == NULL || $checking_log == array() ) {
		if ( $login_attempt_limit_num == 1 ) {
			$status = 0;
		} else $status = 1;
		$login_attempts_data = array(
			'ip_address'            => $ip_address,
			'attempts_failed_count' => 1,
			'status'                => $status,
			'last_failed_datetime'  => date("Y-m-d H:i:s"),
		);

		$wpdb->insert( $login_attempts_log_table_name, $login_attempts_data );
	} else {
		$attempts_failed_count = (int) $checking_log->attempts_failed_count;
		$last_failed_datetime  = $checking_log->last_failed_datetime;

		if ( $attempts_failed_count + 1 < $login_attempt_limit_num ) {
			$status = 1;
		} else $status = 0;

		$login_attempts_data = array(
			'ip_address'            => $ip_address,
			'attempts_failed_count' => (( $attempts_failed_count + 1 ) >= $login_attempt_limit_num ? 0 : ( $attempts_failed_count + 1 )),
			'status'                => $status,
			'last_failed_datetime'  => date("Y-m-d H:i:s"),
		);

		$wpdb->update( $login_attempts_log_table_name, $login_attempts_data, array( 'id' => $checking_log->id ) );

		if ($status == 0) {
			// send email to administrator
			eblc_send_email_notification_for_lockout( $ip_address, $login_attempt_limit_num );

			if ( get_option('eblc_sec_options') != '' ) {
		    	$eblc_sec_options = get_option('eblc_sec_options');
		    	if ( $eblc_sec_options['change_login_url'] != '' && filter_var( $eblc_sec_options['change_login_url'], FILTER_VALIDATE_URL ) ) {

		    		header("Location: " . $eblc_sec_options['change_login_url'], TRUE, 302);
					exit;
		    	} else {
		    		echo '<div style="margin-left: 30%; margin-right: 30%; padding: 20px; margin-top: 150px; background-color: lightgray; text-align: center;">';			
					echo '<p>You are locaked and you can not login right now. Please try ' . $lockout_time . ' hours later, again.</p>';
					echo '<a href="'. home_url() .'">Back home page</a>';
					echo '</div>';
					exit;
		    	}
		    } else {
		    	echo '<div style="margin-left: 30%; margin-right: 30%; padding: 20px; margin-top: 150px; background-color: lightgray; text-align: center;">';			
				echo '<p>You are locaked and you can not login right now. Please try ' . $lockout_time . ' hours later, again.</p>';
				echo '<a href="'. home_url() .'">Back home page</a>';
				echo '</div>';
				exit;
		    }			
		}
	}

	if ( get_option('eblc_sec_options') != '' ) {
    	$eblc_sec_options = get_option('eblc_sec_options');
    	if ( $eblc_sec_options['change_login_url'] != '' && filter_var( $eblc_sec_options['change_login_url'], FILTER_VALIDATE_URL ) ) {

    		header("Location: " . $eblc_sec_options['change_login_url'], TRUE, 302);
			exit;
    	}
    }
}

/**
 * Check login attempts
 */
function eblc_check_login_attempts() {
	//whether ip is from share internet
	if (!empty($_SERVER['HTTP_CLIENT_IP']))
  	{
    	$ip_address = $_SERVER['HTTP_CLIENT_IP'];
  	}
	//whether ip is from proxy
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
  	{
    	$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
 	}
	//whether ip is from remote address
	else
  	{
    	$ip_address = $_SERVER['REMOTE_ADDR'];
  	}

  	global $wpdb;

	$login_attempts_log_table_name = $wpdb->prefix . 'eblc_login_attempts_log';
	// Get last check time
	$checking_log = $wpdb->get_row( "SELECT * FROM {$login_attempts_log_table_name} WHERE ip_address = '{$ip_address}' ;" );

	// Load setting options.
    if ( get_option('eblc_sec_options') != '' ) {
    	$eblc_sec_options = get_option('eblc_sec_options');
    	if ( $eblc_sec_options['login_attempt_limit_num'] == '' || !is_numeric( $eblc_sec_options['login_attempt_limit_num'] )  ) {
    		$login_attempt_limit_num = 3;
    	} else {
    		$login_attempt_limit_num = (int) $eblc_sec_options['login_attempt_limit_num'];
    		if ( $login_attempt_limit_num == 0 ) $login_attempt_limit_num = 3;
    	}

    	if ( $eblc_sec_options['lockout_time'] == '' || !is_numeric( $eblc_sec_options['lockout_time'] )  ) {
    		$lockout_time = 3;
    	} else {
    		$lockout_time = (int) $eblc_sec_options['lockout_time'];
    		if ( $lockout_time == 0 ) $lockout_time = 3;
    	}
    } else {
    	$login_attempt_limit_num = 3;
    	$lockout_time = 3;
    }

    if( $checking_log != NULL && $checking_log != array() ) {
		$attempts_failed_count = (int) $checking_log->attempts_failed_count;
		$last_failed_datetime  = $checking_log->last_failed_datetime;
		$status = (int) $checking_log->status;

		if ( $status == 0 ) {
			$last_failed_datetime_time = strtotime( $last_failed_datetime );
			$now_time = strtotime( date("Y-m-d H:i:s") );

			if ( ($now_time - $last_failed_datetime_time) < ($lockout_time * 3600) ) {
				$diff = ($lockout_time * 3600) - ($now_time - $last_failed_datetime_time);
				$hours = (int) ($diff / 3600);
				$mins = (int) (($diff - $hours * 3600) / 60);
				echo '<div style="margin-left: 30%; margin-right: 30%; padding: 20px; margin-top: 150px; background-color: lightgray; text-align: center;">';
				echo '<p>You are locaked and you can not login right now. Please try ' . $hours . ' h ' . $mins . ' min later, again. </p>';
				echo '<a href="'. home_url() .'">Back home page</a>';
				echo '</div>';

				exit;
			} else {
				$login_attempts_data = array(
					'ip_address'            => $ip_address,
					'attempts_failed_count' => 0,
					'status'                => 1,
					'last_failed_datetime'  => date("Y-m-d H:i:s"),
				);

				$wpdb->update( $login_attempts_log_table_name, $login_attempts_data, array( 'id' => $checking_log->id ) );
			}

		}
	}
}

/**
 * Check login
 */
function eblc_check_login() {
	//whether ip is from share internet
	if (!empty($_SERVER['HTTP_CLIENT_IP']))
  	{
    	$ip_address = $_SERVER['HTTP_CLIENT_IP'];
  	}
	//whether ip is from proxy
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
  	{
    	$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
 	}
	//whether ip is from remote address
	else
  	{
    	$ip_address = $_SERVER['REMOTE_ADDR'];
  	}

  	global $wpdb;

	$login_attempts_log_table_name = $wpdb->prefix . 'eblc_login_attempts_log';
	// Get last check time
	$checking_log = $wpdb->get_row( "SELECT * FROM {$login_attempts_log_table_name} WHERE ip_address = '{$ip_address}' ;" );

	if( $checking_log != NULL && $checking_log != array() ) {
		$wpdb->query( "DELETE FROM {$login_attempts_log_table_name} WHERE ip_address = '{$ip_address}' ; " );
	}
}


/**
 * Send email notification for lockout
 */
function eblc_send_email_notification_for_lockout( $ip_address, $attempts_failed_count ) {
	// Get admin email
	$admin_email = '';
	if ( get_option( 'admin_email' ) != '' ) {
		$admin_email = sanitize_email( get_option( 'admin_email' ) );
	}

	// Get custom admin email and Option with Send e-mail notifications about lockout detect
	$admin_custom_email = "";
	if ( get_option( 'eblc_sec_options' ) != '' ) {
    	$eblc_sec_options = get_option( 'eblc_sec_options' );
    	if ( $eblc_sec_options['lockout_notify_mail'] != '' ) {
    		$admin_custom_email = sanitize_email( $eblc_sec_options['lockout_notify_mail'] );
    	}
    }

    if ( $admin_custom_email != '' ) {
    	$admin_email = $admin_custom_email;
    }



    // Send email to admin with lockout info
    if ( $admin_email != '' && filter_var( $admin_email, FILTER_VALIDATE_EMAIL ) ) {

    	$mail_header = "New Lockout Detected";
    	$mail_body   = "Hello Dear,<br><br>" . 
					   "Easy Broken Link Checker has deteted new login lockout on your site. <br><br>" . 
					   "Here's the details about new login lockout: <br><br>" .

					   "IP Address: " . $ip_address . "<br>" . 
					   "Attempts Count : " . $attempts_failed_count . "<br><br>" .   

					   "Regards <br>" . 
					   "Easy Broken Link Checker Team <br>";
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

		wp_mail( $admin_email, $mail_header, $mail_body, $headers );
    }
}


/**
 * Redirect 404 page when page not found
 */
add_action('template_redirect', 'eblc_redirection_404_page' );

function eblc_redirection_404_page() 
{
    if( is_404() )
    {
    	if ( get_option( 'eblc_sec_options' ) != '' ) {
    		$eblc_sec_options = get_option( 'eblc_sec_options' );
	    	if ( $eblc_sec_options['404_redirect_url'] != '' && filter_var( $eblc_sec_options['404_redirect_url'], FILTER_VALIDATE_URL ) ) {
	    		header("Location: " . $eblc_sec_options['404_redirect_url'], TRUE, 301);
    			exit;
	    	}
	    }        
    }
}


/**
 * Put google analytics code and custom javascript code
 */
add_action( 'wp_head', 'eblc_put_google_analytics_custom_javascript_code' );

function eblc_put_google_analytics_custom_javascript_code() {
	if ( get_option( 'eblc_sec_options' ) != '' ) {
    	$eblc_sec_options = get_option( 'eblc_sec_options' );
    	if ( $eblc_sec_options['google_analytics_code'] != '' ) {
    		echo $eblc_sec_options['google_analytics_code'];
    	}

    	if ( $eblc_sec_options['custom_javascript_code'] != '' ) {
    		echo $eblc_sec_options['custom_javascript_code'];
    	}
    }
}