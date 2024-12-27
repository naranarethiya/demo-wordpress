<?php


namespace WPaaS;

class XMLRPC {

    /**
     * Class constructor.
     */
    public function __construct() {

        if ( $this->is_xmlrcp_enabled() ) {
            return;
        }

        // Disable all xml-rpc endpoints
        add_filter('xmlrpc_methods', function () {
            return [];
        }, PHP_INT_MAX);
        add_filter( 'xmlrpc_enabled', '__return_false' );
        add_filter( 'wp_headers', [ $this, 'disable_x_pingback' ] );
    }

    /**
     * Check if XML-RPC is enabled
     * @return boolean
     */
    public function is_xmlrcp_enabled() {
        return get_option( 'is_xmlrpc_enabled', 'enabled' ) === 'enabled';
    }

    public function disable_x_pingback( $headers ) {
        unset( $headers['x_pingback'] );
        return $headers;
    }
}