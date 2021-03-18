<?php

/**
 * Shorten links page config
 */

require 'shorten-links-table-core.php';

// Shorten links status page
function eblc_shorten_links() {
 	$linksTable = new EBLC_Shorten_Links_Table();

  	echo '<div class="wrap">';

  	$linksTable->prepare_items();

    echo    '<form method="post">';

    $linksTable->display();

    echo    '</form>';
  	echo '</div>';    
}






