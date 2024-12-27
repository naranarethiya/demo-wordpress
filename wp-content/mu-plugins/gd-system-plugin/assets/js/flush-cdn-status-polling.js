jQuery(document).ready(function ($) {
    var counter = 0;
    var limit = 30;
    function poll() {
        $.ajax({
            url: wpaas_flush_cdn_polling_object.ajaxUrl,
            type: 'GET',
            beforeSend: function beforeSend(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpaas_flush_cdn_polling_object.nonce);
            },
            success: function success(data, textStatus, xhr) {
                if (!data) {
                    cleanup();
                    return;
                }
                if (!data.data) {
                    cleanup();
                    return;
                }
                var flush_status = data.data.flush_status;
                if (flush_status === 'SUCCESS' && counter < limit) {
                    cleanup();
                    $.gritter.add({
                        title: 'Success',
                        text: 'Cache cleared.',
                        time: 5000
                    });
                } else if (flush_status === 'PENDING') {
                    counter++;
                }
                if (counter >= limit) {
                    cleanup();
                    $.gritter.add({
                        title: 'Failed',
                        text: 'Flush cache failed.',
                        time: 5000
                    });
                }
            },
            error: function error(_error) {
                cleanup();
                $.gritter.add({
                    title: 'Failed',
                    text: 'Flush cache failed.',
                    time: 5000
                });
            }
        });
    }
    var pollInterval = setInterval(function () {
        poll();
    }, 10000);
    function cleanup() {
        counter = 0;
        clearInterval(pollInterval);
    }
    poll();
});