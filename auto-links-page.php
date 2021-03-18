<?php

/**
 * Auto links page config
 */

require 'auto-links-table-core.php';

// Auto links status page
function eblc_auto_links() {
 	$linksTable = new EBLC_Auto_Links_Table();

  	echo '<div class="wrap">';

  	$linksTable->prepare_items();

    echo    '<form method="post">';

    $linksTable->display();

    echo    '</form>';
  	echo '</div>';    
}






