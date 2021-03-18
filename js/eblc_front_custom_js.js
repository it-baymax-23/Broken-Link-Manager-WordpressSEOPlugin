
/**
 * Action handle When anchor click
 */ 

jQuery(document).ready( function($) {
    /**
     * Save anchor click counts When anchor click
     */ 
    $(document).on( 'click', 'a', function() {

        var ajax_url = eblc_ajax_var_front.eblc_ajax_url_front;
        var anchor_url = $(this).attr('href');
        var anchor_target = $(this).attr('target');
        var anchor_text = $(this).text();

        if ($(this)[0].hostname == window.location.hostname){
            // internal link
            if ( anchor_url != '' && anchor_url != 'javascript:;' ) {
                $.ajax({
                    url:ajax_url + '?action=eblc_check_and_save_clicks',
                    type:'POST',
                    data:{
                        'clicked_url' : anchor_url,
                        'anchor_text' : anchor_text,
                        'eblc_nonce'  : eblc_ajax_var_front.eblc_ajax_nonce_front
                    },
                    dataType: 'json',
                    success:function(res){
                        if( res.success == 1 ){
                            console.log('Save anchor clicks successfully.');
                        } else {
                            console.log('Fail to save anchor clicks.');
                        }
                    }
                });
            }

            if(anchor_target == '_blank'){
                $('<a href="'+ anchor_url +'" target="_blank">External Link</a>')[0].click();
            } else {
                window.location.href = anchor_url;
            }
        } else {
            // external link
            if ( anchor_url != '' && anchor_url != 'javascript:;' ) {
                $.ajax({
                    url:ajax_url + '?action=eblc_check_and_save_clicks',
                    type:'POST',
                    data:{
                        'clicked_url' : anchor_url,
                        'anchor_text' : anchor_text,
                        'eblc_nonce'  : eblc_ajax_var_front.eblc_ajax_nonce_front
                    },
                    dataType: 'json',
                    success:function(res){
                        if( res.success == 1 ){
                            console.log('Save anchor clicks successfully.');
                            if(res.target_option) {
                                $('<a href="'+ res.target_url +'" target="_blank">External Link</a>')[0].click();
                            } else {
                                window.location.href = res.target_url;
                            }                       

                        } else {
                            console.log('Fail to save anchor clicks.');
                            if(anchor_target == '_blank'){
                                $('<a href="'+ anchor_url +'" target="_blank">External Link</a>')[0].click();
                            } else {
                                window.location.href = anchor_url;
                            }
                        }
                    }
                });
            }            
        }

        return false;
    }); 
    
});