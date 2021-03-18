<?php

/**
 * Checking links
 */

/**
 * Hourly schedule for checking links
 */
add_action( 'eblc_hourly_schedule', 'eblc_hourly_check_scdedule' );

function eblc_hourly_check_scdedule() {

	global $wpdb;

	$schedule_checking_log_table_name = $wpdb->prefix . 'eblc_shedule_checking_log';
	// Get last check time
	$checking_log = $wpdb->get_row( "SELECT last_check FROM {$schedule_checking_log_table_name};" );

	if ( $checking_log == NULL || $checking_log == array() ) {
		// If this is the first time, check all contents and links.
		// Check all contents for searhing links.
		eblc_check_all_contents();
		// Check all links if they are valid or not.
		eblc_check_all_links();
		// Send email notification with broken link info
		eblc_send_email_notification();
		
	} else {
		$last_check_time = strtotime( $checking_log->last_check );

		// Load setting options.
        if ( get_option('eblc_options') != '' ) {
        	$eblc_options = get_option('eblc_options');
        	if ( $eblc_options['check_threshold'] == '' ) {
        		$checking_thread_hold = 24;
        	} else {
        		$checking_thread_hold = (int) $eblc_options['check_threshold'];
        	}
        } else {
        	// Defualt thread hold hour - 24 hours
        	$checking_thread_hold = 24;
        }
        // Get current time.
        $current_time = date("Y-m-d H:i:s");
        $current_time = strtotime( $current_time );
        // Get difference of seconds between last checking time and current time.
        $diff = abs( $current_time - $last_check_time );
        // Get difference hours.
        $diff_hours = floor( $diff / 3600 );

        if ( $diff_hours >= $checking_thread_hold ) {
        	// Check all links if they are valid or not.
        	eblc_check_all_links();
        	// Send email notification with broken link info
			eblc_send_email_notification();
			// Sychronize links data
			//eblc_sync_links();
        }
	}
}

/**
 * Checking all contents for links
 */
function eblc_check_all_contents() {	
	eblc_check_all_posts();         // Checking all posts for links	
	eblc_check_all_comments();      // Checking all comments for links
}

/**
 * Checking all posts for links
 */
function eblc_check_all_posts() {
	$post_types = get_post_types( array(), 'objects' );
	$exceptions = array( 'revision', 'nav_menu_item', 'attachment' );
	$enable_post_types = array();
	foreach( $post_types as $data ) {
		$post_type = $data->name;
		
		if ( in_array( $post_type, $exceptions ) ) {
			continue;
		}
		array_push( $enable_post_types, $post_type );
	}
	
	foreach ( $enable_post_types as $post_type ) {
		$args = array(
			'numberposts' => -1,
		  	'post_type'   => $post_type
		);
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			// Check all urls in post and save them
			eblc_check_and_save_urls_in_post( $post, false );
		}
	}
}

/**
 * Checking all comments for links
 */
function eblc_check_all_comments() {
	$comments = get_comments();
	foreach ( $comments as $comment ) {
		// Check all urls in comment and save them
		eblc_check_and_save_urls_in_comment( $comment, false );
	}
}

/**
 * Check all urls in post and save them in database 
 */
function eblc_check_and_save_urls_in_post( $post, $single ) {
	$post_type    = $post->post_type;
	$post_content = $post->post_content;
	$post_content = preg_replace( '/<code[^>]*>.+?<\/code>/si', ' ', $post_content );

	// Check links in post content
	$new_document = new DomDocument('1.0', 'UTF-8');
	$new_document->loadHTML( mb_convert_encoding( $post_content, 'HTML-ENTITIES', 'UTF-8' ) );

	// Check links
	$all_anchors = $new_document->getElementsByTagName('a');
	foreach ( $all_anchors as $anchor ) {
		$link_url = $anchor->getAttribute('href');
		if ( strpos( $link_url, 'mailto:' ) !== false ) {
		    continue;
		}

		$link_text = $anchor->textContent;

		if ( $anchor->getAttribute('target') ) {
			$link_target = $anchor->getAttribute('target');
		} else {
			$link_target = '_self';
		}

		if ( $anchor->getAttribute('rel') ) {
			$rel = $anchor->getAttribute('rel');
			$rel_array = explode(' ', $rel);
			if ( in_array( 'nofollow', $rel_array ) ) {
				$link_type = 'nofollow';
			} else {
				$link_type = 'dofollow';
			}
		} else {
			$link_type = 'dofollow';
		}

		$element_id    = $post->ID;
		$element_type  = $post_type;
		$parser_type   = "link";
		$element_field = "post_content";
		$raw_url       = $link_url;

		if ( filter_var( $link_url, FILTER_VALIDATE_URL ) ) {
			// Save link url in post content
			$insert_link_id = eblc_save_link( $element_id, $element_type, $link_text, $parser_type, $element_field, $raw_url, $link_type, $link_target );
			// When save and untrash the post, then call check that link.
			if( $single ) {
				eblc_check_a_link( $insert_link_id );
			}
		}		
	}

	// Check image src
	$all_imgs = $new_document->getElementsByTagName('img');
	foreach ( $all_imgs as $img ) {
		$img_url = $img->getAttribute('src');
		$img_url_split = explode( '/', $img->getAttribute('src') );
		$img_name = $img_url_split[ count( $img_url_split ) - 1 ];

		$element_id    = $post->ID;
		$element_type  = $post_type;
		$parser_type   = "img_src";
		$element_field = "post_content";
		$raw_url       = $img_url;
		$link_type     = "image";
		$link_target   = "image";

		if ( filter_var( $img_url, FILTER_VALIDATE_URL ) ) {
			// Save link url in post content
			$insert_link_id = eblc_save_link( $element_id, $element_type, $img_name, $parser_type, $element_field, $raw_url, $link_type, $link_target );
			// When save and untrash the post, then call check that link.
			if( $single ) {
				eblc_check_a_link( $insert_link_id );
			}
		}
	}
}

/**
 * Check all urls in comment and save them in database 
 */
function eblc_check_and_save_urls_in_comment( $comment, $single ) {
	$comment_content    = $comment->comment_content;
	$comment_author     = $comment->comment_author;
	$comment_author_url = $comment->comment_author_url;
	$comment_author_url = html_entity_decode( $comment_author_url, ENT_QUOTES, $charset );
	$comment_author_url = trim( $comment_author_url );

	// Validate comment author url and if valid, save it
	if ( filter_var( $comment_author_url, FILTER_VALIDATE_URL ) ) {
		$element_id    = $comment->comment_ID;
		$element_type  = "comment";
		$link_text     = $comment_author;
		$parser_type   = "url_field";
		$element_field = "comment_author_url";
		$raw_url       = $comment_author_url;
		$link_type     = "dofollow";
		$link_target   = "_self";

		// Save comment author url
		$insert_link_id = eblc_save_link( $element_id, $element_type, $link_text, $parser_type, $element_field, $raw_url, $link_type, $link_target );
		// When save and untrash the comment, then call check that link.
		if( $single ) {
			eblc_check_a_link( $insert_link_id );
		}
	}

	$comment_content = preg_replace( '/<code[^>]*>.+?<\/code>/si', ' ', $comment_content );

	// Find href of anchor tag from comment content
	$new_document = new DomDocument('1.0', 'UTF-8');
	$new_document->loadHTML( mb_convert_encoding( $comment_content, 'HTML-ENTITIES', 'UTF-8' ) );
	$all_anchors = $new_document->getElementsByTagName('a');

	foreach ( $all_anchors as $anchor ) {
		$link_url = $anchor->getAttribute('href');

		if ( strpos( $link_url, 'mailto:' ) !== false) {
		    continue;
		}

		$link_text = $anchor->textContent;

		if ( $anchor->getAttribute('target') ) {
			$link_target = $anchor->getAttribute('target');
		} else {
			$link_target = '_self';
		}

		if ( $anchor->getAttribute('rel') ) {
			$rel = $anchor->getAttribute('rel');
			$rel_array = explode(' ', $rel);
			if ( in_array( 'nofollow', $rel_array ) ) {
				$link_type = 'nofollow';
			} else {
				$link_type = 'dofollow';
			}
		} else {
			$link_type = 'dofollow';
		}

		$element_id    = $comment->comment_ID;
		$element_type  = "comment";
		$parser_type   = "link";
		$element_field = "comment_content";
		$raw_url       = $link_url;

		if ( filter_var( $link_url, FILTER_VALIDATE_URL ) ) {
			// Save link url in comment content 
			$insert_link_id = eblc_save_link( $element_id, $element_type, $link_text, $parser_type, $element_field, $raw_url, $link_type, $link_target );
			// When save and untrash the comment, then call check that link.
			if( $single ) {
				eblc_check_a_link( $insert_link_id );
			}
		}
	}

	// Check image src
	$all_imgs = $new_document->getElementsByTagName('img');
	foreach ( $all_imgs as $img ) {
		$img_url = $img->getAttribute('src');
		$img_url_split = explode( '/', $img->getAttribute('src') );
		$img_name = $img_url_split[ count( $img_url_split ) - 1 ];

		$element_id    = $post->ID;
		$element_type  = $post_type;
		$parser_type   = "img_src";
		$element_field = "post_content";
		$raw_url       = $img_url;
		$link_type     = "image";
		$link_target   = "image";

		if ( filter_var( $img_url, FILTER_VALIDATE_URL ) ) {
			// Save link url in post content
			$insert_link_id = eblc_save_link( $element_id, $element_type, $img_name, $parser_type, $element_field, $raw_url, $link_type, $link_target );
			// When save and untrash the post, then call check that link.
			if( $single ) {
				eblc_check_a_link( $insert_link_id );
			}
		}
	}
}

/**
 * Save link in database
 */
function eblc_save_link( $element_id, $element_type, $link_text, $parser_type, $element_field, $raw_url, $link_type, $link_target ) {

	global $wpdb;

	// Save link data in eblc_links table
	$link_table_name = $wpdb->prefix . 'eblc_links';
	$link_data = array(
		'url'         => $raw_url,
		'link_type'   => $link_type,
		'link_target' => $link_target
	);
	$wpdb->insert( $link_table_name, $link_data );
	$insert_link_id = $wpdb->insert_id;

	// Save collection data in eblc_collections table
	$collection_table_name = $wpdb->prefix . 'eblc_collections';
	$collection_data = array(
		'link_id'       => $insert_link_id,
		'element_id'    => $element_id,
		'element_type'  => $element_type,
		'link_text'     => $link_text,
		'parser_type'   => $parser_type,
		'element_field' => $element_field,
		'raw_url'       => $raw_url
	);

	$wpdb->insert( $collection_table_name, $collection_data );

	return $insert_link_id;
}

/**
 * Update link in database
 */
function eblc_update_link( $link_id, $http_code, $status_code, $status_text,  $final_url, $check_failure, $redirect_count, $log, $request_duration ) {
	global $wpdb;
	// Update link data in eblc_links table
	$final_url = rawurldecode( $final_url );
	$link_table_name = $wpdb->prefix . 'eblc_links';
	if ( $check_failure ) {
		$link_data = array(
			'http_code'          => $http_code,
			'status_code'        => $status_code,
			'status_text'        => $status_text,
			'final_url'          => $final_url,
			'first_failure'      => date("Y-m-d H:i:s"),
			'last_check_attempt' => date("Y-m-d H:i:s"),
			'redirect_count'     => $redirect_count,
			'log'                => $log,
			'request_duration'   => $request_duration
		);
	} else {
		$link_data = array(
			'http_code'          => $http_code,
			'status_code'        => $status_code,
			'status_text'        => $status_text,
			'final_url'          => $final_url,
			'last_check'         => date("Y-m-d H:i:s"),
			'last_success'       => date("Y-m-d H:i:s"),
			'last_check_attempt' => date("Y-m-d H:i:s"),
			'redirect_count'     => $redirect_count,
			'log'                => $log,
			'request_duration'   => $request_duration
		);
	}
	
	$result = $wpdb->update( $link_table_name, $link_data, array( 'link_id' => $link_id ) );
	return $result;
}

/**
 * Checking all links
 */
function eblc_check_all_links() {
	global $wpdb;
	// Get links data from links table
	$link_table_name = $wpdb->prefix . 'eblc_links';    	
	$links = $wpdb->get_results( "SELECT * FROM {$link_table_name};" );
	foreach ( $links as $link ) {
		// Get url header for checking
		$header = eblc_get_url_header( $link->url );

		if ( $header['http_code'] < 400 && $header['http_code'] >=200 ) {
			$check_failure    = false;
			$http_code        = $header['http_code'];
			$url_status       = eblc_get_url_status( $http_code );
			$status           = eblc_get_url_status( $http_code );
			$redirect_count   = $header['redirect_count'];
			$log              = $header['log'];
			$final_url        = rawurldecode( $header['final_url'] );
			$request_duration = $header['request_duration'];
		} else if ( $header['http_code'] != 0 ) {
			$check_failure    = false;
			$http_code        = $header['http_code'];
			$url_status       = $header['http_message'];
			$redirect_count   = $header['redirect_count'];
			$log              = $header['log'];
			$final_url        = rawurldecode( $header['final_url'] );
			$request_duration = $header['request_duration'];
		} else {	
			$check_failure    = true;
			$final_url        = $link->url;
			$url_status       = 'Server Not Found';
			$status           = 'Server Not Found';
			$http_code        = 404;
			$redirect_count   = 0;
			$log              = 'Server Not Found';
			$request_duration = $header['request_duration'];
		}

		eblc_update_link( $link->link_id, $http_code, $url_status, $status, $final_url, $check_failure, $redirect_count, $log, $request_duration);
	}

	// Checking log table update
	$schedule_checking_log_table_name = $wpdb->prefix . 'eblc_shedule_checking_log';
	$checking_log = $wpdb->get_row( "SELECT * FROM {$schedule_checking_log_table_name};" );
	if ( $checking_log == NULL ) {
		$checking_log_data = array(
			'last_check' => date("Y-m-d H:i:s"),
		);
		$wpdb->insert( $schedule_checking_log_table_name, $checking_log_data );
		$insert_log_id = $wpdb->insert_id;
	} else {
		$id = $checking_log->id;
		$checking_log_data = array(
			'last_check' => date("Y-m-d H:i:s"),
		);
		$wpdb->update( $schedule_checking_log_table_name, $checking_log_data, array( 'id' => $id ) );
	}
}


/**
 * Checking a link
 */
function eblc_check_a_link( $link_id, $new_url = NULL ) {

	global $wpdb;
	// Get link data from links table
	$link_table_name = $wpdb->prefix . 'eblc_links';    	
	$link = $wpdb->get_row( "SELECT * FROM {$link_table_name} WHERE link_id = {$link_id};" );

	// Get url header for checking
	if ( $new_url != NULL ) {
		$url = $new_url;
	} else {
		$url = $link->url;
	}

	$header = eblc_get_url_header( $url );

	if ( $header['http_code'] < 400 && $header['http_code'] >=200 ) {
		$check_failure    = false;
		$http_code        = $header['http_code'];
		$url_status       = eblc_get_url_status( $http_code );
		$status           = eblc_get_url_status( $http_code );
		$redirect_count   = $header['redirect_count'];
		$log              = $header['log'];
		$final_url        = rawurldecode( $header['final_url'] );
		$request_duration = $header['request_duration'];
	} else if ( $header['http_code'] != 0 ) {
		$check_failure    = false;
		$http_code        = $header['http_code'];
		$url_status       = $header['http_message'];
		$redirect_count   = $header['redirect_count'];
		$log              = $header['log'];
		$final_url        = rawurldecode( $header['final_url'] );
		$request_duration = $header['request_duration'];
	} else {	
		$check_failure    = true;
		$final_url        = $url;
		$url_status       = 'Server Not Found';
		$status           = 'Server Not Found';
		$http_code        = 404;
		$redirect_count   = 0;
		$log              = 'Server Not Found';
		$request_duration = $header['request_duration'];
	}

	// Update link data
	eblc_update_link( $link->link_id, $http_code, $url_status, $status, $final_url, $check_failure, $redirect_count, $log, $request_duration );
}

/**
 * Unlink the link
 */
function eblc_unlink_a_link( $link_id ) {
	global $wpdb;
	// Get link data from links table and collection table
	$link_table_name = $wpdb->prefix . 'eblc_links';
	$collection_table_name = $wpdb->prefix . 'eblc_collections';

	// Get link data before update link 
	$old_link_data = $wpdb->get_row( "SELECT link.*, collection.* FROM $link_table_name as link, $collection_table_name as collection WHERE link.link_id = collection.link_id AND link.link_id = {$link_id} ;" );

  	$link_type   = 'dofollow';
  	$link_target = '_self';
  	$parser_type = $old_link_data->parser_type;
  	if ( $parser_type == 'img_src' ) {
  		$link_type = 'image';
  		$link_target = 'image';
  	}

  	// Update element
	eblc_update_element( $link_id, $old_link_data->element_id, $old_link_data->element_type, $old_link_data->url, $old_link_data->link_text, 'javascript:;', $old_link_data->link_text, $link_type, $link_target, true );
}

/**
 * Unlink the links as array
 */
function eblc_unlink_links( $link_ids ) {
	global $wpdb;
	// Get link data from links table and collection table
	$link_table_name = $wpdb->prefix . 'eblc_links';
	$collection_table_name = $wpdb->prefix . 'eblc_collections';

	// Get links data
	$all_links_data = $wpdb->get_results( "SELECT link.*, collection.* FROM $link_table_name as link, $collection_table_name as collection WHERE link.link_id = collection.link_id ;" );

	$element_id_array = array();
	foreach ( $all_links_data as $link_data ) {
		if ( in_array( $link_data->link_id, $link_ids ) ) {
			$element = array(
				'element_id'   => $link_data->element_id,
				'element_type' => $link_data->element_type
			);
			if ( !in_array( $element, $element_id_array ) ) {
				$element_id_item['element_id']   = $link_data->element_id;
				$element_id_item['element_type'] = $link_data->element_type;
				array_push( $element_id_array, $element_id_item );
			}
		}
	}

	$result = 0;

	foreach ( $element_id_array as $element_item ) {
		if ( $element_item['element_type'] != 'comment' ) {
			// Element is post
			$post = get_post( $element_item['element_id'] );
			$post_content = $post->post_content;

			$new_document = new DomDocument('1.0', 'UTF-8');
			libxml_use_internal_errors(true);
			$new_document->loadHTML( mb_convert_encoding( $post_content, 'HTML-ENTITIES', 'UTF-8' ) );
			$all_anchors = $new_document->getElementsByTagName('a');
			$all_imgs = $new_document->getElementsByTagName('img');

			foreach ( $all_anchors as $anchor ) {
				$link_url = $anchor->getAttribute('href');
				$link_text = $anchor->textContent;

				foreach ( $all_links_data as $link_data ) {
					if ( in_array( $link_data->link_id, $link_ids ) ) {
						if ( $link_url == $link_data->url && $link_text == $link_data->link_text ) {

							foreach ( $anchor->childNodes as $child_node ) {
								$anchor->parentNode->insertBefore( $child_node->cloneNode(false), $anchor );
							}

							$anchor->parentNode->removeChild( $anchor );
						}
					}
				}
			}

			foreach ( $all_imgs as $img ) {
				$img_url = $img->getAttribute('src');
				$img_url_split = explode( '/', $img->getAttribute('src') );
				$img_name = $img_url_split[ count( $img_url_split ) - 1 ];

				foreach ( $all_links_data as $link_data ) {
					if ( in_array( $link_data->link_id, $link_ids ) ) {
						if ( $img_url == $link_data->url && $img_name == $link_data->link_text ) {
							$img->parentNode->removeChild( $img );
						}
					}
				}
			}

			$body = $new_document->getElementsByTagName('body')->item(0);
			$body_children = $body->childNodes;
			$innerHTML = "";
			foreach ( $body_children as $child ) 
		    { 
		        $innerHTML .= $body->ownerDocument->saveHTML($child);
		    }

			$update_post_data = array();
	        $update_post_data['ID'] = $element_item['element_id'];
	        $update_post_data['post_content'] = $innerHTML;

	        $result = wp_update_post( $update_post_data );

	    } else {
	    	// Element is comment
			$comment = get_comment( $element_item['element_id'] );
			$comment_content    = $comment->comment_content;
		
			// Comment content
			$new_document = new DomDocument('1.0', 'UTF-8');
			$new_document->loadHTML( mb_convert_encoding( $comment_content, 'HTML-ENTITIES', 'UTF-8' ) );
			$all_anchors = $new_document->getElementsByTagName('a');
			
			foreach ( $all_anchors as $anchor ) {
				$link_url = $anchor->getAttribute('href');
				$link_text = $anchor->textContent;

				foreach ( $all_links_data as $link_data ) {
					if ( in_array( $link_data->link_id, $link_ids ) ) {
						if ( $link_url == $link_data->url && $link_text == $link_data->link_text ) {
							foreach ( $anchor->childNodes as $child_node ) {
								$anchor->parentNode->insertBefore( $child_node->cloneNode(false), $anchor );
							}
							$anchor->parentNode->removeChild( $anchor );
						}
					}
				}
			}

			foreach ( $all_imgs as $img ) {
				$img_url = $img->getAttribute('src');
				$img_url_split = explode( '/', $img->getAttribute('src') );
				$img_name = $img_url_split[ count( $img_url_split ) - 1 ];

				foreach ( $all_links_data as $link_data ) {
					if ( in_array( $link_data->link_id, $link_ids ) ) {
						if ( $img_url == $link_data->url && $img_name == $link_data->link_text ) {
							$img->parentNode->removeChild( $img );
						}
					}
				}
			}

			$body = $new_document->getElementsByTagName( 'body' )->item(0);
			$body_children = $body->childNodes;
			$innerHTML = "";
			foreach ( $body_children as $child ) 
		    { 
		        $innerHTML .= $body->ownerDocument->saveHTML( $child );
		    }

		    $comment = array();
			$comment['comment_ID'] = $element_item['element_id'];
			$comment['comment_content'] = $innerHTML;

			$result = wp_update_comment( $comment );
	    }
	}

	return $result;
}

/**
 * Hook functions when element is saved, deleted, trashed and untrashed 
 */
add_action( 'delete_post', 'eblc_post_deleted' );
add_action( 'post_updated', 'eblc_post_saved');
add_action( 'wp_trash_post', 'eblc_post_deleted');
add_action( 'untrash_post', 'eblc_post_saved' );
add_action( 'comment_post', 'eblc_comment_saved' );
add_action( 'delete_comment', 'eblc_comment_deleted' );
add_action( 'edit_comment', 'eblc_comment_saved' );
add_action( 'untrash_comment', 'eblc_comment_saved' );
add_action( 'trash_comment', 'eblc_comment_deleted' );
add_action( 'update_option_eblc_link_options', 'eblc_sync_links_with_option_update' );

/**
 * Callback function define for delete_post and trash_post hook
 */
function eblc_post_deleted( $post_id ) {
	global $wpdb;

	$post = get_post( $post_id );
	$post_type = $post->post_type;

	$link_table_name = $wpdb->prefix . 'eblc_links';
	$collection_table_name = $wpdb->prefix . 'eblc_collections';
	// Get link ids from collection table with element id and element type
	$link_ids = $wpdb->get_results( "SELECT link_id FROM {$collection_table_name} WHERE element_id = {$post_id} AND element_type = '{$post_type}';" );

	foreach ($link_ids as $link) {
		$link_id = (int)$link->link_id;
		// Delete link row from link table
		$wpdb->query( "DELETE  FROM {$link_table_name} WHERE link_id = {$link_id};" );
	}
	// Delete collection from collection table
	$wpdb->query( "DELETE  FROM {$collection_table_name} WHERE element_id = {$post_id} AND element_type = '{$post_type}';" );
}

/**
 * Callback function define for save_post hook
 */
function eblc_post_saved( $post_id ) { 
	$post = get_post( $post_id );
	
	$post_types = get_post_types( array(), 'objects' );
	$exceptions = array( 'revision', 'nav_menu_item', 'attachment' );
	$enable_post_types = array();
	foreach( $post_types as $data ) {
		$post_type = $data->name;
		
		if ( in_array( $post_type, $exceptions ) ) {
			continue;
		}
		array_push( $enable_post_types, $post_type );
	}

	if ( in_array( $post->post_type, $enable_post_types ) ) {
		if( $post->post_status != 'trash' and $post->post_status != 'auto-draft' ) {
			eblc_post_deleted( $post_id );			
            eblc_check_and_save_urls_in_post( $post, true );
    	}			
	}
}

/**
 * Callback function define for delete_comment and trash_comment hook
 */
function eblc_comment_deleted( $comment_id ) {
	global $wpdb;
	$link_table_name = $wpdb->prefix . 'eblc_links';
	$collection_table_name = $wpdb->prefix . 'eblc_collections';
	// Get link ids from collection table with element id and element type
	$link_ids = $wpdb->get_results( "SELECT link_id FROM {$collection_table_name} WHERE element_id = {$comment_id} AND element_type = 'comment';" );
	foreach ($link_ids as $link) {
		$link_id = $link->link_id;
		// Delete link from link table
		$wpdb->query( "DELETE  FROM {$link_table_name} WHERE link_id = {$link_id};" );
	}
	// Delete collection from collection table
	$wpdb->query( "DELETE  FROM {$collection_table_name} WHERE element_id = {$comment_id} AND element_type = 'comment';" );
}

/**
 * Callback function define for comment_post and untrash_comment hook
 */
function eblc_comment_saved( $comment_id ) {
	$comment = get_comment( $comment_id );
	// Delete exsited links in comment
	eblc_comment_deleted( $comment_id );
	// Check all urls in comment and save them
	eblc_check_and_save_urls_in_comment( $comment, true );
}


/**
 * Action ajax hook functions
 * that are called on links table actions
 */
add_action( 'wp_ajax_eblc_unlink_url', 'eblc_unlink_a_link_for_ajax' );
add_action( 'wp_ajax_eblc_recheck_url', 'eblc_check_a_link_for_ajax' );
add_action( 'wp_ajax_eblc_get_link_data', 'eblc_get_link_data_a_link_for_ajax' );
add_action( 'wp_ajax_eblc_update_link', 'eblc_update_link_for_ajax' );

/**
 * Unlink a link for ajax function
 */
function eblc_unlink_a_link_for_ajax() {

	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not unlink that link.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not unlink that link.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not unlink that link.")
			));
		wp_die();
	}

	if ( !isset( $_POST['link_id'] ) || !is_numeric( $_POST['link_id'] ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("link_id is not valid. Refresh and try again.")
			));
		wp_die();
	}

	$link_id = $_POST['link_id'];

	$link_ids = array();
	$link_ids[0] = $link_id;

	$result = eblc_unlink_links( $link_ids );

	if ( $result ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Get a link data for ajax function
 */
function eblc_get_link_data_a_link_for_ajax() {

	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not unlink that link.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not unlink that link.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not unlink that link.")
			));
		wp_die();
	}

	if ( !isset( $_POST['link_id'] ) || !is_numeric( $_POST['link_id'] ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("link_id is not valid. Refresh and try again.")
			));
		wp_die();
	}

	$link_id = $_POST['link_id'];

	global $wpdb;
	// Get links data from links table
	$link_table_name = $wpdb->prefix . 'eblc_links';    	
	$link = $wpdb->get_row( "SELECT * FROM {$link_table_name} WHERE link_id = {$link_id};" );

	if ( $link ) {
		$result = array(
	    	'success' => 1,
	    	'link'   => $link
	    );
	} else {
	  	$result = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
	}

    $result = json_encode( $result );
    echo $result;
    wp_die();
}


/**
 * Check a link for ajax function
 */
function eblc_check_a_link_for_ajax() {

	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not unlink that link.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not unlink that link.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not unlink that link.")
			));
		wp_die();
	}

	if ( !isset( $_POST['link_id'] ) || !is_numeric( $_POST['link_id'] ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("link_id is not valid. Refresh and try again.")
			));
		wp_die();
	}

	$link_id = $_POST['link_id'];

	global $wpdb;
	// Get link data from links table
	$link_table_name = $wpdb->prefix . 'eblc_links';    	
	$link = $wpdb->get_row( "SELECT * FROM {$link_table_name} WHERE link_id = {$link_id};" );
	// Get url header for checking
	$url = $link->url;

	$header = eblc_get_url_header( $url );

	if ( $header['http_code'] < 400 && $header['http_code'] >=200 ) {
		$check_failure    = false;
		$http_code        = $header['http_code'];
		$url_status       = eblc_get_url_status( $http_code );
		$status           = eblc_get_url_status( $http_code );
		$redirect_count   = $header['redirect_count'];
		$log              = $header['log'];
		$final_url        = rawurldecode( $header['final_url'] );
		$request_duration = $header['request_duration'];
	} else if ( $header['http_code'] != 0 ) {
		$check_failure    = false;
		$http_code        = $header['http_code'];
		$url_status       = $header['http_message'];
		$redirect_count   = $header['redirect_count'];
		$log              = $header['log'];
		$final_url        = rawurldecode( $header['final_url'] );
		$request_duration = $header['request_duration'];
	} else {	
		$check_failure    = true;
		$final_url        = $url;
		$url_status       = 'Server Not Found';
		$status           = 'Server Not Found';
		$http_code        = 404;
		$redirect_count   = 0;
		$log              = 'Server Not Found';
		$request_duration = $header['request_duration'];
	}

	$result = eblc_update_link( $link->link_id, $http_code, $url_status, $status, $final_url, $check_failure, $redirect_count, $log, $request_duration );
	
	if ( $result ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
		$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Update link for ajax function
 */
function eblc_update_link_for_ajax() {
	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not unlink that link.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not unlink that link.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not unlink that link.")
			));
		wp_die();
	}

	if ( !isset( $_POST['link_id'] ) || !is_numeric( $_POST['link_id'] ) || !isset( $_POST['link_url'] ) || !isset( $_POST['link_text'] ) || !isset( $_POST['link_type'] )|| !isset( $_POST['link_target'] )) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("link_id is not valid. Refresh and try again.")
			));
		wp_die();
	}

	$link_id     = $_POST['link_id'];
	$link_url    = esc_url_raw( sanitize_text_field( $_POST['link_url'] ) );
	$link_text   = sanitize_text_field( $_POST['link_text'] );
	$link_rel    = sanitize_text_field( $_POST['link_type'] );
	$link_target = sanitize_text_field( $_POST['link_target'] );

	global $wpdb;
	// Update link data in eblc_links table
	$link_table_name = $wpdb->prefix . 'eblc_links';
	$collection_table_name = $wpdb->prefix . 'eblc_collections';

	// Get link data before update link 
	$old_link_data = $wpdb->get_row( "SELECT link.*, collection.* FROM $link_table_name as link, $collection_table_name as collection WHERE link.link_id = collection.link_id AND link.link_id = {$link_id} ;" );

	if ( get_option( 'eblc_options' ) != '' ) {
    	$eblc_options = get_option( 'eblc_options' );
    }

	// Update element
	$result = eblc_update_element( $link_id, $old_link_data->element_id, $old_link_data->element_type, $old_link_data->url, $old_link_data->link_text, $link_url, $link_text, $link_rel, $link_target );
	if ( $result ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
		$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
	}	

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Update element function
 */
function eblc_update_element( $link_id, $element_id, $element_type, $old_link_url, $old_link_text, $new_link_url, $new_link_text, $new_link_rel, $new_link_target, $unlink = NULL ) {

	if ( get_option( 'eblc_options' ) != '' ) {
    	$eblc_options = get_option( 'eblc_options' );
    }

	if ( $element_type != 'comment' ) {
		// Element is post
		$post = get_post( $element_id );
		$post_content = $post->post_content;

		$new_document = new DomDocument('1.0', 'UTF-8');
		$new_document->loadHTML( mb_convert_encoding( $post_content, 'HTML-ENTITIES', 'UTF-8' ) );
		$all_anchors = $new_document->getElementsByTagName('a');
		$all_imgs = $new_document->getElementsByTagName('img');

		if ( $new_link_rel != 'image' ) {
			foreach ( $all_anchors as $anchor ) {
				$link_url = $anchor->getAttribute('href');
				$link_text = $anchor->textContent;

				if ( $link_url == $old_link_url && $link_text == $old_link_text ) {
					$anchor->setAttribute( 'href', $new_link_url );
					$anchor->textContent = $new_link_text;

					$anchor->removeAttribute( 'target' );
					if ( $new_link_target == '_blank' ) {
			    		$anchor->setAttribute( 'target', '_blank' );
			    	} 
			    	$anchor->removeAttribute( 'rel' );
			    	if ( $new_link_rel == 'nofollow' ) {
			    		$anchor->setAttribute( 'rel', 'nofollow' );
			    	} 		
				}
			}
		} else {
			foreach ( $all_imgs as $img ) {
				$link_url = $img->getAttribute('src');

				if ( $link_url == $old_link_url ) {
					if ( $unlink == NULL ){
						// Replace src
						$img->setAttribute( 'src', $new_link_url );
					} else {
						// Remove image tag
						$img->parentNode->removeChild($img);					
					}					
				}
			}
		}

		$body = $new_document->getElementsByTagName('body')->item(0);
		$body_children = $body->childNodes;
		$innerHTML = "";
		foreach ( $body_children as $child ) 
	    { 
	        $innerHTML .= $body->ownerDocument->saveHTML( $child );
	    }

		$update_post_data = array();
        $update_post_data['ID'] = $element_id;
        $update_post_data['post_content'] = $innerHTML;
        $result = wp_update_post( $update_post_data );
        return $result;
	} else {
		// Element is comment
		$comment = get_comment( $element_id );
		$comment_content    = $comment->comment_content;
		$comment_author     = $comment->comment_author;
		$comment_author_url = $comment->comment_author_url;

		if ( !( $old_link_url == $comment_author_url && $old_link_text == $comment_author ) ) {		
			// Comment content
			$new_document = new DomDocument('1.0', 'UTF-8');
			$new_document->loadHTML( mb_convert_encoding( $comment_content, 'HTML-ENTITIES', 'UTF-8' ) );
			$all_anchors = $new_document->getElementsByTagName('a');
			$all_imgs = $new_document->getElementsByTagName('img');

			if ( $new_link_rel != 'image' ) {
				foreach ( $all_anchors as $anchor ) {
					$link_url = $anchor->getAttribute('href');
					$link_text = $anchor->textContent;

					if ( $link_url == $old_link_url && $link_text == $old_link_text ) {
						$anchor->setAttribute( 'href', $new_link_url );
						$anchor->textContent = $new_link_text;

						if ( $link_url == $old_link_url && $link_text == $old_link_text ) {
							$anchor->setAttribute( 'href', $new_link_url );
							$anchor->textContent = $new_link_text;
							
							$anchor->removeAttribute( 'target' );
							if ( $new_link_target == '_blank' ) {
					    		$anchor->setAttribute( 'target', '_blank' );
					    	} 
					    	$anchor->removeAttribute( 'rel' );
					    	if ( $new_link_rel == 'nofollow' ) {
					    		$anchor->setAttribute( 'rel', 'nofollow' );
					    	} 
						}			
					}
				}
			} else {
				foreach ( $all_imgs as $img ) {
					$link_url = $img->getAttribute('src');

					if ( $link_url == $old_link_url ) {
						if ( $unlink == NULL ){
							// Replace src
							$img->setAttribute( 'src', $new_link_url );
						} else {
							// Remove image tag
							$img->parentNode->removeChild($img);					
						}
					}
				}
			}

			$body = $new_document->getElementsByTagName('body')->item(0);
			$body_children = $body->childNodes;
			$innerHTML = "";
			foreach ( $body_children as $child ) 
		    { 
		        $innerHTML .= $body->ownerDocument->saveHTML($child);
		    }

		    $comment = array();
			$comment['comment_ID'] = $element_id;
			$comment['comment_content'] = $innerHTML;
			$result = wp_update_comment( $comment );
			return $result;
		} else {
			// Comment author
			$new_comment_author = $new_link_text;
			$new_comment_author_url = $new_link_url;

			$comment = array();
			$comment['comment_ID'] = $element_id;
			$comment['comment_author'] = $new_comment_author;
			$comment['comment_author_url'] = $new_comment_author_url;
			$result = wp_update_comment( $comment );
			return $result;
		}
	}
}

/**
 * Catch the current microtime
 */
function eblc_microtime_count() {
    list( $usec, $sec ) = explode( " ", microtime() );
    return ( (float)$usec + (float)$sec );
}

/**
 * Send email notification to adminstrator or author
 */
function eblc_send_email_notification() {

	global $wpdb;

	$link_table_name = $wpdb->prefix . 'eblc_links';
	$collection_table_name = $wpdb->prefix . 'eblc_collections';

	$broken_links = $wpdb->get_results( "SELECT link.*, collection.* FROM $link_table_name as link, $collection_table_name as collection WHERE link.link_id = collection.link_id AND link.http_code >= 400;" );

	// Get admin email
	$admin_email = "";
	if ( get_option( 'admin_email' ) != '' ) {
		$admin_email = sanitize_email( get_option( 'admin_email' ) );
	}

	// Get custom admin email and Option with Send e-mail notifications about newly detected broken links
	$admin_custom_email = "";
	$send_email_notifications = true;
	$send_authors_email_notifications = false;
	if ( get_option( 'eblc_options' ) != '' ) {
    	$eblc_options = get_option( 'eblc_options' );
    	if ( $eblc_options['notification_email_address'] != '' ) {
    		$admin_custom_email = sanitize_email( $eblc_options['notification_email_address'] );
    	}

    	$send_email_notifications_control = $eblc_options['send_email_notifications_control'];
    	$send_email_notifications = $eblc_options['send_email_notifications'];
    	$send_authors_email_notifications = $eblc_options['send_authors_email_notifications'];
    }

    if ( $admin_custom_email != '' ) {
    	$admin_email = $admin_custom_email;
    }

    // Send email to admin with broken link info
    if ( $admin_email != '' && count( $broken_links ) > 0 && $send_email_notifications && $send_email_notifications_control && filter_var( $admin_email, FILTER_VALIDATE_EMAIL ) ) {

    	$mail_header = "Broken Links Found";
    	$mail_body   = "Hello Dear,<br><br>" . 
					   "Easy Broken Link Checker has deteted " . count( $broken_links ) . " new broken links on your site. <br>" . 
					   "Here's a list of the new broken links: <br><br>";

    	foreach ( $broken_links as $link ) {
    		$url          = $link->url;
	    	$element_id   = $link->element_id;
	    	$element_type = $link->element_type;
	    	$guid = '';
	    	if ( $element_type != 'comment' ) {
	    		$post = get_post( $element_id );
	    		$guid = $post->guid;
	    		$source = $post->post_title;
	    	} else {
	    		$guid = get_comments_link( $element_id );
	    		$comment = get_comment( $element_id );
				$comment_author  = $comment->comment_author;
				$comment_content = $comment->comment_content;
				$source = $comment_author . ' -- ' . substr( strip_tags( $comment_content ), 0, 50 );
	    	}

	    	$mail_body .= "Anchor text : " . esc_html( $link->link_text ) . "<br>" .
					   	  "Link URL : <a href='" . esc_url( $link->url ) . "'>" . rawurldecode( $link->url ) . "</a><br>" . 
					      "Source   : <a href='" . esc_url( $guid ) . "'>" . esc_html( $source ) . "</a><br><br>";

		}

		$mail_body .= "You can see all broken links here: <a href='" . esc_url( admin_url('admin.php?page=page=eblc_check_options&link_status=broken') ) . "'> Detected broken links page </a><br><br>" .  

					  "Regards <br>" . 
					  "Easy Broken Link Checker Team <br>";
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

		wp_mail( $admin_email, $mail_header, $mail_body, $headers );
    }

    // Send email to author with broken link info
    if ( count( $broken_links ) > 0 && $send_authors_email_notifications && $send_email_notifications_control ) {

    	foreach ( $broken_links as $link ) {
    		$url          = $link->url;
	    	$element_id   = $link->element_id;
	    	$element_type = $link->element_type;
	    	$author_email = '';
	    	$guid = '';
	    	if ( $element_type != 'comment' ) {
	    		$post = get_post( $element_id );
	    		$author_id = $post->post_author;
	    		$author_email = sanitize_email( get_the_author_meta( 'user_email', $author_id ) );
	    		$guid = $post->guid;
	    		$source = $post->post_title;
   		
	    	} else {
	    		$author_email = sanitize_email(  get_comment_author_email( $element_id ) );
	    		$guid = get_comments_link( $element_id );
	    		$comment = get_comment( $element_id );
				$comment_author  = $comment->comment_author;
				$comment_content = $comment->comment_content;
				$source = $comment_author . ' -- ' . substr( strip_tags( $comment_content ), 0, 50 );
	    	}

	    	if ( $author_email != '' &&  filter_var( $author_email, FILTER_VALIDATE_EMAIL ) ) {
    			$mail_header = "Broken Link Found";

				$mail_body   = "Hello Dear,<br><br>" . 
							   "Easy Broken Link Checker has deteted 1 new broken link on your site. <br>" . 
							   "Here's the new broken link detail: <br><br>" . 

							   "Anchor text : " . esc_html( $link->link_text ) . "<br>" .
							   "Link URL : <a href='" . esc_url( $link->url ) . "'>" . rawurldecode( $link->url ) . "</a><br>" . 
							   "Source   : <a href='" . esc_url( $guid ) . "'>" . esc_html( $source ) . "</a><br><br><br>" . 

							   "You can see all broken links here: <a href='" . esc_url( admin_url('admin.php?page=page=eblc_check_options&link_status=broken') ) . "'> Detected broken links page </a><br><br>" . 
							   "Regards <br>" .
							   "Easy Broken Link Checker Team <br>";

				$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

				wp_mail( $author_email, $mail_header, $mail_body, $headers );
    		}
    	}		
    }
}


/**
 * Synchronize link data
 */
function eblc_sync_links() {
	global $wpdb;
	$link_table_name = $wpdb->prefix . 'eblc_links';
	$collection_table_name = $wpdb->prefix . 'eblc_collections';
	// Get links data  
	$all_links_data = $wpdb->get_results( "SELECT link.*, collection.* FROM $link_table_name as link, $collection_table_name as collection WHERE link.link_id = collection.link_id AND link.link_type != 'image' ;" );

	if ( get_option( 'eblc_options' ) != '' ) {
    	$eblc_options = get_option( 'eblc_options' );
    }

    $element_id_array = array();
	foreach ( $all_links_data as $link_data ) {
		$element = array(
			'element_id'   => $link_data->element_id,
			'element_type' => $link_data->element_type
		);
		if ( !in_array( $element, $element_id_array ) ) {
			$element_id_item['element_id']   = $link_data->element_id;
			$element_id_item['element_type'] = $link_data->element_type;
			array_push( $element_id_array, $element_id_item );
		}
	}

	foreach ( $element_id_array as $element_item ) {
		if ( $element_item['element_type'] != 'comment' ) {
			// Element is post
			$post = get_post( $element_item['element_id'] );
			$post_content = $post->post_content;

			$new_document = new DomDocument('1.0', 'UTF-8');
			$new_document->loadHTML( mb_convert_encoding( $post_content, 'HTML-ENTITIES', 'UTF-8' ) );
			$all_anchors = $new_document->getElementsByTagName('a');

			foreach ( $all_anchors as $anchor ) {
				$link_url = $anchor->getAttribute('href');
				$link_text = $anchor->textContent;

				if ( $anchor->getAttribute('target') ) {
					$link_target = $anchor->getAttribute('target');
				} else {
					$link_target = '_self';
				}

				if ( $anchor->getAttribute('rel') ) {
					$rel = $anchor->getAttribute('rel');
					$rel_array = explode(' ', $rel);
					if ( in_array( 'nofollow', $rel_array ) ) {
						$link_rel = 'nofollow';
					} else {
						$link_rel = 'dofollow';
					}
				} else {
					$link_rel = 'dofollow';
				}

				foreach ( $all_links_data as $link_data ) {
					if ( $link_data->element_id == $element_item['element_id'] ) {
						if ( $link_url == $link_data->url && $link_text == $link_data->link_text ) {

							if ( $eblc_options != NULL ) {
								$server_host = $_SERVER['HTTP_HOST'];
								if ( strpos( $link_url, $server_host ) !== false ) {
									// For internal link
									$internal_target_check     = $eblc_options['eblc-internal-link-settings']['target'];
									$internal_target_overwrite = $eblc_options['eblc-internal-link-settings']['target_overwrite'];
									$internal_rel_follow       = $eblc_options['eblc-internal-link-settings']['rel_follow'];
									$internal_rel_overwrite    = $eblc_options['eblc-internal-link-settings']['rel_overwrite'];

									if ( $internal_target_overwrite == '1' ) {
										if ( $internal_target_check == '1' ) {
											$link_target = '_blank';
										} else {
											$link_target = '_self';
										}
									}

									if ( $internal_rel_overwrite == '1' ) {
										$link_rel = $internal_rel_follow;
									}
								} else {
									// For external link
									$external_target_check     = $eblc_options['eblc-external-link-settings']['target'];
									$external_target_overwrite = $eblc_options['eblc-external-link-settings']['target_overwrite'];
									$external_rel_follow       = $eblc_options['eblc-external-link-settings']['rel_follow'];
									$external_rel_overwrite    = $eblc_options['eblc-external-link-settings']['rel_overwrite'];

									if ( $external_target_overwrite == '1' ) {
										if ( $external_target_check == '1' ) {
											$link_target = '_blank';
										} else {
											$link_target = '_self';
										}
									}

									if ( $external_rel_overwrite == '1' ) {
										$link_rel = $external_rel_follow;
									}			
								}
							} else {
								$link_target = '_self';
								$link_rel = 'dofollow';
							}

							$anchor->removeAttribute( 'target' );
							if ( $link_target == '_blank' ) {
								$anchor->setAttribute( 'target', $link_target );
							}
				    		
				    		$anchor->removeAttribute( 'rel' );
				    		if ( $link_rel == 'nofollow' ) {
				    			$anchor->setAttribute( 'rel', $link_rel );
				    		}
				    	}
					}
				}				
			}

			$body = $new_document->getElementsByTagName( 'body' )->item(0);
			$body_children = $body->childNodes;
			$innerHTML = "";
			foreach ( $body_children as $child ) 
		    { 
		        $innerHTML .= $body->ownerDocument->saveHTML($child);
		    }

			$update_post_data = array();
	        $update_post_data['ID'] = $element_item['element_id'];
	        $update_post_data['post_content'] = $innerHTML;
	        wp_update_post( $update_post_data );

	    } else {
	    	// Element is comment
			$comment = get_comment( $element_item['element_id'] );
			$comment_content    = $comment->comment_content;
		
			// Comment content
			$new_document = new DomDocument('1.0', 'UTF-8');
			$new_document->loadHTML( mb_convert_encoding( $comment_content, 'HTML-ENTITIES', 'UTF-8' ) );
			$all_anchors = $new_document->getElementsByTagName('a');
			
			foreach ( $all_anchors as $anchor ) {
				$link_url = $anchor->getAttribute('href');
				$link_text = $anchor->textContent;

				if ( $anchor->getAttribute('target') ) {
					$link_target = $anchor->getAttribute('target');
				} else {
					$link_target = '_self';
				}

				if ( $anchor->getAttribute('rel') ) {
					$rel = $anchor->getAttribute('rel');
					$rel_array = explode(' ', $rel);
					if ( in_array( 'nofollow', $rel_array ) ) {
						$link_rel = 'nofollow';
					} else {
						$link_rel = 'dofollow';
					}
				} else {
					$link_rel = 'dofollow';
				}

				foreach ( $all_links_data as $link_data ) {
					if ( $link_data->element_id == $element_item['element_id'] ) {
						if ( $link_url == $link_data->url && $link_text == $link_data->link_text ) {

							if ( $eblc_options != NULL ) {
								$server_host = $_SERVER['HTTP_HOST'];
								if ( strpos( $link_url, $server_host ) !== false ) {
									// For internal link
									$internal_target_check     = $eblc_options['eblc-internal-link-settings']['target'];
									$internal_target_overwrite = $eblc_options['eblc-internal-link-settings']['target_overwrite'];
									$internal_rel_follow       = $eblc_options['eblc-internal-link-settings']['rel_follow'];
									$internal_rel_overwrite    = $eblc_options['eblc-internal-link-settings']['rel_overwrite'];

									if ( $internal_target_overwrite == '1' ) {
										if ( $internal_target_check == '1' ) {
											$link_target = '_blank';
										} else {
											$link_target = '_self';
										}
									}

									if ( $internal_rel_overwrite == '1' ) {
										$link_rel = $internal_rel_follow;
									}
								} else {
									// For external link
									$external_target_check     = $eblc_options['eblc-external-link-settings']['target'];
									$external_target_overwrite = $eblc_options['eblc-external-link-settings']['target_overwrite'];
									$external_rel_follow       = $eblc_options['eblc-external-link-settings']['rel_follow'];
									$external_rel_overwrite    = $eblc_options['eblc-external-link-settings']['rel_overwrite'];

									if ( $external_target_overwrite == '1' ) {
										if ( $external_target_check == '1' ) {
											$link_target = '_blank';
										} else {
											$link_target = '_self';
										}
									}

									if ( $external_rel_overwrite == '1' ) {
										$link_rel = $external_rel_follow;
									}			
								}
							} else {
								$link_target = '_self';
								$link_rel = 'dofollow';
							}

							$anchor->removeAttribute( 'target' );
							if ( $link_target == '_blank' ) {
								$anchor->setAttribute( 'target', $link_target );
							}
				    		
				    		$anchor->removeAttribute( 'rel' );
				    		if ( $link_rel == 'nofollow' ) {
				    			$anchor->setAttribute( 'rel', $link_rel );
				    		}
						}
					}
				}				
			}

			$body = $new_document->getElementsByTagName( 'body' )->item(0);
			$body_children = $body->childNodes;
			$innerHTML = "";
			foreach ( $body_children as $child ) 
		    { 
		        $innerHTML .= $body->ownerDocument->saveHTML($child);
		    }

		    $comment = array();
			$comment['comment_ID'] = $element_item['element_id'];
			$comment['comment_content'] = $innerHTML;
			wp_update_comment( $comment );
		}
	}
}

/**
 * Synchronize link data (new)
 */
function eblc_sync_links_with_option_update() {
	global $wpdb;
	$link_table_name = $wpdb->prefix . 'eblc_links';
	$collection_table_name = $wpdb->prefix . 'eblc_collections';
	// Get links data  
	$all_links_data = $wpdb->get_results( "SELECT link.*, collection.* FROM $link_table_name as link, $collection_table_name as collection WHERE link.link_id = collection.link_id AND link.link_type != 'image' ;" );

	if ( get_option( 'eblc_link_options' ) != '' ) {
    	$eblc_options = get_option( 'eblc_link_options' );
    }

    $element_id_array = array();
	foreach ( $all_links_data as $link_data ) {
		$element = array(
			'element_id'   => $link_data->element_id,
			'element_type' => $link_data->element_type
		);
		if ( !in_array( $element, $element_id_array ) ) {
			$element_id_item['element_id']   = $link_data->element_id;
			$element_id_item['element_type'] = $link_data->element_type;
			array_push( $element_id_array, $element_id_item );
		}
	}

	foreach ( $element_id_array as $element_item ) {
		if ( $element_item['element_type'] != 'comment' ) {
			// Element is post
			$post = get_post( $element_item['element_id'] );
			$post_content = $post->post_content;

			$new_document = new DomDocument('1.0', 'UTF-8');
			$new_document->loadHTML( mb_convert_encoding( $post_content, 'HTML-ENTITIES', 'UTF-8' ) );
			$all_anchors = $new_document->getElementsByTagName('a');

			foreach ( $all_anchors as $anchor ) {
				$link_url = $anchor->getAttribute('href');
				$link_text = $anchor->textContent;

				if ( $anchor->getAttribute('target') ) {
					$link_target = $anchor->getAttribute('target');
				} else {
					$link_target = '_self';
				}

				if ( $anchor->getAttribute('rel') ) {
					$rel = $anchor->getAttribute('rel');
					$rel_array = explode(' ', $rel);
					if ( in_array( 'nofollow', $rel_array ) ) {
						$link_rel = 'nofollow';
					} else {
						$link_rel = 'dofollow';
					}
				} else {
					$link_rel = 'dofollow';
				}

				foreach ( $all_links_data as $link_data ) {
					if ( $link_data->element_id == $element_item['element_id'] ) {
						if ( $link_url == $link_data->url && $link_text == $link_data->link_text ) {

							if ( $eblc_options != NULL ) {
								$server_host = $_SERVER['HTTP_HOST'];
								if ( strpos( $link_url, $server_host ) !== false ) {
									// For internal link									
									$internal_rel_follow       = $eblc_options['internal_link_follow_setting'];
									$internal_target_check     = $eblc_options['internal_link_target_setting'];

									if ( $internal_target_check == '1' ) {
										$link_target = '_blank';
									} else {
										$link_target = '_self';
									}

									$link_rel = $internal_rel_follow;
								} else {
									// For external link
									$external_rel_follow       = $eblc_options['external_link_follow_setting'];
									$external_target_check     = $eblc_options['external_link_target_setting'];

									if ( $external_target_check == '1' ) {
										$link_target = '_blank';
									} else {
										$link_target = '_self';
									}

									$link_rel = $external_rel_follow;			
								}
							} else {
								$link_target = '_self';
								$link_rel = 'dofollow';
							}

							$anchor->removeAttribute( 'target' );
							if ( $link_target == '_blank' ) {
								$anchor->setAttribute( 'target', $link_target );
							}
				    		
				    		$anchor->removeAttribute( 'rel' );
				    		if ( $link_rel == 'nofollow' ) {
				    			$anchor->setAttribute( 'rel', $link_rel );
				    		}
				    	}
					}
				}				
			}

			$body = $new_document->getElementsByTagName( 'body' )->item(0);
			$body_children = $body->childNodes;
			$innerHTML = "";
			foreach ( $body_children as $child ) 
		    { 
		        $innerHTML .= $body->ownerDocument->saveHTML($child);
		    }

			$update_post_data = array();
	        $update_post_data['ID'] = $element_item['element_id'];
	        $update_post_data['post_content'] = $innerHTML;
	        wp_update_post( $update_post_data );

	    } else {
	    	// Element is comment
			$comment = get_comment( $element_item['element_id'] );
			$comment_content    = $comment->comment_content;
		
			// Comment content
			$new_document = new DomDocument('1.0', 'UTF-8');
			$new_document->loadHTML( mb_convert_encoding( $comment_content, 'HTML-ENTITIES', 'UTF-8' ) );
			$all_anchors = $new_document->getElementsByTagName('a');
			
			foreach ( $all_anchors as $anchor ) {
				$link_url = $anchor->getAttribute('href');
				$link_text = $anchor->textContent;

				if ( $anchor->getAttribute('target') ) {
					$link_target = $anchor->getAttribute('target');
				} else {
					$link_target = '_self';
				}

				if ( $anchor->getAttribute('rel') ) {
					$rel = $anchor->getAttribute('rel');
					$rel_array = explode(' ', $rel);
					if ( in_array( 'nofollow', $rel_array ) ) {
						$link_rel = 'nofollow';
					} else {
						$link_rel = 'dofollow';
					}
				} else {
					$link_rel = 'dofollow';
				}

				foreach ( $all_links_data as $link_data ) {
					if ( $link_data->element_id == $element_item['element_id'] ) {
						if ( $link_url == $link_data->url && $link_text == $link_data->link_text ) {

							if ( $eblc_options != NULL ) {
								$server_host = $_SERVER['HTTP_HOST'];
								if ( strpos( $link_url, $server_host ) !== false ) {
									// For internal link									
									$internal_rel_follow       = $eblc_options['internal_link_follow_setting'];
									$internal_target_check     = $eblc_options['internal_link_target_setting'];

									if ( $internal_target_check == '1' ) {
										$link_target = '_blank';
									} else {
										$link_target = '_self';
									}

									$link_rel = $internal_rel_follow;
								} else {
									// For external link
									$external_rel_follow       = $eblc_options['external_link_follow_setting'];
									$external_target_check     = $eblc_options['external_link_target_setting'];

									if ( $external_target_check == '1' ) {
										$link_target = '_blank';
									} else {
										$link_target = '_self';
									}

									$link_rel = $external_rel_follow;			
								}
							} else {
								$link_target = '_self';
								$link_rel = 'dofollow';
							}

							$anchor->removeAttribute( 'target' );
							if ( $link_target == '_blank' ) {
								$anchor->setAttribute( 'target', $link_target );
							}
				    		
				    		$anchor->removeAttribute( 'rel' );
				    		if ( $link_rel == 'nofollow' ) {
				    			$anchor->setAttribute( 'rel', $link_rel );
				    		}
						}
					}
				}				
			}

			$body = $new_document->getElementsByTagName( 'body' )->item(0);
			$body_children = $body->childNodes;
			$innerHTML = "";
			foreach ( $body_children as $child ) 
		    { 
		        $innerHTML .= $body->ownerDocument->saveHTML($child);
		    }

		    $comment = array();
			$comment['comment_ID'] = $element_item['element_id'];
			$comment['comment_content'] = $innerHTML;
			wp_update_comment( $comment );
		}
	}
}



/**
 * Get url status with http_code
 */
function eblc_get_url_status( $http_code ) {

	if ( $http_code >= 200 && $http_code < 300 ) {
		return $http_code . ' OK';
	} else if ( $http_code >= 300 && $http_code < 400 ) {
		return $http_code . ' OK';
	} else {
		return '404 Not Found';
	}
}

/**
 * Format log data
 */
function eblc_format_log( $log ) {
    $format_log_str = "<br>";
    foreach ( $log as $key => $value ) {
        if ( is_numeric( $key ) ) {
            continue;
        }

        if ( is_array( $value ) ) {
            if ( $key != "Set-Cookie" ) {
                $format_log_str .= $key . ": " . $value[0] . "<br>";
            } else {
                foreach ( $value as $inner_value ) {
                    $format_log_str .= $key . ": " . $inner_value . "<br>";
                }
            }
        } else {
            $format_log_str .= $key . ": " . $value . "<br>";
        }
    }
    return $format_log_str;
}


/**
 * Get url header
 */
function eblc_get_url_header( $url ) {

	if ( get_option('eblc_options') != '' ) {
    	$eblc_options = get_option('eblc_options');
    	if ( $eblc_options['timeout'] == '' ) {
    		$timeout = 30;
    	} else {
    		$timeout = (int) $eblc_options['timeout'];
    	} 
    } else {
    	// Defualt timeout - 30 seconds
    	$timeout = 30;
    }

    $arg = array(
    	'timeout' => $timeout
    );

    $start_time       = eblc_microtime_count();
    // Get http api response of url
	$response         = wp_remote_get( $url, $arg );

	$request_duration = eblc_microtime_count() - $start_time;

	if( is_array( $response ) ) {
		// Get response
		$response_headers = wp_remote_retrieve_headers( $response );
		$http_code        = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$header_response  = $response['http_response'];
		$final_url        = $header_response->get_response_object()->url;
		$redirects        = $header_response->get_response_object()->redirects;		
		$protocol_version = $header_response->get_response_object()->protocol_version;

		$log = 'HTTP/' . $protocol_version . ' ' . $http_code . ' ' . $response_message;
		$log = $log . eblc_format_log( $response_headers );
	} else {
		// Get error
		$http_code        = 0;
		$response_message = 'Server Not Found';
		$final_url        = '';
		$redirects        = 0;
		$log              = '';
	}

	$result['http_code']        = $http_code;
	$result['http_message']     = $http_code . ' ' . $response_message;
    $result['final_url']        = $final_url;
    $result['request_duration'] = $request_duration;
    $result['redirect_count']   = $redirects;
	$result['log']              = rawurldecode( $log );

	return $result;
}


/**
 * Action ajax hook functions for URL Shorten Part
 */
add_action( 'wp_ajax_eblc_save_shorten_url', 'eblc_save_shorten_url_for_ajax' );
add_action( 'wp_ajax_eblc_save_redirection_url', 'eblc_save_redirection_url_for_ajax' ); 
add_action( 'wp_ajax_eblc_update_shorten_link', 'eblc_update_shorten_link_for_ajax' );
add_action( 'wp_ajax_eblc_delete_shorten_link', 'eblc_delete_shorten_link_for_ajax' );
add_action( 'wp_ajax_eblc_update_redirection_link', 'eblc_update_redirection_link_for_ajax' );
add_action( 'wp_ajax_eblc_delete_redirection_link', 'eblc_delete_redirection_link_for_ajax' );
add_action( 'wp_ajax_eblc_instant_check', 'eblc_instant_for_ajax' );

add_action( 'wp_ajax_eblc_check_and_save_clicks', 'eblc_check_and_save_clicks_for_ajax' );
add_action( 'wp_ajax_nopriv_eblc_check_and_save_clicks', 'eblc_check_and_save_clicks_for_ajax' );

add_action( 'wp_ajax_eblc_create_auto_link', 'eblc_create_auto_link_for_ajax' );
add_action( 'wp_ajax_eblc_update_auto_link', 'eblc_update_auto_link_for_ajax' );
add_action( 'wp_ajax_eblc_delete_auto_link', 'eblc_delete_auto_link_for_ajax' );


/**
 * Save Shorten URL for ajax function
 */
function eblc_save_shorten_url_for_ajax() {

	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not save shorten url.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not save shorten url.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not save shorten url.")
			));
		wp_die();
	}

	if ( !isset( $_POST['target_url'] ) || !isset( $_POST['shorten_url'] ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("Target URL or shorten URL is not setted. Refresh and try again.")
			));
		wp_die();
	}

	if ( !filter_var( $_POST['target_url'], FILTER_VALIDATE_URL ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("Target URL is invalidate. Try validate url.")
			));
		wp_die();
	}

	global $wpdb;

	$target_url  = esc_url_raw( sanitize_text_field( $_POST['target_url'] ) );
	$shorten_url = esc_url_raw( home_url() . '/' . sanitize_text_field( $_POST['shorten_url'] ) );

	while ( substr( $shorten_url, -1, 1 ) == '/' ) {
		$shorten_url = substr( $shorten_url, 0, -1 );
		if( substr( $shorten_url, -1, 1 ) != '/' ) {
			break;
		}
	}

	$shorten_url_table_name = $wpdb->prefix . 'eblc_shorten_links';

	$existance_checking = $wpdb->get_row( "SELECT * FROM {$shorten_url_table_name} WHERE target_url = '{$target_url}' ;" );

	if ( $existance_checking == NULL || $existance_checking == array() ) {
		$shorten_url_data = array(
			'target_url'       => $target_url,
			'shorten_url'      => $shorten_url,
			'redirection_type' => 302,
			'created_date'     => date("Y-m-d H:i:s"),
		);
		$wpdb->insert( $shorten_url_table_name, $shorten_url_data );
		$insert_id = $wpdb->insert_id;		
	} else {
		$shorten_link_id = $existance_checking->shorten_link_id;
		$shorten_url_data = array(
			'target_url'       => $target_url,
			'shorten_url'      => $shorten_url,
			'redirection_type' => 302,
			'created_date'     => date("Y-m-d H:i:s"),
		);
		$result = $wpdb->update( $shorten_url_table_name, $shorten_url_data, array( 'shorten_link_id' => $shorten_link_id ) );
		if( $result ) {
			$insert_id = $shorten_link_id;
		} else {
			$insert_id = false;
		}
	}	

	if ( $insert_id ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Update Shorten URL for ajax function
 */
function eblc_update_shorten_link_for_ajax() {
	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not update shorten url.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not update shorten url.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not update shorten url.")
			));
		wp_die();
	}

	if ( !isset( $_POST['target_url'] ) || !isset( $_POST['shorten_url'] ) || !isset( $_POST['shorten_link_id'] ) || !is_numeric( $_POST['shorten_link_id'] )) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not update shorten url.")
			));
		wp_die();
	}

	if ( !filter_var( $_POST['target_url'], FILTER_VALIDATE_URL ) || !filter_var( $_POST['shorten_url'], FILTER_VALIDATE_URL ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("URL is not valid. Try valid url.")
			));
		wp_die();
	}

	global $wpdb;

	$shorten_link_id = $_POST['shorten_link_id'];
	$target_url      = esc_url_raw( sanitize_text_field( $_POST['target_url'] ) );
	$shorten_url     = esc_url_raw( sanitize_text_field( $_POST['shorten_url'] ) );

	while ( substr( $shorten_url, -1, 1 ) == '/' ) {
		$shorten_url = substr( $shorten_url, 0, -1 );
		if( substr( $shorten_url, -1, 1 ) != '/' ) {
			break;
		}
	}

	$shorten_url_table_name = $wpdb->prefix . 'eblc_shorten_links';

	$existance_checking = $wpdb->get_row( "SELECT * FROM {$shorten_url_table_name} WHERE shorten_link_id = {$shorten_link_id} ;" );

	if ( $existance_checking != NULL && $existance_checking != array() ) {

		$shorten_url_data = array(
			'target_url'       => $target_url,
			'shorten_url'      => $shorten_url,
			'clicks_count'     => 0,
			'redirection_type' => 302,
			'created_date'     => date("Y-m-d H:i:s"),
		);
		$result = $wpdb->update( $shorten_url_table_name, $shorten_url_data, array( 'shorten_link_id' => $shorten_link_id ) );
		if( $result ) {
			$insert_id = $shorten_link_id;
		} else {
			$insert_id = false;
		}
	}	

	if ( $insert_id ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Delete a shorten link for ajax function
 */
function eblc_delete_shorten_link_for_ajax() {

	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not delete that link.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not delete that link.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not delete that link.")
			));
		wp_die();
	}

	if ( !isset( $_POST['shorten_link_id'] ) || !is_numeric( $_POST['shorten_link_id'] ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("Shorten link id is not valid. Refresh and try again.")
			));
		wp_die();
	}

	global $wpdb;

	$shorten_link_id = $_POST['shorten_link_id'];

	$shorten_url_table_name = $wpdb->prefix . 'eblc_shorten_links';

	$result = $wpdb->query( "DELETE  FROM {$shorten_url_table_name} WHERE shorten_link_id = {$shorten_link_id} ;" );

	if ( $result ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Save Redirection URL for ajax function
 */
function eblc_save_redirection_url_for_ajax() {

	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not save redirection url.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not save redirection url.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not save redirection url.")
			));
		wp_die();
	}

	if ( !isset( $_POST['source_url'] ) || !isset( $_POST['target_url'] ) || !isset( $_POST['redirection_type'] ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("Source URL or target URL or redirection type is not setted. Refresh and try again.")
			));
		wp_die();
	}

	if ( !filter_var( $_POST['target_url'], FILTER_VALIDATE_URL ) || !filter_var( $_POST['source_url'], FILTER_VALIDATE_URL ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("URL is not valid. Try valid url.")
			));
		wp_die();
	}

	global $wpdb;

	$source_url  = esc_url_raw( sanitize_text_field( $_POST['source_url'] ) );
	$target_url = esc_url_raw( sanitize_text_field( $_POST['target_url'] ) );
	$redirection_type = sanitize_text_field( $_POST['redirection_type'] );

	while ( substr( $source_url, -1, 1 ) == '/' ) {
		$source_url = substr( $source_url, 0, -1 );
		if( substr( $source_url, -1, 1 ) != '/' ) {
			break;
		}
	}

	$redirection_url_table_name = $wpdb->prefix . 'eblc_redirection_links';

	$existance_checking = $wpdb->get_row( "SELECT * FROM {$redirection_url_table_name} WHERE source_url = '{$source_url}' ;" );

	if ( $existance_checking == NULL || $existance_checking == array() ) {
		$redirection_url_data = array(
			'source_url'       => $source_url,
			'target_url'       => $target_url,
			'redirection_type' => $redirection_type,
			'created_date'     => date("Y-m-d H:i:s"),
		);
		$wpdb->insert( $redirection_url_table_name, $redirection_url_data );
		$insert_id = $wpdb->insert_id;		
	} else {
		$redirection_link_id = $existance_checking->redirection_link_id;
		$redirection_url_data = array(
			'source_url'       => $source_url,
			'target_url'       => $target_url,
			'redirection_type' => $redirection_type,
			'created_date'     => date("Y-m-d H:i:s"),
		);
		$result = $wpdb->update( $redirection_url_table_name, $redirection_url_data, array( 'redirection_link_id' => $redirection_link_id ) );
		if( $result ) {
			$insert_id = $redirection_link_id;
		} else {
			$insert_id = false;
		}
	}	

	if ( $insert_id ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Update Redirection URL for ajax function
 */
function eblc_update_redirection_link_for_ajax() {
	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not update redirection url.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not update redirection url.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not update redirection url.")
			));
		wp_die();
	}

	if ( !isset( $_POST['source_url'] ) || !isset( $_POST['target_url'] ) || !isset( $_POST['redirection_type'] ) || !isset( $_POST['redirection_link_id'] ) || !is_numeric( $_POST['redirection_link_id'] )) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not update redirection url.")
			));
		wp_die();
	}

	if ( !filter_var( $_POST['source_url'], FILTER_VALIDATE_URL ) || !filter_var( $_POST['target_url'], FILTER_VALIDATE_URL ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("URL is not valid. Try valid url.")
			));
		wp_die();
	}

	global $wpdb;

	$redirection_link_id = $_POST['redirection_link_id'];
	$source_url          = esc_url_raw( sanitize_text_field( $_POST['source_url'] ) );
	$target_url          = esc_url_raw( sanitize_text_field( $_POST['target_url'] ) );
	$redirection_type    = $_POST['redirection_type'];

	while ( substr( $source_url, -1, 1 ) == '/' ) {
		$source_url = substr( $source_url, 0, -1 );
		if( substr( $source_url, -1, 1 ) != '/' ) {
			break;
		}
	}

	$redirection_url_table_name = $wpdb->prefix . 'eblc_redirection_links';

	$existance_checking = $wpdb->get_row( "SELECT * FROM {$redirection_url_table_name} WHERE redirection_link_id = {$redirection_link_id} ;" );

	if ( $existance_checking != NULL && $existance_checking != array() ) {

		$redirection_url_data = array(
			'source_url'       => $source_url,
			'target_url'       => $target_url,
			'hits_count'       => 0,
			'redirection_type' => $redirection_type,
			'created_date'     => date("Y-m-d H:i:s"),
		);
		$result = $wpdb->update( $redirection_url_table_name, $redirection_url_data, array( 'redirection_link_id' => $redirection_link_id ) );
		if( $result ) {
			$insert_id = $redirection_link_id;
		} else {
			$insert_id = false;
		}
	}	

	if ( $insert_id ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Delete a Redirection link for ajax function
 */
function eblc_delete_redirection_link_for_ajax() {

	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not delete that link.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not delete that link.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not delete that link.")
			));
		wp_die();
	}

	if ( !isset( $_POST['redirection_link_id'] ) || !is_numeric( $_POST['redirection_link_id'] ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("Redirection link id is not valid. Refresh and try again.")
			));
		wp_die();
	}

	global $wpdb;

	$redirection_link_id = $_POST['redirection_link_id'];

	$redirection_url_table_name = $wpdb->prefix . 'eblc_redirection_links';

	$result = $wpdb->query( "DELETE  FROM {$redirection_url_table_name} WHERE redirection_link_id = {$redirection_link_id} ;" );

	if ( $result ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Delete a shorten link
 */
function eblc_delete_a_shorten_link( $id ) {
	global $wpdb;

	$shorten_url_table_name = $wpdb->prefix . 'eblc_shorten_links';
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$shorten_url_table_name} WHERE shorten_link_id = {$id} ;" );

	if ( $count >= 1 ) {
		$wpdb->query( "DELETE  FROM {$shorten_url_table_name} WHERE shorten_link_id = {$id} ;" );
	}
}

/**
 * Delete a redirection link
 */
function eblc_delete_a_redirection_link( $id ) {
	global $wpdb;

	$redirection_url_table_name = $wpdb->prefix . 'eblc_redirection_links';
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$redirection_url_table_name} WHERE redirection_link_id = {$id} ;" );

	if ( $count >= 1 ) {
		$wpdb->query( "DELETE  FROM {$redirection_url_table_name} WHERE redirection_link_id = {$id} ;" );
	}
} 

/**
 * Check url and save clicks
 */
function eblc_check_and_save_clicks_for_ajax() {

	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce-front' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not save clicks.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not save clicks.")
			));
		wp_die();
	}

	if ( !isset( $_POST['clicked_url'] ) || !isset( $_POST['anchor_text'] ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not save clicks.")
			));
		wp_die();
	}

	if ( !filter_var( $_POST['clicked_url'], FILTER_VALIDATE_URL ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not save clicks.")
			));
		wp_die();
	}


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

	$clicked_url  = esc_url_raw( sanitize_text_field( $_POST['clicked_url'] ) );
	$anchor_text  = sanitize_text_field( $_POST['anchor_text'] );


	$link_clicks_table_name = $wpdb->prefix . 'eblc_link_clicks';
	$existance_checking_link_clicks = $wpdb->get_row( "SELECT * FROM {$link_clicks_table_name} WHERE target_url = '{$clicked_url}' AND anchor_text = '{$anchor_text}' AND ip = '{$ip_address}' ;" );
	$link_click_unique = false;
	if ( $existance_checking_link_clicks == NULL || $existance_checking_link_clicks == array() ) {
		$link_click_unique = true;
		$url_data = array(
			'ip'          => $ip_address,
			'anchor_text' => $anchor_text,
			'target_url'  => $clicked_url,
			'date'        => date("Y-m-d H:i:s"),
		);

		$wpdb->insert( $link_clicks_table_name, $url_data );
	}

	$server_host = $_SERVER['HTTP_HOST'];
	//if ( strpos( $clicked_url, $server_host ) === false ) {	

		$shorten_url_table_name = $wpdb->prefix . 'eblc_shorten_links';

		$existance_checking = $wpdb->get_row( "SELECT * FROM {$shorten_url_table_name} WHERE target_url = '{$clicked_url}' OR shorten_url = '{$clicked_url}' ;" );

		if ( $existance_checking != NULL && $existance_checking != array() ) {
			$shorten_link_id = $existance_checking->shorten_link_id;
			$target_url = $existance_checking->target_url;
			if($link_click_unique) {
				$unique_clicks = $existance_checking->unique_clicks_count + 1;
			} else {
				$unique_clicks = $existance_checking->unique_clicks_count;
			}
			$shorten_url_data = array(
				'clicks_count' => $existance_checking->clicks_count + 1,
				'unique_clicks_count' => $unique_clicks
			);
			$result = $wpdb->update( $shorten_url_table_name, $shorten_url_data, array( 'shorten_link_id' => $shorten_link_id ) ); 
		} 

		$redirection_url_table_name = $wpdb->prefix . 'eblc_redirection_links';

		$existance_checking_redirection = $wpdb->get_row( "SELECT * FROM {$redirection_url_table_name} WHERE source_url = '{$clicked_url}' ;" );
		if ( $existance_checking_redirection != NULL && $existance_checking_redirection != array() ) {
			$redirection_link_id = $existance_checking_redirection->redirection_link_id;
			if ( strpos( $clicked_url, $server_host ) === false ) {
				$target_url = $existance_checking_redirection->target_url;
			}
			if($link_click_unique) {
				$unique_clicks = $existance_checking_redirection->unique_hits_count + 1;
			} else {
				$unique_clicks = $existance_checking_redirection->unique_hits_count;
			}
			$redirection_url_data = array(
				'hits_count' => $existance_checking_redirection->hits_count + 1,
				'unique_hits_count' => $unique_clicks,
			);
			
			$target_url = $existance_checking_redirection->target_url;
			$result = $wpdb->update( $redirection_url_table_name, $redirection_url_data, array( 'redirection_link_id' => $redirection_link_id ) );
		}
	//}

	$auto_links_table_name = $wpdb->prefix . 'eblc_auto_links';

	$existance_checking_autolinking = $wpdb->get_row( "SELECT * FROM {$auto_links_table_name} WHERE anchor_text = '{$anchor_text}' ;" );

	if ( $existance_checking_autolinking != NULL && $existance_checking_autolinking != array() ) {
		$auto_link_id = $existance_checking_autolinking->auto_link_id;
		$target_url = $existance_checking_autolinking->target_url;
		if($link_click_unique) {
			$unique_clicks = $existance_checking_autolinking->unique_clicks_count + 1;
		} else {
			$unique_clicks = $existance_checking_autolinking->unique_clicks_count;
		}
		$auto_link_data = array(
			'clicks_count' => $existance_checking_autolinking->clicks_count + 1,
			'unique_clicks_count' => $unique_clicks,
		);
		$result = $wpdb->update( $auto_links_table_name, $auto_link_data, array( 'auto_link_id' => $auto_link_id ) );
		
	}

	$link_url_table_name = $wpdb->prefix . 'eblc_links';
	$collection_table_name = $wpdb->prefix . 'eblc_collections';

	$existance_checking_links = $wpdb->get_row( "SELECT * FROM {$link_url_table_name} as link, {$collection_table_name} as collection WHERE link.link_id = collection.link_id AND ((link.url = '{$clicked_url}' AND collection.link_text = '{$anchor_text}') OR (link.final_url = '{$clicked_url}' AND collection.link_text = '{$anchor_text}')) ;" );

	if ( $existance_checking_links != NULL && $existance_checking_links != array() ) {
		
		$link_id = $existance_checking_links->link_id;
		if($link_click_unique) {
			$unique_clicks = $existance_checking_links->unique_clicks_count + 1;
		} else {
			$unique_clicks = $existance_checking_links->unique_clicks_count;
		}
		$link_url_data = array(
			'clicks_count' => $existance_checking_links->clicks_count + 1,
			'unique_clicks_count' => $unique_clicks,
		);
		$wpdb->update( $link_url_table_name, $link_url_data, array( 'link_id' => $link_id ) );
	}


	if ( get_option('eblc_link_options') != '' ) {
    	$eblc_sec_options = get_option('eblc_link_options');

    	$internal_link_target_setting = $eblc_sec_options['internal_link_target_setting'];
    	$external_link_target_setting = $eblc_sec_options['external_link_target_setting'];

    	if ( strpos( $target_url, $server_host ) !== false ) {
    		$target_option = $internal_link_target_setting;
    	} else {
    		$target_option = $external_link_target_setting;
    	}
    } else {
    	if ( strpos( $target_url, $server_host ) !== false ) {
    		$target_option = false;
    	} else {
    		$target_option = true;
    	}
    }

	if ( $result ) {
		$result_array = array(
	    	'success'       => 1,
	    	'target_url'    => $target_url,
	    	'target_option' => $target_option
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}


/**
 * Checking url and redirect shorten url or redirection url
 */

add_action( 'init', 'eblc_checking_shorten_or_redirection_url', 5 );

function eblc_checking_shorten_or_redirection_url() {
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
	    $url = "https"; 
	else
	    $url = "http"; 
	  
	// Here append the common URL characters. 
	$url .= "://"; 
	  
	// Append the host(domain name, ip) to the URL. 
	$url .= $_SERVER['HTTP_HOST']; 
	  
	// Append the requested resource location to the URL 
	$url .= $_SERVER['REQUEST_URI'];

	while ( substr( $url, -1, 1 ) == '/' ) {
		$url = substr( $url, 0, -1 );
		if( substr( $url, -1, 1 ) != '/' ) {
			break;
		}
	}

	// Load setting options.
    if ( get_option('eblc_sec_options') != '' ) {
    	$eblc_sec_options = get_option('eblc_sec_options');
    	if ( $eblc_sec_options['change_login_url'] != '' && filter_var( $eblc_sec_options['change_login_url'], FILTER_VALIDATE_URL ) && $eblc_sec_options['change_login_url'] == $url ) {

    		// header("Location: " . home_url() . "/wp-admin", TRUE, 301);
    		// exit;

    		if ( is_user_logged_in() ) {
    			header("Location: " . get_admin_url(), TRUE, 302);
				exit;
    		} else {
    			$request  = wp_remote_get( get_admin_url() );
				$response = wp_remote_retrieve_body( $request );
				echo $response;
				exit;
    		}    		
    	}
    } 

	
	global $wpdb; 

	$shorten_url_table_name = $wpdb->prefix . 'eblc_shorten_links';

	$shorten_url_existance_checking = $wpdb->get_row( "SELECT * FROM {$shorten_url_table_name} WHERE shorten_url = '{$url}' ;" );

	if ( $shorten_url_existance_checking != NULL && $shorten_url_existance_checking != array() ) {
		$target_url = $shorten_url_existance_checking->target_url;

		// $shorten_link_id = $shorten_url_existance_checking->shorten_link_id;
		// $shorten_url_data = array(
		// 	'clicks_count' => $shorten_url_existance_checking->clicks_count + 1,
		// );
		// $wpdb->update( $shorten_url_table_name, $shorten_url_data, array( 'shorten_link_id' => $shorten_link_id ) );

		// $url_page = file_get_contents($target_url);
		// echo $url_page;
		// exit;
		header("Location: " . $target_url, TRUE, 302);
		exit;

	}

	$auto_links_table_name = $wpdb->prefix . 'eblc_auto_links';

	$auto_links_existance_checking = $wpdb->get_row( "SELECT * FROM {$auto_links_table_name} WHERE target_url = '{$url}' ;" );

	if ( $auto_links_existance_checking != NULL && $auto_links_existance_checking != array() ) {
		$target_url = $auto_links_existance_checking->target_url;

		// $auto_link_id = $auto_links_existance_checking->auto_link_id;
		// $auto_link_data = array(
		// 	'clicks_count' => $auto_links_existance_checking->clicks_count + 1,
		// );
		// $wpdb->update( $auto_links_table_name, $auto_link_data, array( 'auto_link_id' => $auto_link_id ) );

		header("Location: " . $target_url, TRUE, 301);
		exit;
	} 

	$redirection_url_table_name = $wpdb->prefix . 'eblc_redirection_links';

	$redirection_url_existance_checking = $wpdb->get_row( "SELECT * FROM {$redirection_url_table_name} WHERE source_url = '{$url}' ;" );


	if ( $redirection_url_existance_checking != NULL && $redirection_url_existance_checking != array() ) {
		$target_url = $redirection_url_existance_checking->target_url;
		$redirection_type = $redirection_url_existance_checking->redirection_type;

		// $redirection_link_id = $redirection_url_existance_checking->redirection_link_id;
		// $redirection_url_data = array(
		// 	'hits_count' => $redirection_url_existance_checking->hits_count + 1,
		// );
		// $wpdb->update( $redirection_url_table_name, $redirection_url_data, array( 'redirection_link_id' => $redirection_link_id ) );

		header("Location: " . $target_url, TRUE, $redirection_type);
		exit;
	}  
} 

/**
 * instant check broken link and send mail
 */
function eblc_instant_for_ajax() {
	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not delete that link.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not delete that link.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not delete that link.")
			));
		wp_die();
	}

	// Check all links if they are valid or not.
	eblc_check_all_links();
	// Send email notification with broken link info
	eblc_send_email_notification();

	echo json_encode( array(
			'success' => 1
		));
	wp_die();
}


/**
 * Create auto link for ajax
 */
function eblc_create_auto_link_for_ajax() {
	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not save auto link.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not save auto link.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not save auto link.")
			));
		wp_die();
	}

	if ( !isset( $_POST['anchor_text'] ) || !isset( $_POST['target_url'] ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("Anchor Text or target URL is not setted. Refresh and try again.")
			));
		wp_die();
	}

	if ( !filter_var( $_POST['target_url'], FILTER_VALIDATE_URL ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("Target URL is invalidate. Try validate url.")
			));
		wp_die();
	}

	global $wpdb;

	$target_url  = esc_url_raw( sanitize_text_field( $_POST['target_url'] ) );
	$anchor_text = sanitize_text_field( $_POST['anchor_text'] );
	//str_replace(" ", "", $anchor_text);
	$anchor_text_array = explode(",", $anchor_text);

	$auto_links_table_name = $wpdb->prefix . 'eblc_auto_links';

	foreach ($anchor_text_array as $anchor) {
		$existance_checking = $wpdb->get_row( "SELECT * FROM {$auto_links_table_name} WHERE anchor_text = '{$anchor}' ;" );

		if ( $existance_checking == NULL || $existance_checking == array() ) {
			$auto_url_data = array(
				'anchor_text'  => $anchor,
				'target_url'   => $target_url,
				'created_date' => date("Y-m-d H:i:s"),
			);
			$result = $wpdb->insert( $auto_links_table_name, $auto_url_data );
			//$insert_id = $wpdb->insert_id;

			$query = new WP_Query('s=' . $anchor);
			while($query->have_posts()) { 
				$query->the_post();
				$post_content = get_the_content();
				$post_id = get_the_ID();
				$post_content = str_replace($anchor, "<a href='" . $target_url . "'>" . $anchor . "</a>", $post_content);

				$post_data = array(
					'ID'           => $post_id,
					'post_content' => $post_content,
				);
				 
				// Update the post into the database
				$result = wp_update_post( $post_data );
			}

		} else {
			$auto_link_id = $existance_checking->auto_link_id;
			$auto_url_data = array(
				'anchor_text'  => $anchor,
				'target_url'   => $target_url,
				'created_date' => date("Y-m-d H:i:s"),
			);
			$result = $wpdb->update( $auto_links_table_name, $auto_url_data, array( 'auto_link_id' => $auto_link_id ) );

			$query = new WP_Query('s=' . $anchor);
			while($query->have_posts()) { 
				$query->the_post();
				$post_content = get_the_content();
				$post_id = get_the_ID();
				

				$new_document = new DomDocument('1.0', 'UTF-8');
				libxml_use_internal_errors(true);
				$new_document->loadHTML( mb_convert_encoding( $post_content, 'HTML-ENTITIES', 'UTF-8' ) );
				$all_anchors = $new_document->getElementsByTagName('a');

				foreach ( $all_anchors as $anchor_tag ) {
					$link_url = $anchor_tag->getAttribute('href');
					$link_text = $anchor_tag->textContent;

					if ( $link_text == $anchor ) {
						$anchor_tag->setAttribute( 'href', $target_url );
						$anchor_tag->textContent = $anchor; 		
					}
				}

				$body = $new_document->getElementsByTagName('body')->item(0);
				$body_children = $body->childNodes;
				$innerHTML = "";
				foreach ( $body_children as $child ) 
			    { 
			        $innerHTML .= $body->ownerDocument->saveHTML($child);
			    }

				$update_post_data = array();
		        $update_post_data['ID'] = $post_id;
		        $update_post_data['post_content'] = $innerHTML;

		        $result = wp_update_post( $update_post_data );
			}
		}		
	}

	if ( $result ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Delete a auto link
 */
function eblc_delete_a_auto_link($id){
	global $wpdb;

	$auto_links_table_name = $wpdb->prefix . 'eblc_auto_links';
	//$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$auto_links_table_name} WHERE auto_link_id = {$id} ;" );

	$existance_checking = $wpdb->get_row( "SELECT * FROM {$auto_links_table_name} WHERE auto_link_id = {$id} ;" );

	if ( $existance_checking != NULL && $existance_checking != array() ) {

	//if ( $count >= 1 ) {
		$anchor_text = $existance_checking->anchor_text;

		$wpdb->query( "DELETE  FROM {$auto_links_table_name} WHERE auto_link_id = {$id} ;" );

		$query = new WP_Query('s=' . $anchor_text);
		while($query->have_posts()) { 
			$query->the_post();
			$post_content = get_the_content();
			$post_id = get_the_ID();

			$new_document = new DomDocument('1.0', 'UTF-8');
			libxml_use_internal_errors(true);
			$new_document->loadHTML( mb_convert_encoding( $post_content, 'HTML-ENTITIES', 'UTF-8' ) );
			$all_anchors = $new_document->getElementsByTagName('a');

			foreach ( $all_anchors as $anchor_tag ) {
				$link_url = $anchor_tag->getAttribute('href');
				$link_text = $anchor_tag->textContent;

				if ( $link_text == $anchor_text ) {

					foreach ( $anchor_tag->childNodes as $child_node ) {
						$anchor_tag->parentNode->insertBefore( $child_node->cloneNode(false), $anchor_tag );
					}

					$anchor_tag->parentNode->removeChild( $anchor_tag );
				}
			}

			$body = $new_document->getElementsByTagName('body')->item(0);
			$body_children = $body->childNodes;
			$innerHTML = "";
			foreach ( $body_children as $child ) 
		    { 
		        $innerHTML .= $body->ownerDocument->saveHTML($child);
		    }

			$update_post_data = array();
	        $update_post_data['ID'] = $post_id;
	        $update_post_data['post_content'] = $innerHTML;

	        $result = wp_update_post( $update_post_data );
	    }

	}
}


/**
 * Update auto link for ajax function
 */
function eblc_update_auto_link_for_ajax() {
	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not update auto link.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not update auto link.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not update auto link.")
			));
		wp_die();
	}

	if ( !isset( $_POST['anchor_text'] ) || !isset( $_POST['target_url'] ) || !isset( $_POST['auto_link_id'] ) || !is_numeric( $_POST['auto_link_id'] )) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not update auto link.")
			));
		wp_die();
	}

	if ( !filter_var( $_POST['target_url'], FILTER_VALIDATE_URL ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("URL is not valid. Try valid url.")
			));
		wp_die();
	}

	global $wpdb;

	$auto_link_id = $_POST['auto_link_id'];
	$target_url   = esc_url_raw( sanitize_text_field( $_POST['target_url'] ) );
	$anchor_text  = sanitize_text_field( $_POST['anchor_text'] );

	$auto_links_table_name = $wpdb->prefix . 'eblc_auto_links';

	$existance_checking = $wpdb->get_row( "SELECT * FROM {$auto_links_table_name} WHERE auto_link_id = {$auto_link_id} ;" );

	if ( $existance_checking != NULL && $existance_checking != array() ) {

		$auto_link_data = array(
			'anchor_text'  => $anchor_text,
			'target_url'   => $target_url,
			'clicks_count' => 0,
			'created_date' => date("Y-m-d H:i:s"),
		);
		$result = $wpdb->update( $auto_links_table_name, $auto_link_data, array( 'auto_link_id' => $auto_link_id ) );


		$query = new WP_Query('s=' . $anchor_text);
		while($query->have_posts()) { 
			$query->the_post();
			$post_content = get_the_content();
			$post_id = get_the_ID();
			

			$new_document = new DomDocument('1.0', 'UTF-8');
			libxml_use_internal_errors(true);
			$new_document->loadHTML( mb_convert_encoding( $post_content, 'HTML-ENTITIES', 'UTF-8' ) );
			$all_anchors = $new_document->getElementsByTagName('a');

			foreach ( $all_anchors as $anchor_tag ) {
				$link_url = $anchor_tag->getAttribute('href');
				$link_text = $anchor_tag->textContent;

				if ( $link_text == $anchor_text ) {
					$anchor_tag->setAttribute( 'href', $target_url );
					$anchor_tag->textContent = $anchor_text; 		
				}
			}

			$body = $new_document->getElementsByTagName('body')->item(0);
			$body_children = $body->childNodes;
			$innerHTML = "";
			foreach ( $body_children as $child ) 
		    { 
		        $innerHTML .= $body->ownerDocument->saveHTML($child);
		    }

			$update_post_data = array();
	        $update_post_data['ID'] = $post_id;
	        $update_post_data['post_content'] = $innerHTML;

	        $result = wp_update_post( $update_post_data );
		}


		if( $result ) {
			$insert_id = $auto_link_id;
		} else {
			$insert_id = false;
		}
	}	

	if ( $insert_id ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
}

/**
 * Delete a auto link for ajax function
 */
function eblc_delete_auto_link_for_ajax() {

	if ( isset( $_POST['eblc_nonce'] ) ) {
		$ajax_nonce = $_POST['eblc_nonce'];	
		if ( ! wp_verify_nonce( $ajax_nonce, 'eblc-ajax-nonce' ) ) {
        	echo json_encode( array(
					'success' => 0,
					'error'   => __("You can not delete that link.")
				));
        	wp_die();
        }
	} else {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not delete that link.")
			));
		wp_die();
	}

	if ( !current_user_can('edit_others_posts') ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("You can not delete that link.")
			));
		wp_die();
	}

	if ( !isset( $_POST['auto_link_id'] ) || !is_numeric( $_POST['auto_link_id'] ) ) {
		echo json_encode( array(
				'success' => 0,
				'error'   => __("Auto link id is not valid. Refresh and try again.")
			));
		wp_die();
	}

	global $wpdb;

	$auto_link_id = $_POST['auto_link_id'];

	$auto_links_table_name = $wpdb->prefix . 'eblc_auto_links';

	//$result = $wpdb->query( "DELETE  FROM {$auto_links_table_name} WHERE auto_link_id = {$auto_link_id} ;" );

	//$auto_links_table_name = $wpdb->prefix . 'eblc_auto_links';
	//$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$auto_links_table_name} WHERE auto_link_id = {$id} ;" );

	$existance_checking = $wpdb->get_row( "SELECT * FROM {$auto_links_table_name} WHERE auto_link_id = {$auto_link_id} ;" );

	if ( $existance_checking != NULL && $existance_checking != array() ) {

	//if ( $count >= 1 ) {
		$anchor_text = $existance_checking->anchor_text;

		$wpdb->query( "DELETE  FROM {$auto_links_table_name} WHERE auto_link_id = {$auto_link_id} ;" );
		

		$query = new WP_Query('s=' . $anchor_text);
		while($query->have_posts()) { 
			$query->the_post();
			$post_content = get_the_content();
			$post_id = get_the_ID();

			$new_document = new DomDocument('1.0', 'UTF-8');
			libxml_use_internal_errors(true);
			$new_document->loadHTML( mb_convert_encoding( $post_content, 'HTML-ENTITIES', 'UTF-8' ) );
			$all_anchors = $new_document->getElementsByTagName('a');

			foreach ( $all_anchors as $anchor_tag ) {
				$link_url = $anchor_tag->getAttribute('href');
				$link_text = $anchor_tag->textContent;

				if ( $link_text == $anchor_text ) {

					foreach ( $anchor_tag->childNodes as $child_node ) {
						$anchor_tag->parentNode->insertBefore( $child_node->cloneNode(false), $anchor_tag );
					}

					$anchor_tag->parentNode->removeChild( $anchor_tag );
				}
			}

			$body = $new_document->getElementsByTagName('body')->item(0);
			$body_children = $body->childNodes;
			$innerHTML = "";
			foreach ( $body_children as $child ) 
		    { 
		        $innerHTML .= $body->ownerDocument->saveHTML($child);
		    }

			$update_post_data = array();
	        $update_post_data['ID'] = $post_id;
	        $update_post_data['post_content'] = $innerHTML;

	        $result = wp_update_post( $update_post_data );
	    }

	}


	if ( $result ) {
		$result_array = array(
	    	'success' => 1
	    );
	} else {
 	  	$result_array = array(
	    	'success' => 0,
	    	'error'   => "Some errors. Refresh and try again."
	    );
 	}

    $result_array = json_encode( $result_array );
    echo $result_array;
    wp_die();
} 



