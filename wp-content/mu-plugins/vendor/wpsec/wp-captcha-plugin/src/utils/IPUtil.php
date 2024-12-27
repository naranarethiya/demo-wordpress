<?php

namespace Wpsec\captcha\utils;

class IPUtil {

	/**
	 * Returns all relevant client IP headers to Manged WordPress platform
	 *
	 * @return  array  Client IP headers
	 * @since   1.0.0
	 *
	 */
	public static function get_client_ip_headers() {
		$ip_addresses   = array();
		$lookup_headers = array(
			'HTTP_X_SUCURI_CLIENTIP',
			'HTTP_X_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $lookup_headers as $lookup_header ) {
			if ( isset( $_SERVER[ $lookup_header ] ) ) {
				$ip_addresses[ $lookup_header ] = $_SERVER[ $lookup_header ];
			}
		}

		return $ip_addresses;
	}
}
