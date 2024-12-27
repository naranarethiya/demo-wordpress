/**
 * Admin code for dismissing notifications.
 *
 */
jQuery( document ).ready( function( $ ) {
    $( '.wpaas-notice' ).on( 'click', '.notice-dismiss', function( event, el ) {

        var $notice = $(this).parent('.notice.is-dismissible');
        var dismiss_id = $notice.attr('data-dismiss-id');
        if ( dismiss_id ) {
            window.wp.apiRequest({
                path: '/wpaas/v1/dismiss-notice',
                type: 'POST',
                data: {
                    id: dismiss_id
                }
            });

        }
    });
} );