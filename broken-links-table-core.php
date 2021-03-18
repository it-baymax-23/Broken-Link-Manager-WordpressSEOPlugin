<?php

/**
 * Create a new table class for detected broken links that extends the WP_List_Table
 */

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class EBLC_Broken_Links_Table extends WP_List_Table {

    /**
     * Prepare the items for the table to process
     */
    public function prepare_items()
    {
        $columns     = $this->get_columns();
        $hidden      = $this->get_hidden_columns();
        $sortable    = $this->get_sortable_columns();
        $this->process_bulk_action();

        // Search item
        $search_item = ( ! empty( $_REQUEST['s']) ? sanitize_text_field( $_REQUEST['s'] ) : '' );
        $link_status = ( isset( $_REQUEST['link_status'] ) ? sanitize_text_field( $_REQUEST['link_status'] ) : '' );
        // Pagination config
        $per_page = 10;
		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		// Get table data and count
        $data = $this->table_data( $search_item, $link_status, $per_page, $offset );
        $count = $this->table_count( $search_item, $link_status );
        // Set sort
        usort( $data, array( &$this, 'sort_data' ) );
        // Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page )
		) );
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->items = $data;
                
    }

    /**
     * Override the parent columns method.
     */
    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'url'           => __( 'URL', 'easy-broken-link-checker' ),
            'status'        => __( 'Status', 'easy-broken-link-checker' ),
            'link_text'     => __( 'Anchor Text', 'easy-broken-link-checker' ),
            'link_type'     => __( 'Link Type', 'easy-broken-link-checker' ),
            'clicks'        => __( 'Clicks', 'easy-broken-link-checker' ),
            'unique_clicks_count' => __( 'Unique Clicks', 'easy-broken-link-checker' ),
            'source'        => __( 'Source', 'easy-broken-link-checker' )
        );
        return $columns;
    }

    /**
  	 * Render the bulk edit checkbox
  	 */
  	public function column_cb( $item ) {
      	return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />', $item['id']
      	);
  	}

    /**
     * Define which columns are hidden
     */
    public function get_hidden_columns() {
        return array();
    }

    /**
     * Define the sortable columns
     */
    public function get_sortable_columns() {
        return array(
          	'url'           => array( 'url', false ),
          	'link_text'     => array( 'link_text', false ),
        );
    }

    /**
  	 * Returns an associative array containing the bulk action
  	 */
  	public function get_bulk_actions() {
      	$actions = array(
          	'recheck'               => __( 'Recheck', 'easy-broken-link-checker' ),
          	'unlink'                => __( 'Unlink', 'easy-broken-link-checker' ),
      	);

      	return $actions;
  	}

  	public function process_bulk_action()
    {
        if ( 'recheck' === $this->current_action() ) {
            $ids = ( isset($_REQUEST['id']) && is_array($_REQUEST['id']) ) ? $_REQUEST['id'] : array();
            foreach ( $ids as $id ) {
            	// Check link
                if ( current_user_can('edit_others_posts') && is_numeric( $id ) ) {
            	   eblc_check_a_link( $id );
                }
            }
        } 
        else if ( 'unlink' === $this->current_action() ) {
        	$ids = ( isset($_REQUEST['id']) && is_array($_REQUEST['id']) ) ? $_REQUEST['id'] : array();
            // Delete links from source
            if ( current_user_can('edit_others_posts') ) {
                eblc_unlink_links( $ids );
            }
        }
    }

  	public function get_views() { 
    	$views = array();

    	$all_count = $this->table_count( '', '' );
    	$broken_count = $this->table_count( '', 'broken' );

    	$current = ( !empty( $_REQUEST['link_status']) ? sanitize_text_field( $_REQUEST['link_status'] ) : 'all' );
     
    	// All link
    	$class = ( $current == 'all' ? ' class="current"' :'' );
    	$all_url = remove_query_arg( 'link_status' );
    	$views['all'] = "<a href='{$all_url}' {$class} >" . __( "All", 'easy-broken-link-checker' ) . "</a> <span> (" . $all_count . ") </span>";
     
    	// Broken link
    	$broken_url = add_query_arg( 'link_status','broken' );
    	$class = ( $current == 'broken' ? ' class="current"' :'' );
    	$views['broken'] = "<a href='{$broken_url}' {$class} >" . __( "Broken", 'easy-broken-link-checker' ) . "</a> <span> (" . $broken_count . ") </span>";
     
    	return $views;
  	}

  	/**
      * Add column action
      */
    // For url column
  	public function column_url( $item ) {
        $actions = array();
        $actions = array(
            'eblc_edit_url'         => sprintf( '<a href="javascript:;" parser_type="' . $item['parser_type'] . '"  link_target="' . $item['link_target'] . '" >' . __( 'Edit', 'easy-broken-link-checker' ) . '</a>' ),
            'trash eblc_trash_url'  => sprintf( '<a href="javascript:;">' . __( 'Unlink', 'easy-broken-link-checker' ) . '</a>' ),
            'eblc_recheck_url'      => sprintf( '<a href="javascript:;">' . __( 'Recheck', 'easy-broken-link-checker' ) . '</a>' )
        );    	

        if ( $item['link_type'] != 'image' ) {
            $rel = 'rel="' . $item['link_type'] . '"';
            $target = 'target="' . $item['link_target'] . '"';
        } else {
            $rel = '';
            $target = '';
        }
    	return sprintf( '<a href="%1$s" %2$s %3$s >%4$s</a> %5$s', rawurldecode( $item['url'] ), $rel, $target, rawurldecode( $item['url'] ), $this->row_actions( $actions ) );
  	}
    // For status column
  	public function column_status( $item ) {
      	$actions = array(
            'eblc_link_details'   => sprintf( '<a href="javascript:;">' . __( 'Details', 'easy-broken-link-checker' ) . '</a>' ),
        );
        if ( $item['http_code'] < 400 && $item['http_code'] >= 200 ) {
        	$label_color = "#008000";
        } else if ( $item['http_code'] >= 400 ) {
        	$label_color = "#ff8c00";
        } else {
        	$label_color = "gray";
        }
      	return sprintf( '<span style="color: %1$s; font-weight: bold;">%2$s</span> %3$s', $label_color, $item['status_code'], $this->row_actions( $actions ) );
  	}
    // For source column
    public function column_source( $item ) {
        $actions = array(
            'eblc_edit_source'  => sprintf( '<a href="%s" title="Edit this item">' . __( 'Edit', 'easy-broken-link-checker' ) . '</a>', $item['edit_link'] ),
            'trash'        => sprintf( '<a href="%s" title="Move this item to the trash">' . __( 'Trash', 'easy-broken-link-checker' ) . '</a>', $item['trash_link'] ),
            'eblc_view_source'  => sprintf( "<a href='%s' title='%s'>" . __( "View", 'easy-broken-link-checker' ) . "</a>", $item['guid'], ( 'View "' . substr( $item['source'], 0, 40 ) . ' ..."'))
        );
        return sprintf( '<a href="%1$s" title="Edit this item">%2$s</a> %3$s', $item['edit_link'], $item['source'], $this->row_actions( $actions ) );
    }
    // For link type column
    public function column_link_type( $item ) {
        if ( $item['parser_type'] == 'img_src' ) {
            return '( image )';
        } else {
            return $item['link_type'];
        }
    }
    // For link text column
    public function column_link_text( $item ) {
        if ( $item['link_text'] == '' ) {
            return '( Anchor text none )';
        } else {
            return $item['link_text'];
        }
    }

    // For link clicks column
    // public function column_clicks( $item ) {
    //     return sprintf( '<a href="%1$s" >%2$s</a>', __( 'https://jannatqualitybacklinks.com/product/link-manager/', 'easy-broken-link-checker' ), __( 'Locked Go For Pro', 'easy-broken-link-checker' ) );
    // }
    // 
    // For link clicks column
    // public function column_unique_clicks_count( $item ) {
    //     return sprintf( '<a href="%1$s" target="_blank">%2$s</a>', __( 'https://jannatqualitybacklinks.com/product/link-manager/', 'easy-broken-link-checker' ), __( 'Locked Go For Pro', 'easy-broken-link-checker' ) );
    // }
    
  	/**
     * Get the table data
     */
    private function table_data( $search, $link_status, $per_page, $offset )
    {
    	global $wpdb;
    	$link_table_name       = $wpdb->prefix . "eblc_links";
    	$collection_table_name = $wpdb->prefix . "eblc_collections";
    	// Search config
    	$search_link_text = " collection.link_text LIKE '%" . esc_sql( $wpdb->esc_like( $search ) ) . "%'";
    	$search_link_url  = " link.url LIKE '%" . esc_sql( $wpdb->esc_like( $search ) ) . "%'";
    	// Link status config
    	if ( $link_status == 'broken' ) {
    		$http_code = "AND link.http_code >= 400 ";
    	} else {
    		$http_code = "";
    	}
        // Get all links data
    	$all_links_data   = $wpdb->get_results( "SELECT link.*, collection.* FROM $link_table_name as link, $collection_table_name as collection WHERE link.link_id = collection.link_id AND ( {$search_link_text} OR {$search_link_url} ) {$http_code} ORDER BY link.url LIMIT {$per_page} OFFSET {$offset} ;" );

    	$table_data = array();
    	foreach ( $all_links_data as $link_data ) {
    		$source = $this->get_source( $link_data->element_id, $link_data->element_type, $link_data->element_field );
    		$row_data = array(
    			'id'             => $link_data->link_id,
	            'url'            => $link_data->url,
	            'status_code'    => $link_data->status_code,
	            'http_code'      => $link_data->http_code,
	            'link_text'      => $link_data->link_text,
	            'link_type'      => $link_data->link_type,
                'link_target'    => $link_data->link_target,
	            'redirect_count' => $link_data->redirect_count,
	            'source'         => $source['source'],
	            'guid'           => $source['guid'],
	            'edit_link'      => $source['edit_link'],
	            'trash_link'     => $source['trash_link'],
	            'element_id'     => $link_data->element_id,
	            'element_type'   => $link_data->element_type,
	            'element_field'  => $link_data->element_field,
                'parser_type'    => $link_data->parser_type,
                'last_check'     => $link_data->last_check,
                'first_failure'  => $link_data->first_failure,
                'final_url'      => $link_data->final_url,
                'log'            => $link_data->log,
                'clicks'         => $link_data->clicks_count,
                'unique_clicks_count'  => $link_data->unique_clicks_count
    		);
    		array_push( $table_data, $row_data );
    	}

        return $table_data;
    }

    /**
     * Get the table count
     */
    private function table_count( $search, $link_status )
    {
    	global $wpdb;
    	$link_table_name       = $wpdb->prefix . "eblc_links";
    	$collection_table_name = $wpdb->prefix . "eblc_collections";
    	// Search config
    	$search_link_text = " collection.link_text LIKE '%" . esc_sql( $wpdb->esc_like( $search ) ) . "%'";
    	$search_link_url  = " link.url LIKE '%" . esc_sql( $wpdb->esc_like( $search ) ) . "%'";
    	// Link status config
    	if ( $link_status == 'broken' ) {
    		$http_code = "AND link.http_code >= 400 ";
    	} else {
    		$http_code = "";
    	}

    	$count = $wpdb->get_var( "SELECT COUNT(link.link_id) FROM $link_table_name as link, $collection_table_name as collection WHERE link.link_id = collection.link_id AND ( {$search_link_text} OR {$search_link_url} ) {$http_code} ;" );
        
        return $count;
    }

    /**
     * Get source with element id, element type, element type
     */
    public function get_source( $element_id, $element_type, $element_field ) {

    	// Get all post types
    	$post_types = get_post_types( array(), 'objects' );
		$exceptions = array( 'revision', 'nav_menu_item', 'attachment' );

		// Enable post types
		$enable_post_types = array();
		foreach( $post_types as $data ) {
			$post_type = $data->name;
			
			if ( in_array( $post_type, $exceptions ) ) {
				continue;
			}
			array_push( $enable_post_types, $post_type );
		}

		// Checking element is post or not and get source
		if( in_array( $element_type, $enable_post_types ) ) {
			$post = get_post( $element_id );
			$return_arry = array(
				'source'     => $post->post_title,
                'guid'       => get_permalink( $element_id ),
				'edit_link'  => get_edit_post_link( $post->ID ),
				'trash_link' => get_delete_post_link( $post->ID )
			);
			return $return_arry;
		}
		// Checking element is comment or not and get source
		if( $element_type == 'comment' ) {
			$comment = get_comment( $element_id );
			$comment_author  = $comment->comment_author;
			$comment_content = $comment->comment_content;
			$source = $comment_author . ' -- ' . strip_tags( $comment_content );
			$del_nonce = esc_html( '_wpnonce=' . wp_create_nonce( "delete-comment_$comment->comment_ID" ) );
			$trash_url = esc_url( admin_url( "comment.php?action=trashcomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$del_nonce" ) );
			$return_arry = array(
				'source'     => $source,
				'guid'       => get_comments_link( $comment->comment_post_ID ),
				'edit_link'  => get_edit_comment_link( $comment->comment_ID ),
				'trash_link' => $trash_url
			);
			return $return_arry;
		}
    }
	
    /**
     * Define what data to show on each column of the table
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
            case 'url':
            case 'status':
            case 'link_text':
            case 'link_type':
            case 'clicks':
            case 'unique_clicks_count':
            case 'source':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }
    }
  
    /**
     * Allows to sort the data by the variables set in the $_GET
     */
    private function sort_data( $first, $second ) {
        // Set defaults
        $orderby = 'url';
        $order = 'asc';
        // If orderby is set, use this as the sort column
        if( !empty( $_GET['orderby'] ) )
        {
            $orderby = sanitize_text_field( $_GET['orderby'] );
        }
        // If order is set use this as the order
        if( !empty( $_GET['order'] ) )
        {
            $order = sanitize_text_field( $_GET['order'] );
        }
        $result = strcmp( $first[ $orderby ], $second[ $orderby ] );
        if( $order === 'asc' )
        {
            return $result;
        }
        return -$result;
    }
} 

