<?php


namespace WPaaS;

class GMV {
	const OPTION_KEY = 'wpaas-paypal-gmv';

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( ! $GLOBALS['wpaas_feature_flag']->get_feature_flag_value( 'gmv_monitor', false ) ) {
			return;
		}
		add_filter( 'pre_http_request', [ $this, 'parse_headers' ], 10, 3 );
	}

	/**
	 * @param false|array|\WP_Error $response
	 * @param array $parsed_args
	 * @param string $url
	 *
	 * @return bool
	 */
	public function parse_headers( $response, $parsed_args, $url ) {
		if ( strpos( $url, 'paypal.com/v2/checkout/orders' ) !== false && strpos( $url, 'sandbox' ) === false ) {
			if ( empty( $parsed_args ) || empty( $parsed_args['body'] ) || ! is_string( $parsed_args['body'] ) ) {
				return false;
			}
			$key  = date( 'Ymd' );
			$body = json_decode( $parsed_args['body'], true );
			if ( $body === null ) {
				return false;
			}
			$amountObj = $body['purchase_units'][0]['amount'];
			$value     = $amountObj['value'];
			$currency  = $amountObj['currency_code'];
			$bn_code   = isset( $parsed_args['headers']['PayPal-Partner-Attribution-Id'] );


			$gmv = json_decode( get_option( self::OPTION_KEY, '{}' ), true );
			if ( ! array_key_exists( $key, $gmv ) ) {
				$gmv[ $key ] = [ 'partner_id' => 0, 'no_partner_id' => 0, 'transactions' => 0, ];
			}
			if ( ! array_key_exists( $currency, $gmv[ $key ] ) ) {
				$gmv[ $key ][ $currency ] = 0;
			}
			$gmv[ $key ][ $currency ] += floatval( $value );
			if ( $bn_code ) {
				$gmv[ $key ]['partner_id'] += 1;
			} else {
				$gmv[ $key ]['no_partner_id'] += 1;
			}
			$gmv[ $key ]['transactions'] += 1;

			$trace = debug_backtrace();
			foreach ( $trace as $line ) {
				if ( strpos( $line['file'], '/var/www/wp-content/plugins/' ) !== false ) {
					$slugs  = explode( '/', $line['file'] );
					$plugin = $slugs[5];
					if ( ! array_key_exists( $plugin, $gmv[ $key ] ) ) {
						$gmv[ $key ][ $plugin ] = 0;
					}
					$gmv[ $key ][ $plugin ] += 1;
					break;
				}
			}
			ksort( $gmv );
			$save = array_slice( $gmv, - 10, null, true );

			update_option( self::OPTION_KEY, json_encode( $save ) );
		}

		return false;

	}
}
