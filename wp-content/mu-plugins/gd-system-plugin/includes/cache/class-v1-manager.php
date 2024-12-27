<?php

namespace WPaaS\Cache;

use WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class V1_Manager implements Cache_Manager {

	/**
	 * Curl host to resolve to VIP
	 *
	 * @var array
	 */
	public static $curlResolvePairs = [];


	/**
	 * Purge a specific URL
	 *
	 * @param string $url
	 */
	public function purge( $url = null ) {
		$this->request( 'PURGE', $url );
	}

	/**
	 * Purge all cache
	 *
	 * @return void
	 */
	public function ban() {
		$this->request( 'BAN' );
	}

	/**
	 * Check if Full Page Cache is enabled
	 *
	 * @return bool
	 */
	public function is_full_page_cache_enabled() {
		return defined( 'GD_CDN_FULLPAGE' ) && GD_CDN_FULLPAGE;
	}

	/**
	 * Make a non-blocking request to Varnish.
	 *
	 * @param string $method
	 * @param string $url (optional)
	 */
	private function request( $method, $url = null ) {

		// Curl Resolve binding
		add_action( 'http_api_curl', [ $this, 'curl_resolve' ], PHP_INT_MAX, 3 );

		$url  = empty( $url ) ? home_url() : $url;
		$host = parse_url( $url, PHP_URL_HOST );

		$urlHttp      = set_url_scheme( $url, 'http' );
		$urlHttps     = set_url_scheme( $url, 'https' );
		$httpResolve  = $host . ":80:" . Plugin::vip();
		$httpsResolve = $host . ":443:" . Plugin::vip();

		if ( ! in_array( $httpResolve, self::$curlResolvePairs ) ) {
			self::$curlResolvePairs[] = $httpResolve;
		}
		if ( ! in_array( $httpsResolve, self::$curlResolvePairs ) ) {
			self::$curlResolvePairs[] = $httpsResolve;
		}

		// This will force persistent APCu cache to flush across servers.
		update_option( 'gd_system_last_cache_flush', time() );

		/** Logg user action */
		$GLOBALS['wpaas_activity_logger']->log_sp_action( get_current_user_id(),
			sprintf( 'Platform flush cache call. type: %s', $method )
		);

		$this->executeRequest( $method, $host, $urlHttp );
		$this->executeRequest( $method, $host, $urlHttps );

		if ( $this->is_full_page_cache_enabled() && stripos( $url, 'https://' ) !== false ) {
			// In case this is purge request we want to save original resolve pairs for loops
			$originalResolvePairs   = self::$curlResolvePairs;
			self::$curlResolvePairs = [];
			$this->executeRequest( $method, $host, $urlHttps );
			self::$curlResolvePairs = $originalResolvePairs;
		}

	}


	public function curl_resolve( $handle, $parsed_args, $url ) {
		curl_setopt( $handle, CURLOPT_RESOLVE, self::$curlResolvePairs );
	}

	private function executeRequest( $method, $host, $url ) {
		wp_remote_request(
			esc_url_raw( $url ),
			[
				'method'   => $method,
				'blocking' => false,
				'headers'  => [
					'Host' => $host,
				],
			]
		);
	}


}