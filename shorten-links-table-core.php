<?php

/**
 * Create a new table class for shorten links that extends the WP_List_Table
 */

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class EBLC_Shorten_Links_Table extends WP_List_Table {

    /**
     * Prepare the items for the table to process
     */
    public function prepare_items()
    {
        $columns   = $this->get_columns();
        $hidden    = $this->get_hidden_columns();
        $sortable  = $this->get_sortable_columns();
        $this->process_bulk_action();

        // Pagination config
        $per_page = 10;
		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		// Get table data and count
        $data = $this->table_data( $per_page, $offset );
        $count = $this->table_count();
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
            'url'           => __( 'Target URL', 'easy-broken-link-checker' ),
            'shorten_url'   => __( 'Shorten URL / Copy URL', 'easy-broken-link-checker' ),
            'clicks'        => __( 'Clicks', 'easy-broken-link-checker' ),
            'unique_clicks_count' => __( 'Unique Clicks', 'easy-broken-link-checker' ),
            'created_date'  => __( 'Created Date', 'easy-broken-link-checker' )
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
            'created_date'  => array( 'created_date', false )
        );
    }

    /**
  	 * Returns an associative array containing the bulk action
  	 */
  	public function get_bulk_actions() {
      	$actions = array(
          	'delete'               => __( 'Delete', 'easy-broken-link-checker' ),
      	);

      	return $actions;
  	}

  	public function process_bulk_action()
    {
        if ( 'delete' === $this->current_action() ) {
            $ids = ( isset($_REQUEST['id']) && is_array($_REQUEST['id']) ) ? $_REQUEST['id'] : array();
            foreach ( $ids as $id ) {
            	// Delete link
                if ( current_user_can('edit_others_posts') && is_numeric( $id ) ) {
            	   eblc_delete_a_shorten_link( $id );
                }
            }
        } 
    }


    // For shorten url column
    public function column_url( $item ) {
        $actions = array(
            'eblc_edit_shorten_url'         => sprintf( '<a href="javascript:;">' . __( 'Edit', 'easy-broken-link-checker' ) . '</a>' ),
            'trash eblc_trash_shorten_url'  => sprintf( '<a href="javascript:;">' . __( 'Delete', 'easy-broken-link-checker' ) . '</a>' ),
        );

        return sprintf( '<span class="eblc_edit_shorten_target_url">%1$s</span> %2$s', rawurldecode( $item['url'] ), $this->row_actions( $actions ) );
    } 

    // For shorten url column
    public function column_shorten_url( $item ) {
        return sprintf( '<input style="width: 200px;" type="text" value="%1$s" /><span class="btn btn-default eblc_clipboard_btn"><i class="fa fa-clipboard" aria-hidden="true"></i></span>', rawurldecode( $item['shorten_url'] ) );
    }

    // For link clicks column
    // public function column_clicks( $item ) {
    //     return sprintf( '<a href="%1$s" ">%2$s</a>', __( 'https://jannatqualitybacklinks.com/product/link-manager-plugin/', 'easy-broken-link-checker' ), __( 'Locked Go For Pro', 'easy-broken-link-checker' ) );
    // }
    // For link clicks column
    // public function column_unique_clicks_count( $item ) {
    //     return sprintf( '<a href="%1$s" target="_blank">%2$s</a>', __( 'https://jannatqualitybacklinks.com/product/link-manager/', 'easy-broken-link-checker' ), __( 'Locked Go For Pro', 'easy-broken-link-checker' ) );
    // }

    /**
     * Get the table data
     */
    private function table_data( $per_page, $offset )
    {
        global $wpdb;
        $shorten_link_table_name  = $wpdb->prefix . "eblc_shorten_links";

        // Get all links data
        $all_links_data   = $wpdb->get_results( "SELECT * FROM $shorten_link_table_name ORDER BY target_url LIMIT {$per_page} OFFSET {$offset} ;" );

        $table_data = array();
        foreach ( $all_links_data as $link_data ) {
            $row_data = array(
                'id'               => $link_data->shorten_link_id,
                'url'              => $link_data->target_url,
                'shorten_url'      => $link_data->shorten_url,
                'clicks'           => $link_data->clicks_count,
                'unique_clicks_count'    => $link_data->unique_clicks_count,
                'redirection_type' => $link_data->redirection_type,
                'created_date'     => $link_data->created_date,
            );
            array_push( $table_data, $row_data );
        }

        return $table_data;
    }

    /**
     * Get the table count
     */
    private function table_count()
    {
        global $wpdb;
        $shorten_link_table_name  = $wpdb->prefix . "eblc_shorten_links";

        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $shorten_link_table_name ;" );
        
        return $count;
    }

	
    /**
     * Define what data to show on each column of the table
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
            case 'url':
            case 'shorten_url':
            case 'clicks':
            case 'unique_clicks_count':
            case 'created_date':
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

