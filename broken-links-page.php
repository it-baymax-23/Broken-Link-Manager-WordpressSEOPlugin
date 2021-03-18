<?php

/**
 * Broken links page config
 */

require 'broken-links-table-core.php';

// Broken links status page
function eblc_broken_links() {
 	$linksTable = new EBLC_Broken_Links_Table();

  	echo '<div class="wrap"><h2>' . __( 'Detected Links', 'easy-broken-link-checker' ) . '</h2>';

  	$linksTable->prepare_items();
  	$linksTable->views();

    echo    '<form method="post">';

    $linksTable->search_box( __( 'Search', 'easy-broken-link-checker' ), 'search_id' );
    $linksTable->display();

    echo    '</form>';
  	echo '</div>';    
}






