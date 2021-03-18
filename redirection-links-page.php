<?php

/**
 * Broken links page config
 */

require 'redirection-links-table-core.php';

// Broken links status page
function eblc_redirection_links() {
 	$linksTable = new EBLC_Redirection_Links_Table();

  	echo '<div class="wrap">';

  	$linksTable->prepare_items();

    echo    '<form method="post">';

    $linksTable->display();

    echo    '</form>';
  	echo '</div>';    
}






