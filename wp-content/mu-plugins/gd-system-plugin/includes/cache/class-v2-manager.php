<?php

namespace WPaaS\Cache;

use WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class V2_Manager implements Cache_Manager {
	public $baseUrls = array();
	public $servers = array();


	public function __construct() {
		$uniqueServers = [];
		// This would be the base URL of the site pre domain change
		$this->baseUrls[] = home_url();
		if ( defined( 'WP_HOME' ) && ! empty( WP_HOME ) ) {
			$this->baseUrls[] = WP_HOME;
		}

		if ( defined( 'VARNISH_SERVERS' ) ) {
			$tmp = explode( ',', VARNISH_SERVERS );
			foreach ( $tmp as $server ) {
				$parts = explode( ':', $server );
				$host  = $parts[0];
				if ( isset( $parts[1] ) ) {
					$port = $parts[1];
				} else {
					$port = 80;
				}
				if ( ! isset( $uniqueServers[ $host ] ) ) {
					$uniqueServers[ $host ] = [];
				}
				$uniqueServers[ $host ][] = $port;
			}

			// Ensure that each server has a port 80
			foreach ( $uniqueServers as $server => $value ) {
				foreach ( $value as $v ) {
					$this->servers[] = [ $server, $v ];
				}
				if ( ! in_array( 80, $value ) ) {
					$this->servers[] = [ $server, 80 ];
				}
			}
		}
	}

	/**
	 * Purge a specific URL
	 *
	 * @param string $url
	 */
	public function purge( $url = null ) {
		$path = preg_replace( '@https?://[^/]+@', '', $url );

		$this->purgePath( $path );
	}

	/**
	 * Purge all cache
	 *
	 * @return void
	 */
	public function ban() {
		$this->purgePath( "/(.*)" );
	}

	/**
	 * Check if full page cache is enabled
	 *
	 * @return bool
	 */
	public function is_full_page_cache_enabled() {
		// V2 is always behind full page cache
		return true;
	}

	/**
	 * Purge a specific path
	 *
	 * @param string $path
	 */
	public function purgePath( $path ) {
		if ( empty( $path ) ) {
			return;
		}
		// We add home_url() again in case the domain has changed
		$this->baseUrls[] = home_url();
		// Add also temp domain to clear redirect caches
		if ( defined( 'GD_TEMP_DOMAIN' ) ) {
			$this->baseUrls[] = 'https://' . GD_TEMP_DOMAIN;
		}
		// Let's make it unique
		$this->baseUrls = array_unique( $this->baseUrls );

		$GLOBALS['wpaas_activity_logger']->log_sp_action( get_current_user_id(),
			sprintf( 'Gateway servers servers: %s, Base URL: %s',
				var_export( $this->servers, true ),
				var_export( $this->baseUrls, true )
			)
		);

		foreach ( $this->baseUrls as $url ) {
			$host = parse_url( $url, PHP_URL_HOST );

			foreach ( $this->servers as $row ) {
				list( $serverHost, $serverPort ) = $row;
				if ( empty( $serverHost ) ) {
					continue;
				}

				$headers = [ 'Host' => $host ];
				switch ( $serverPort ) {
					case 443:
						$scheme                   = 'https';
						$headers['Authorization'] = $this->generateV2AuthorizationHash();
						break;
					default:
						$scheme = 'http';
				}
				$target = $scheme . "://" . $serverHost . $path;

				if ( empty( $target ) ) {
					continue;
				}

				$this->executeRequest( $target, $headers );
			}
		}
	}


	/**
	 * Generate V2 authorization hash
	 *
	 * @return string
	 */
	public function generateV2AuthorizationHash() {
		$config = Plugin::$configs->get_v2_data();
		$key    = $config->apiKey;
		$rand   = sha1( mt_rand() . microtime() );
		$hash   = sha1( $rand . $config->apiSecret );

		return "PAGELY $key $rand $hash";
	}

	private function executeRequest( $target, $headers ) {
		$http = _wp_http_get_object();
		$conf = [
			'method'      => 'PURGE',
			'timeout'     => 3,
			'httpversion' => '1.1',
			'headers'     => $headers
		];

		$response = $http->request( $target, $conf );

		if ( is_wp_error( $response ) ) {
			$code = $response->get_error_code();
			$GLOBALS['wpaas_activity_logger']->log_sp_action( get_current_user_id(),
				sprintf( 'Gateway cache purge error code: %s, target: %s, Host: %s', $code, $target, $headers['Host'] ?? '' )
			);
		} else {
			$code = $response['response']['code'];
			$GLOBALS['wpaas_activity_logger']->log_sp_action( get_current_user_id(),
				sprintf( 'Gateway cache purge success code: %s, target: %s, Host: %s', $code, $target, $headers['Host'] ?? '' )
			);
		}
	}

}