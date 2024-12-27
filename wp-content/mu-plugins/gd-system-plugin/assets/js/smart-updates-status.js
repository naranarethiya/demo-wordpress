jQuery(document).ready(function ($) {

    function dismiss() {
        $( '.wpaas-smart-updates' ).on( 'click', '.notice-dismiss', function( event, el ) {
            $(this).parent().remove();
            var dismiss_id = $(this).parent().attr('data-dismiss-id');
            $.ajax({
                url: wpaas_smart_updates_status_object.ajaxUrlDismiss,
                type: 'POST',
                data: {
                    dismiss_id: dismiss_id
                },
                beforeSend: function beforeSend(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpaas_smart_updates_status_object.nonce);
                },
                success: function success(data, textStatus, xhr) {
                },
                error: function error(_error) {
                }
            });
        });
    }



    function fetchSmartUpdateStatus() {
        $.ajax({
            url: wpaas_smart_updates_status_object.ajaxUrlFetch,
            type: 'GET',
            beforeSend: function beforeSend(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpaas_smart_updates_status_object.nonce);
            },
            success: function success(data, textStatus, xhr) {
                var headerEnd = $( '.wp-header-end' );
                if ( ! headerEnd.length ) {
                    headerEnd = $( '.wrap h1, .wrap h2' ).first();
                }
                $( 'div.updated, div.error, div.notice' ).not( '.inline, .below-h2' ).insertAfter( headerEnd );

                for(var i = 0; i < data.data.length; i++) {
                    headerEnd.after('<div class="wpaas-notice wpaas-smart-updates notice notice-warning is-dismissible" data-dismiss-id="'+data.data[i].dismiss_id+'"> <p>'+data.data[i].message+'</p>  <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button> </div>');
                }


                dismiss();
            },
            error: function error(_error) {
            }
        });
    }
    fetchSmartUpdateStatus();
});
