
/**
 * Action handle on plugin page
 */

jQuery(document).ready( function($) {

    /**
     * Action handle When click edit action under url
     */
    $(document).on( 'click', '.eblc_edit_url', function() {
        var link_id = $(this).parent().parent().parent().find('th').eq(0).find('input').eq(0).val();
        var parser_type = $(this).find('a').eq(0).attr('parser_type');
        var link_target = $(this).find('a').eq(0).attr('link_target');
        var current_tr = $(this).parent().parent().parent();
        // Close previous open edit pane
        $('.eblc-inline-editor').each( function(index) {
            $(this).prev().prev().show();
            $(this).prev().remove();
            $(this).remove();
        });

        var link_url = $(this).parent().parent().find('a').eq(0).text();
        var link_text = $(this).parent().parent().parent().find('.link_text').eq(0).text();
        var link_type = $(this).parent().parent().parent().find('.link_type').eq(0).text();

        if ( link_text == '( Anchor text none )' ) {
            link_text = '';
        }

        var follow_option = '';
        var target_option = '';
        if ( parser_type != 'img_src' ) {
            if ( link_type == 'dofollow' ) {
                follow_option = '<input type="radio" name="link_type" value="dofollow" class="link_type_dofollow" checked /> dofollow' + 
                                '<input type="radio" name="link_type" value="nofollow" class="link_type_nofollow" /> nofollow';
            } else if ( link_type == 'nofollow' ) {
                follow_option = '<input type="radio" name="link_type" value="dofollow" class="link_type_dofollow" /> dofollow' + 
                                '<input type="radio" name="link_type" value="nofollow" class="link_type_nofollow" checked /> nofollow';
            } else {
                follow_option = '<input type="radio" name="link_type" value="dofollow" class="link_type_dofollow" /> dofollow' +
                                '<input type="radio" name="link_type" value="nofollow" class="link_type_nofollow" /> nofollow';
            }

            if ( link_target == '_blank' ) {
                target_option = '<input type="checkbox" name="link_target" class="link_target_openinnewwindow" checked /> open in new window';
            } else {
                target_option = '<input type="checkbox" name="link_target" class="link_target_openinnewwindow" /> open in new window';
            }

        }

        var link_text_hidden = "";
        if ( parser_type == 'img_src' ) { 
            link_text_hidden = 'disabled';
            link_text = 'Image';
        }

        var edit_tr = 
            '<tr></tr>' + 
            '<tr id="eblc-edit-url-row" class="eblc-inline-editor">' +
                '<input type="hidden" value="' + link_id + '" class="hidden_link_id" />' +
                '<input type="hidden" value="' + parser_type + '" class="hidden_link_parser_type" />' +
                '<td class="eblc-colspan-change" colspan="6">' +
                    '<div class="eblc-inline-editor-content">' +
                        '<h5>Edit Link</h5>' +
                        '<label>' +
                            '<span class="title"> Anchor Text </span>' +
                            '<span class="eblc-input-text-wrap">' + 
                                '<input type="text" name="link_text" class="eblc-link-text-field" placeholder="Link Text" value="' + link_text + '" ' + link_text_hidden + '/>' +
                            '</span>' + 
                        '</label>' +
                        '<label>' +
                            '<span class="title"> Link URL </span>' +
                            '<span class="eblc-input-text-wrap">' + 
                                '<input type="text" name="link_url" class="eblc-link-url-field" placeholder="Link URL" value="' + link_url + '" />' +
                            '</span>' +
                        '</label>' +
                        '<label>' +
                            follow_option + target_option +
                        '</label>' +
                        '<div class="submit eblc-inline-editor-buttons">' +
                            '<input type="button" class="button-secondary cancel alignleft eblc-cancel-button" value="Cancel">' +
                            '<input type="button" class="button-primary save alignright eblc-update-link-button" value="Update">' +
                        '</div>' +
                    '</div>' +
                '</td>' +
            '</tr>';
        current_tr.after( edit_tr );
        current_tr.hide();
    });
    // Action handle when click cancel edit button on edit link pane
    $(document).on( 'click', '.eblc-cancel-button', function() {
        var adding_tr = $(this).parent().parent().parent().parent();
        var current_tr = adding_tr.prev().prev();
        current_tr.show();
        adding_tr.prev().remove();
        adding_tr.remove();
    });
    // Action handle when click uodate link button on edit link pane
    $(document).on( 'click', '.eblc-update-link-button', function() {

        var adding_tr = $(this).parent().parent().parent().parent();
        var current_tr = adding_tr.prev().prev();

        var parser_type   = $('.hidden_link_parser_type').val();
        // Origin link values
        var old_link_url    = $(this).parent().parent().parent().parent().prev().prev().find('.url').eq(0).find('a').eq(0).text();
        var old_link_text   = $(this).parent().parent().parent().parent().prev().prev().find('.link_text').eq(0).text();
        var old_link_type   = $(this).parent().parent().parent().parent().prev().prev().find('.link_type').eq(0).text();
        var old_link_target = $(this).parent().parent().parent().parent().prev().prev().find('.edit_url').eq(0).find('a').eq(0).attr('link_target');
        // New link values
        var link_id   = $('.hidden_link_id').val();
        var link_text = $('.eblc-link-text-field').val();
        var link_url  = $('.eblc-link-url-field').val();

        var dofollow_tag = $(this).parent().prev().find('.link_type_dofollow').eq(0);
        var nofollow_tag = $(this).parent().prev().find('.link_type_nofollow').eq(0);
        var openinnewwindow_tag = $(this).parent().prev().find('.link_target_openinnewwindow').eq(0);

        if ( parser_type != 'img_src' ) {
            if ( dofollow_tag[0]['checked'] ) {
                link_type = 'dofollow';
            } else if ( nofollow_tag[0]['checked'] ) {
                link_type = 'nofollow';
            } else {
                link_type = 'dofollow';
            }

            if ( openinnewwindow_tag[0]['checked'] ) {
                link_target = '_blank';
            } else {
                link_target = '_self';
            }
        } else {
            old_link_text   = 'image';
            old_link_type   = 'image';
            old_link_target = 'image';
            link_text       = 'image';                    
            link_type       = 'image';
            link_target     = 'image';
        }

        // Compare between old values and new values
        if ( ( old_link_url != link_url ) || ( old_link_text != link_text ) || ( old_link_type != link_type ) || ( old_link_target != link_target ) ) {
            var self = this;
            var ajax_url = eblc_ajax_var.eblc_ajax_url;
            $(this).val('Waiting...');
            var send_data = {
                'link_id'     : link_id,
                'link_url'    : link_url,
                'link_text'   : link_text,
                'link_type'   : link_type,
                'link_target' : link_target,
                'eblc_nonce'  : eblc_ajax_var.eblc_ajax_nonce
            };

            $.ajax({
                url:ajax_url + '?action=eblc_update_link',
                type: 'POST',
                data: send_data,
                dataType: 'json',
                success: function(res){
                    if( res.success == 1 ){
                        location.reload();
                    } else {
                        alert( res.error );
                        location.reload();
                    }
                }
            });
        } else {
            current_tr.show();
            adding_tr.prev().remove();
            adding_tr.remove();
        }
    });

    /**
     * Action handle when click unlink action under url
     */
    $(document).on( 'click', '.eblc_trash_url', function() {
        $(this).find('a').eq(0).html('Waiting...');
        var link_id = $(this).parent().parent().parent().find('th').eq(0).find('input').eq(0).val();
        var ajax_url = eblc_ajax_var.eblc_ajax_url;
        $.ajax({
            url:ajax_url + '?action=eblc_unlink_url',
            type:'POST',
            data:{
                'link_id'    : link_id,
                'eblc_nonce' : eblc_ajax_var.eblc_ajax_nonce
            },
            dataType: 'json',
            success:function(res){
                if( res.success == 1 ){
                    location.reload();
                } else {
                    alert( res.error );
                    location.reload();
                }
            }
        });
    });

    /**
     * Action handle when click recheck action under url
     */
    $(document).on( 'click', '.eblc_recheck_url', function() {
        $(this).find('a').eq(0).html('Waiting...');
        var link_id = $(this).parent().parent().parent().find('th').eq(0).find('input').eq(0).val();
        var ajax_url = eblc_ajax_var.eblc_ajax_url;
        $.ajax({
            url:ajax_url + '?action=eblc_recheck_url',
            type:'POST',
            data:{
                'link_id'    : link_id,
                'eblc_nonce' : eblc_ajax_var.eblc_ajax_nonce
            },
            dataType: 'json',
            success:function(res){
                if(res.success == 1){
                    location.reload();
                } else {
                    alert( res.error );
                    location.reload();
                }
            }
        });
    });

    /**
     * Action handle when click details action under status
     */
    $(document).on( 'click', '.eblc_link_details', function() {
        var link_id = $(this).parent().parent().parent().find('th').eq(0).find('input').eq(0).val();
        var current_tr = $(this).parent().parent().parent();
        var classname = current_tr.attr('class');
        if ( classname == 'link-details-open' ) {
            $(this).find('a').eq(0).text('Details');
            current_tr.next().remove();
            current_tr.removeClass('link-details-open');
        } else {
            $(this).find('a').eq(0).text('Waiting...');
            var self = this;
            current_tr.addClass('link-details-open');
            
            var ajax_url = eblc_ajax_var.eblc_ajax_url;
            $.ajax({
                url:ajax_url + '?action=eblc_get_link_data',
                type:'POST',
                data:{
                    'link_id'    : link_id,
                    'eblc_nonce' : eblc_ajax_var.eblc_ajax_nonce
                },
                dataType: 'json',
                success:function(res){
                    if( res.success == 1 ){
                        var link = res.link;                            
                        var edit_tr = 
                            '<tr id="link-details" class="blc-link-details" style="display: table-row;">' +
                                '<td colspan="6">' +
                                    '<div class="blc-detail-container">' +
                                        '<div class="blc-detail-block" style="float: left; width: 30%;">' +
                                            '<ol style="list-style-type: none; margin-right: 20px;">' +
                                                '<li><strong>Link last checked: </strong>' +
                                                    '<span class="check_date">' + link['last_check_attempt'] + '</span>' +
                                                '</li>' +
                                                '<li><strong>HTTP code: </strong>' +
                                                    '<span class="http_code">' + link['http_code'] + '</span>' +
                                                '</li>' +
                                                '<li><strong>Response time: </strong>' +
                                                    '<span class="request_duration">' + link['request_duration'] + ' seconds</span>' +
                                                '</li>' +
                                                '<li><strong>Final URL: </strong>' +
                                                    '<span class="final_url">' + link['final_url'] + '</span>' +
                                                '</li>' +
                                            '</ol>' +
                                        '</div>' +
                                        '<div class="blc-detail-block" style="float: right; width: 70%;">' +
                                            '<ol style="list-style-type: none; margin-left: 0px;">' +
                                                '<li><strong>Log: </strong>' +
                                                    '<span class="blc_log">HTTP code: ' + link['http_code'] +
                                                    '</span><br>' +
                                                    '<span class="blc_log">===================' +
                                                    '</span><br>' +
                                                    '<span class="blc_log">' + link['log'] +
                                                    '</span>' +
                                                '</li>' +
                                            '</ol>' +
                                        '</div>' +
                                        '<div style="clear:both;"> </div>' +
                                    '</div>' +
                                '</td>' +
                            '</tr>';
                        current_tr.after( edit_tr );

                        $(self).find('a').eq(0).text('Close');
                    } else {
                        alert( res.error );
                        location.reload();
                    }
                }
            });
        }
    });


    /**
     ** URL Shorten Part
     * Action handle when click shorten url save and create button
     */
    $(document).on( 'click', '#eblc_shorten_url_btn', function() {        

        if ( $('#eblc_target_url').val() != '' && $('#eblc_shorten_url').val() != '' ) {
            var target_url = $('#eblc_shorten_target_url').val();
            var shorten_url = $('#eblc_shorten_url').val();

            var ajax_url = eblc_ajax_var.eblc_ajax_url;
            $.ajax({
                url:ajax_url + '?action=eblc_save_shorten_url',
                type:'POST',
                data:{
                    'target_url'  : target_url,
                    'shorten_url' : shorten_url,
                    'eblc_nonce'  : eblc_ajax_var.eblc_ajax_nonce
                },
                dataType: 'json',
                success:function(res){
                    if( res.success == 1 ){
                        $('#eblc_shorten_target_url').val('');
                        $('#eblc_shorten_url').val('');
                        location.reload();
                    } else {
                        alert( res.error );
                        $('#eblc_shorten_target_url').val('');
                        $('#eblc_shorten_url').val('');
                        location.reload();
                    }
                }
            });
        }
    });

    /**
     * Action handle When click edit action under shorten url
     */
    $(document).on( 'click', '.eblc_edit_shorten_url', function() {
        var link_id    = $(this).parent().parent().parent().find('th').eq(0).find('input').eq(0).val();
        var current_tr = $(this).parent().parent().parent();
        // Close previous open edit pane
        $('.eblc-inline-editor-for-shorten').each( function(index) {
            $(this).prev().prev().show();
            $(this).prev().remove();
            $(this).remove();
        });

        var target_url  = $(this).parent().parent().find('.eblc_edit_shorten_target_url').eq(0).text();
        var shorten_url = $(this).parent().parent().parent().find('.shorten_url').eq(0).find('input').eq(0).val();

        var edit_tr = 
            '<tr></tr>' + 
            '<tr id="eblc-edit-shorten-url-row" class="eblc-inline-editor-for-shorten">' +
                '<input type="hidden" value="' + link_id + '" class="hidden_shorten_link_id" />' +
                '<td class="eblc-colspan-change" colspan="6">' +
                    '<div class="eblc-inline-editor-content">' +
                        '<h5>Edit Shorten URL</h5>' +
                        '<label>' +
                            '<span class="title"> Target URL </span>' +
                            '<span class="eblc-input-text-wrap">' + 
                                '<input type="text" class="eblc-shorten-target-url" placeholder="Affiliate or any other links" value="' + target_url + '" />' +
                            '</span>' + 
                        '</label>' +
                        '<label>' +
                            '<span class="title"> Shorten URL </span>' +
                            '<span class="eblc-input-text-wrap">' + 
                                '<input type="text" class="eblc-shorten-url" placeholder="Shorten URL" value="' + shorten_url + '" />' +
                            '</span>' +
                        '</label>' +
                        '<div class="submit eblc-inline-editor-buttons">' +
                            '<input type="button" class="button-secondary cancel alignleft eblc-shorten-cancel-button" value="Cancel">' +
                            '<input type="button" class="button-primary save alignright eblc-update-shorten-link-button" value="Update">' +
                        '</div>' +
                    '</div>' +
                '</td>' +
            '</tr>';
        current_tr.after( edit_tr );
        current_tr.hide();
    });
    // Action handle when click cancel edit button on edit shorten link pane
    $(document).on( 'click', '.eblc-shorten-cancel-button', function() {
        var adding_tr  = $(this).parent().parent().parent().parent();
        var current_tr = adding_tr.prev().prev();
        current_tr.show();
        adding_tr.prev().remove();
        adding_tr.remove();
    });
    // Action handle when click uodate link button on edit shorten link pane
    $(document).on( 'click', '.eblc-update-shorten-link-button', function() {

        var adding_tr  = $(this).parent().parent().parent().parent();
        var current_tr = adding_tr.prev().prev();

        // Origin link values
        var old_target_url  = $(this).parent().parent().parent().parent().prev().prev().find('.url').eq(0).find('.eblc_edit_shorten_target_url').eq(0).text();
        var old_shorten_url = $(this).parent().parent().parent().parent().prev().prev().find('.shorten_url').eq(0).find('input').eq(0).val();
        // New link values
        var shorten_link_id = $('.hidden_shorten_link_id').val();
        var target_url      = $('.eblc-shorten-target-url').val();
        var shorten_url     = $('.eblc-shorten-url').val();


        // Compare between old values and new values
        if ( ( old_target_url != target_url ) || ( old_shorten_url != shorten_url ) ) {
            var self = this;
            var ajax_url = eblc_ajax_var.eblc_ajax_url;
            $(this).val('Waiting...');
            var send_data = {
                'shorten_link_id' : shorten_link_id,
                'target_url'      : target_url,
                'shorten_url'     : shorten_url,
                'eblc_nonce'      : eblc_ajax_var.eblc_ajax_nonce
            };

            $.ajax({
                url:ajax_url + '?action=eblc_update_shorten_link',
                type: 'POST',
                data: send_data,
                dataType: 'json',
                success: function(res){
                    if( res.success == 1 ){
                        location.reload();
                    } else {
                        alert( res.error );
                        location.reload();
                    }
                }
            });
        } else {
            current_tr.show();
            adding_tr.prev().remove();
            adding_tr.remove();
        }
    });

    /**
     * Action handle when click unlink action under url
     */
    $(document).on( 'click', '.eblc_trash_shorten_url', function() {
        $(this).find('a').eq(0).html('Waiting...');
        var shorten_link_id = $(this).parent().parent().parent().find('th').eq(0).find('input').eq(0).val();
        var ajax_url = eblc_ajax_var.eblc_ajax_url;
        $.ajax({
            url:ajax_url + '?action=eblc_delete_shorten_link',
            type:'POST',
            data:{
                'shorten_link_id' : shorten_link_id,
                'eblc_nonce'      : eblc_ajax_var.eblc_ajax_nonce
            },
            dataType: 'json',
            success:function(res){
                if( res.success == 1 ){
                    location.reload();
                } else {
                    alert( res.error );
                    location.reload();
                }
            }
        });
    });



    /**
     ** URL Redirection Part
     * Action handle when click redirection url save and changes button
     */
    $(document).on( 'click', '#eblc_redirection_url_btn', function() {        

        if ( $('#eblc_redirection_source_url').val() != '' && $('#eblc_redirection_target_url').val() != '' ) {
            var source_url       = $('#eblc_redirection_source_url').val();
            var target_url       = $('#eblc_redirection_target_url').val();
            var redirection_type = $('#eblc_redirection_type').val();

            var ajax_url = eblc_ajax_var.eblc_ajax_url;
            $.ajax({
                url:ajax_url + '?action=eblc_save_redirection_url',
                type:'POST',
                data:{
                    'source_url'       : source_url,
                    'target_url'       : target_url,
                    'redirection_type' : redirection_type,
                    'eblc_nonce'       : eblc_ajax_var.eblc_ajax_nonce
                },
                dataType: 'json',
                success:function(res){
                    if( res.success == 1 ){
                        $('#eblc_redirection_source_url').val('');
                        $('#eblc_redirection_target_url').val('');
                        location.reload();
                    } else {
                        alert( res.error );
                        $('#eblc_redirection_source_url').val('');
                        $('#eblc_redirection_target_url').val('');
                        location.reload();
                    }
                }
            });
        }
    });


    /**
     * Action handle When click edit action under redirection url
     */
    $(document).on( 'click', '.eblc_edit_redirection_url', function() {
        var link_id    = $(this).parent().parent().parent().find('th').eq(0).find('input').eq(0).val();
        var current_tr = $(this).parent().parent().parent();
        // Close previous open edit pane
        $('.eblc-inline-editor-for-redirection').each( function(index) {
            $(this).prev().prev().show();
            $(this).prev().remove();
            $(this).remove();
        });

        var source_url       = $(this).parent().parent().find('.eblc_edit_redirection_source_url').eq(0).val();
        var target_url       = $(this).parent().parent().parent().find('.target_url').eq(0).text();
        var redirection_type = $(this).parent().parent().parent().find('.redirection_type').eq(0).text();
        redirection_type = Number( redirection_type.split(' ')[0] );


        var edit_tr = 
            '<tr></tr>' + 
            '<tr id="eblc-edit-redirection-url-row" class="eblc-inline-editor-for-redirection">' +
                '<input type="hidden" value="' + link_id + '" class="hidden_redirection_link_id" />' +
                '<td class="eblc-colspan-change" colspan="6">' +
                    '<div class="eblc-inline-editor-content">' +
                        '<h5>Edit Redirection URL</h5>' +
                        '<label>' +
                            '<span class="title"> Source URL </span>' +
                            '<span class="eblc-input-text-wrap">' + 
                                '<input type="text" class="eblc-redirection-source-url" placeholder="Source URL" value="' + source_url + '" />' +
                            '</span>' + 
                        '</label>' +
                        '<label>' +
                            '<span class="title"> Target URL </span>' +
                            '<span class="eblc-input-text-wrap">' + 
                                '<input type="text" class="eblc-redirection-target-url" placeholder="Target URL" value="' + target_url + '" />' +
                            '</span>' +
                        '</label>' +
                        '<label>' +
                            '<span class="title"> Type </span>' +
                            '<span class="eblc-input-text-wrap">' + 
                                '<select type="text" class="eblc-redirection-type" >' +
                                    '<option value="301" ' + (redirection_type == 301? 'selected':'') + ' >301 Redirect</option>' +
                                    '<option value="302" ' + (redirection_type == 302? 'selected':'') + ' >302 Redirect</option>' +
                                    '<option value="307" ' + (redirection_type == 307? 'selected':'') + ' >307 Redirect</option>' +
                                '</select>' +
                            '</span>' +
                        '</label>' +
                        '<div class="submit eblc-inline-editor-buttons">' +
                            '<input type="button" class="button-secondary cancel alignleft eblc-redirection-cancel-button" value="Cancel">' +
                            '<input type="button" class="button-primary save alignright eblc-update-redirection-link-button" value="Update">' +
                        '</div>' +
                    '</div>' +
                '</td>' +
            '</tr>';
        current_tr.after( edit_tr );
        current_tr.hide();
    });
    // Action handle when click cancel edit button on edit redirection link pane
    $(document).on( 'click', '.eblc-redirection-cancel-button', function() {
        var adding_tr  = $(this).parent().parent().parent().parent();
        var current_tr = adding_tr.prev().prev();
        current_tr.show();
        adding_tr.prev().remove();
        adding_tr.remove();
    });
    // Action handle when click uodate link button on edit redirection link pane
    $(document).on( 'click', '.eblc-update-redirection-link-button', function() {

        var adding_tr  = $(this).parent().parent().parent().parent();
        var current_tr = adding_tr.prev().prev();

        // Origin link values
        var old_source_url       = $(this).parent().parent().parent().parent().prev().prev().find('.url').eq(0).find('.eblc_edit_redirection_source_url').eq(0).val();
        var old_target_url      = $(this).parent().parent().parent().parent().prev().prev().find('.target_url').eq(0).text();
        var old_redirection_type = $(this).parent().parent().parent().parent().prev().prev().find('.redirection_type').eq(0).text();
        old_redirection_type     = Number( old_redirection_type.split(' ')[0] );
        // New link values
        var redirection_link_id  = $('.hidden_redirection_link_id').val();
        var source_url           = $('.eblc-redirection-source-url').val();
        var target_url           = $('.eblc-redirection-target-url').val();
        var redirection_type     = $('.eblc-redirection-type').val();


        // Compare between old values and new values
        if ( ( old_source_url != source_url ) || ( old_target_url != target_url ) || ( old_redirection_type != redirection_type ) ) {
            var self = this;
            var ajax_url = eblc_ajax_var.eblc_ajax_url;
            $(this).val('Waiting...');
            var send_data = {
                'redirection_link_id' : redirection_link_id,
                'source_url'          : source_url,
                'target_url'          : target_url,
                'redirection_type'    : redirection_type,
                'eblc_nonce'          : eblc_ajax_var.eblc_ajax_nonce
            };

            $.ajax({
                url:ajax_url + '?action=eblc_update_redirection_link',
                type: 'POST',
                data: send_data,
                dataType: 'json',
                success: function(res){
                    if( res.success == 1 ){
                        location.reload();
                    } else {
                        alert( res.error );
                        location.reload();
                    }
                }
            });
        } else {
            current_tr.show();
            adding_tr.prev().remove();
            adding_tr.remove();
        }
    });

    /**
     * Action handle when click unlink action under url
     */
    $(document).on( 'click', '.eblc_trash_redirection_url', function() {
        $(this).find('a').eq(0).html('Waiting...');
        var redirection_link_id = $(this).parent().parent().parent().find('th').eq(0).find('input').eq(0).val();
        var ajax_url = eblc_ajax_var.eblc_ajax_url;
        $.ajax({
            url:ajax_url + '?action=eblc_delete_redirection_link',
            type:'POST',
            data:{
                'redirection_link_id' : redirection_link_id,
                'eblc_nonce'          : eblc_ajax_var.eblc_ajax_nonce
            },
            dataType: 'json',
            success:function(res){
                if( res.success == 1 ){
                    location.reload();
                } else {
                    alert( res.error );
                    location.reload();
                }
            }
        });
    });


    /**
     * Instant check button click event
     */
    $(document).on( 'click', '#eblc_instant_check', function() {
        $(this).text('Checking now...');
        $('#eblc_instant_check_spinner').show();
        var ajax_url = eblc_ajax_var.eblc_ajax_url;
        $.ajax({
            url:ajax_url + '?action=eblc_instant_check',
            type:'POST',
            data:{
                'eblc_nonce' : eblc_ajax_var.eblc_ajax_nonce
            },
            dataType: 'json',
            success:function(res){
                if( res.success == 1 ){
                    location.reload();
                } else {
                    alert( res.error );
                    location.reload();
                }
            }
        });

    });


    /**
     * Clipboard button click event
     */    
    $(document).on( 'click', '.eblc_clipboard_btn', function() {
        var copyText = $(this).parent().find('input').eq(0);
        copyText.select();
        document.execCommand("copy");
    });



    /**
     ** Auto linking Part
     * Action handle When click edit action under anchor text
     */
    $(document).on( 'click', '.eblc_edit_auto_links', function() {
        var link_id    = $(this).parent().parent().parent().find('th').eq(0).find('input').eq(0).val();
        var current_tr = $(this).parent().parent().parent();
        // Close previous open edit pane
        $('.eblc-inline-editor-for-autolinking').each( function(index) {
            $(this).prev().prev().show();
            $(this).prev().remove();
            $(this).remove();
        });

        var anchor_text  = $(this).parent().parent().find('.eblc_edit_autolinking_anchor_text').eq(0).text();
        var target_url = $(this).parent().parent().parent().find('.target_url').eq(0).text();

        var edit_tr = 
            '<tr></tr>' + 
            '<tr id="eblc-edit-auto-link-row" class="eblc-inline-editor-for-autolinking">' +
                '<input type="hidden" value="' + link_id + '" class="hidden_auto_link_id" />' +
                '<td class="eblc-colspan-change" colspan="6">' +
                    '<div class="eblc-inline-editor-content">' +
                        '<h5>Edit Auto Link</h5>' +
                        '<label>' +
                            '<span class="title"> Anchor Text </span>' +
                            '<span class="eblc-input-text-wrap">' + 
                                '<input type="text" class="eblc-autolinking-anchor-text" placeholder="Keyword" value="' + anchor_text + '" />' +
                            '</span>' + 
                        '</label>' +
                        '<label>' +
                            '<span class="title"> Target URL </span>' +
                            '<span class="eblc-input-text-wrap">' + 
                                '<input type="text" class="eblc-autolinking-target-url" placeholder="Target URL" value="' + target_url + '" />' +
                            '</span>' +
                        '</label>' +
                        '<div class="submit eblc-inline-editor-buttons">' +
                            '<input type="button" class="button-secondary cancel alignleft eblc-autolinking-cancel-button" value="Cancel">' +
                            '<input type="button" class="button-primary save alignright eblc-update-autolinking-button" value="Update">' +
                        '</div>' +
                    '</div>' +
                '</td>' +
            '</tr>';
        current_tr.after( edit_tr );
        current_tr.hide();
    });
    // Action handle when click cancel edit button on edit auto link pane
    $(document).on( 'click', '.eblc-autolinking-cancel-button', function() {
        var adding_tr  = $(this).parent().parent().parent().parent();
        var current_tr = adding_tr.prev().prev();
        current_tr.show();
        adding_tr.prev().remove();
        adding_tr.remove();
    });
    // Action handle when click uodate link button on edit auto link pane
    $(document).on( 'click', '.eblc-update-autolinking-button', function() {

        var adding_tr  = $(this).parent().parent().parent().parent();
        var current_tr = adding_tr.prev().prev();

        // Origin link values
        var old_anchor_text  = $(this).parent().parent().parent().parent().prev().prev().find('.anchor_text').eq(0).find('.eblc_edit_autolinking_anchor_text').eq(0).text();
        var old_target_url = $(this).parent().parent().parent().parent().prev().prev().find('.target_url').eq(0).text();
        // New link values
        var auto_link_id = $('.hidden_auto_link_id').val();
        var anchor_text  = $('.eblc-autolinking-anchor-text').val();
        var target_url   = $('.eblc-autolinking-target-url').val();


        // Compare between old values and new values
        if ( ( old_anchor_text != anchor_text ) || ( old_target_url != target_url ) ) {
            var self = this;
            var ajax_url = eblc_ajax_var.eblc_ajax_url;
            $(this).val('Waiting...');
            var send_data = {
                'auto_link_id' : auto_link_id,
                'anchor_text'  : anchor_text,
                'target_url'   : target_url,
                'eblc_nonce'   : eblc_ajax_var.eblc_ajax_nonce
            };

            $.ajax({
                url:ajax_url + '?action=eblc_update_auto_link',
                type: 'POST',
                data: send_data,
                dataType: 'json',
                success: function(res){
                    if( res.success == 1 ){
                        location.reload();
                    } else {
                        alert( res.error );
                        location.reload();
                    }
                }
            });
        } else {
            current_tr.show();
            adding_tr.prev().remove();
            adding_tr.remove();
        }
    });

    /**
     * Action handle when click unlink action under anchor text
     */
    $(document).on( 'click', '.eblc_trash_auto_links', function() {
        $(this).find('a').eq(0).html('Waiting...');
        var auto_link_id = $(this).parent().parent().parent().find('th').eq(0).find('input').eq(0).val();
        var ajax_url = eblc_ajax_var.eblc_ajax_url;
        $.ajax({
            url:ajax_url + '?action=eblc_delete_auto_link',
            type:'POST',
            data:{
                'auto_link_id' : auto_link_id,
                'eblc_nonce'   : eblc_ajax_var.eblc_ajax_nonce
            },
            dataType: 'json',
            success:function(res){
                if( res.success == 1 ){
                    location.reload();
                } else {
                    alert( res.error );
                    location.reload();
                }
            }
        });
    });


    /**
     * Create new auto links
     */
    $(document).on( 'click', '.eblc_auto_linking_create_btn', function() {
        var auto_linking_anchor_text = $('#eblc_auto_linking_anchor_text').val();
        var auto_linking_url = $('#eblc_auto_linking_url').val();

        if(auto_linking_anchor_text != '' && auto_linking_url != '') {

            $(this).text('Creating now...');
            $('#eblc_create_autolinking_spinner').show();
            var ajax_url = eblc_ajax_var.eblc_ajax_url;
            $.ajax({
                url:ajax_url + '?action=eblc_create_auto_link',
                type:'POST',
                data:{
                    'anchor_text' : auto_linking_anchor_text,
                    'target_url'    : auto_linking_url,
                    'eblc_nonce'  : eblc_ajax_var.eblc_ajax_nonce
                },
                dataType: 'json',
                success:function(res){
                    if( res.success == 1 ){
                        $('#eblc_auto_linking_anchor_text').val('');
                        $('#eblc_auto_linking_url').val('');
                        location.reload();
                    } else {
                        alert( res.error );
                        location.reload();
                    }
                }
            });
        }
    });

});