<?php

namespace WPaaS;

use \WP_Error;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

interface API_Interface {

	public function get_disallowed_plugins();

	public function is_valid_sso_hash( $hash );

	public function user_changed_domain( $domain = '' );

	public function get_woocommerce_products( $product_type );

	public function refresh_blog_title( $blogname = null );

	public function refresh_nextgen_compatibility( $is_nextgen_compat = false );

	public function toggle_rum( $enabled = true );

	public function flush_cdn();

	public function backup_now();

	public function get_failed_smart_updates();

	public function smart_update_notice_dismiss( $dismiss_id );
	public function hmt_register( $key );

}

final class API implements API_Interface {

	/**
	 * Return an array of disallowed plugins.
	 *
	 * Note: The transient used here is persistent, meaning it
	 * will not be short-circuited by object cache and it will
	 * always be set to a non-false value regardless of the API
	 * response.
	 *
	 * @return array
	 */
	public function get_disallowed_plugins() {

		$transient = Plugin::get_persistent_site_transient( 'gd_system_disallowed_plugins' );

		if ( false !== $transient ) {

			return (array) $transient;

		}

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );

		$response = $this->call( 'disallowPlugins/' );

		$body               = json_decode( wp_remote_retrieve_body( $response ), true );
		$disallowed_plugins = ! empty( $body['disallowPlugins'] ) ? (array) $body['disallowPlugins'] : [];

		Plugin::set_persistent_site_transient( 'gd_system_disallowed_plugins', $disallowed_plugins, HOUR_IN_SECONDS );

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );

		return $disallowed_plugins;

	}

	/**
	 * Validate an SSO hash.
	 *
	 * @param  string $hash
	 *
	 * @return bool
	 */
	public function is_valid_sso_hash( $hash ) {

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$method_args = [
			'token'    => $hash,
			'database' => DB_NAME
		];

		$response = $this->call(
			"sso/allowLogin/",
			$method_args,
			'POST'
		);

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		return isset( $body['allow'] ) ? $body['allow'] : false;

	}

	/**
	 * Check if a user has changed their domain.
	 *
	 * It isn't reflected here yet because we're waiting on the
	 * DNS TTL to take effect.
	 *
	 * Note: The transient used here is persistent, meaning it
	 * will not be short-circuited by object cache and it will
	 * always be set to a non-false value regardless of the API
	 * response.
	 *
	 * @param  string $cname (optional)
	 *
	 * @return bool
	 */
	public function user_changed_domain( $cname = '' ) {

		$transient = Plugin::get_persistent_site_transient( 'gd_system_domain_changed' );

		if ( false !== $transient ) {

			return (
				1 === (int) $transient
				||
				'Y' === $transient // Back compat
			);

		}

		$cname    = ( $cname ) ? $cname : Plugin::domain();

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );

		$response = $this->call( 'domains/' . $cname );
		$body     = json_decode( wp_remote_retrieve_body( $response ), true );
		$changed  = ! empty( $body['domainChanged'] ) ? 1 : 0;
		$timeout  = 300;

		Plugin::set_persistent_site_transient( 'gd_system_domain_changed', $changed, absint( $timeout ) );

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );

		return ( 1 === $changed );

	}

	/**
	 * Retreive WooCommerce product
	 *
	 * @param string $product_type The type of product to achieve.
	 *
	 * @return array $products The retreived products from the WooCommerce API.
	 */
	public function get_woocommerce_products( $product_type ) {

		$product_cache = get_transient( 'wpaas_woocommerce_' . $product_type );

		if ( ! WP_DEBUG && false !== $product_cache ) {

			return $product_cache;

		}

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );

		$request = $this->call( $this->wp_public_api_accountuid( "automattic/%s/woocommerce/info" ) );

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );

		if ( 200 !== wp_remote_retrieve_response_code( $request ) || is_wp_error( $request ) ) {

			set_transient( 'wpaas_woocommerce_' . $product_type, [], 15 * MINUTE_IN_SECONDS );

			return [];

		}

		$products = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( empty( $products ) || ! is_array( $products ) || ! isset( $products['products'] ) ) {

			set_transient( 'wpaas_woocommerce_' . $product_type, [], 15 * MINUTE_IN_SECONDS );

			return [];

		}

		$type = 'extensions' === $product_type ? 'plugin' : 'theme';

		// Return the proper product type only.
		$products = array_filter(
			$products['products'],
			function( $extension ) use ( $type ) { return $type === $extension['type']; }
		);

		set_transient( 'wpaas_woocommerce_' . $product_type, $products, 8 * HOUR_IN_SECONDS );

		return $products;

	}

	/**
	 * Request that the API refresh its copy of the blog title.
	 *
	 * @param string $blogname (optional)
	 *
	 * @return void
	 */
	public function refresh_blog_title( $blogname = null ) {

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$method_args = $blogname ? [ 'blogname' => htmlspecialchars_decode( $blogname, ENT_QUOTES ) ] : [];

		$this->call( $this->wp_public_api_accountuid( "v3-proxy/%s/refreshBlogTitle" ), $method_args, 'POST' );

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

	}

	/**
	 * Request that the API refresh wether or not the site is nextgen compatible.
	 *
	 * @param boolean $is_nextgen_compat (optional)
	 *
	 * @return void
	 */
	public function refresh_nextgen_compatibility( $is_nextgen_compat = false ) {

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$method_args = [ 'nextgenEnabled' => (bool) $is_nextgen_compat ];

		$this->call( $this->wp_public_api_accountuid( "v3-proxy/%s/nextgen" ), $method_args, 'POST' );

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

	}

	/**
	 * Send RAD data to the API.
	 *
	 * @param string $name
	 * @param array  $metadata (optional)
	 *
	 * @return void
	 */
	public function log_rad_event( $name, $metadata = [] ) {

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$method_args = [
			'datetime' => current_time( 'mysql', 1 ),
			'metadata' => (array) $metadata,
			'name'     => $name,
		];


		$this->call( $this->wp_public_api_accountuid( "rad/%s/event" ), $method_args, 'POST' );

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

	}

	/**
	 * Toggle RUM for this site.
	 *
	 * @param bool $enabled True or false
	 *
	 * @return void
	 */
	public function toggle_rum( $enabled = true ) {

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$method_args = [
			'rumEnabled' => (bool) $enabled,
		];

		$this->call( $this->wp_public_api_accountuid( "v3-proxy/%s/rum" ), $method_args, 'POST' );

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'non_blocking_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

	}

	/**
	 * Flush full page cdn
	 *
	 *
	 * @return string|null
	 */
	public function flush_cdn() {

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		$accountUid = defined( 'GD_ACCOUNT_UID' ) ? GD_ACCOUNT_UID : '';
		try {
			$response = $this->call('cdn/cache/' . $accountUid, [], 'DELETE');
			$body = json_decode(wp_remote_retrieve_body($response), true);
			$invalidationId = !empty($body['invalidationId']) ? $body['invalidationId'] : null;
		} catch (WP_Error $e) {
			return null;
		}

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );

		return $invalidationId;
	}


	/**
	 * Flush full page cdn
	 *
	 *
	 * @return void
	 */
	public function send_automatic_plugin_update_metadata( $params ) {
		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		try {
			$this->call('event/pluginUpdate', $params, 'POST');
		} catch (WP_Error $e) {
			return null;
		}

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );
	}

	/**
	 * Get CDN cache clear status
	 *
	 * @returns string|null
	 */
	public function flush_cache_cdn_status( $invalidation_id ) {
		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );

		try {
			$accountUid = defined( 'GD_ACCOUNT_UID' ) ? GD_ACCOUNT_UID : '';
			$response = $this->call('cdn/' . $accountUid . '/cache/' . $invalidation_id);
			$body = json_decode(wp_remote_retrieve_body($response), true);
		} catch (WP_Error $e) {
			return null;
		}

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );

		return $body;
	}

    /**
     * Get feature flags
     *
     * @param string $api_url
     *
     * @returns array|null
     */
    public function fetch_feature_flag( $api_url ) {
        add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
        add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
        add_filter( 'wpaas_api_http_args', [ $this, 'timeout_3s_http_args' ] );

        /** Logg user action */
        $GLOBALS['wpaas_activity_logger']->log_sp_action(get_current_user_id(), 'Fetch feature flags from API' );

        try {
            $response = $this->call($api_url);
            $body = json_decode(wp_remote_retrieve_body($response), true);
        } catch (WP_Error $e) {
            return null;
        }

        remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
        remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
        remove_filter( 'wpaas_api_http_args', [ $this, 'timeout_3s_http_args' ] );

        return $body;
    }

	/**
	 * Filter the API URL when using the V3 API.
	 *
	 * @param string $api_url
	 *
	 * @return string
	 */
	public function v3_api_url( $api_url ) {

		if ( ! defined( 'GD_ACCOUNT_UID' ) || ! GD_ACCOUNT_UID ) {

			return;

		}

		$env    = Plugin::get_env();
		$prefix = ( 'prod' === $env ) ? '' : "{$env}-";

		return sprintf(
			"https://mwp.api.phx3.{$prefix}godaddy.com/api/v1/mwp/sites/%s/",
			GD_ACCOUNT_UID
		);

	}

	/**
	 * Filter the API URL when using the WP Public API.
	 *
	 * @return string
	 */
	public function wp_public_api_url()
	{
		$env = Plugin::get_env();
		if (in_array($env, array('myh.test'))) {
			$env = 'test';
		}

		if (in_array($env, ['dev', 'test'], true)) {
			return "https://wp-api.wpsecurity.{$env}-godaddy.com/api/v1/";
		}

        return "https://wp-api.wpsecurity.godaddy.com/api/v1/";
	}

	/**
	 * Filter the HTTP request args when using the V3 API.
	 *
	 * @param array $http_args
	 *
	 * @return array
	 */
	public function v3_api_http_args( $http_args ) {

		$body = apply_filters( 'wpaas_v3_api_body_signage', [] );

		$http_args['headers'] = array_merge( $http_args['headers'], Plugin::sign_http_request( wp_json_encode( $body ) ) );

		return $http_args;

	}


	/**
	 * Filter the HTTP request args to use a 30s timeout.
	 *
	 * @param array $http_args
	 *
	 * @return array
	 */
	public function timeout_30s_http_args( $http_args ) {

		$http_args['timeout'] = 30;

		return $http_args;

	}

    /**
     * Filter the HTTP request args to use a 3s timeout.
     *
     * @param array $http_args
     *
     * @return array
     */
    public function timeout_3s_http_args( $http_args ) {

        $http_args['timeout'] = 3;

        return $http_args;

    }

	/**
	 * Filter the HTTP request args to make calls non-blocking.
	 *
	 * @param array $http_args
	 *
	 * @return array
	 */
	public function non_blocking_http_args( $http_args ) {

		$http_args['blocking'] = false;
		$http_args['timeout']  = 5;

		return $http_args;

	}

	public function backup_now() {
		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$accountUid = defined( 'GD_ACCOUNT_UID' ) ? GD_ACCOUNT_UID : '';

		try {
			$response = $this->call('backup/' . $accountUid . '/now',[] , 'POST');
			$body = json_decode(wp_remote_retrieve_body($response), true);
			$success = !empty($body['success']) ? $body['success'] : null;
		} catch (WP_Error $e) {
			return null;
		}

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );
		return $success;
	}
	/**
	 * Make an API call.
	 *
	 * @param  string        $method
	 * @param  array|string  $method_args (optional)
	 * @param  string        $http_verb   (optional)
	 *
	 * @return array|WP_Error
	 */
	private function call( $method, $method_args = [], $http_verb = 'GET' ) {

		$api_url = (string) apply_filters( 'wpaas_api_url', '' );

		if ( ! $api_url ) {
            /** Logg user action */
            $GLOBALS['wpaas_activity_logger']->log_sp_action(get_current_user_id(), sprintf('API call fail URL: %s', $api_url) );

			return new WP_Error( 'wpaas_api_url_not_found' );

		}

		add_filter('wpaas_v3_api_body_signage',function() use ($method_args) {
			return $method_args;
		});

		$http_args = (array) apply_filters(
			'wpaas_api_http_args',
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
			]
		);

		$url = trailingslashit( $api_url ) . $method;

		$retries     = 0;
		$max_retries = 1;

		add_filter( 'https_ssl_verify', '__return_false' );

		while ( $retries <= $max_retries ) {

			$retries++;

			switch ( $http_verb ) {

				case 'GET':
					if ( ! empty( $method_args ) ) {

						$url .= '?' . build_query( $method_args );

					}

					$response = wp_remote_get( $url, $http_args );

					break;

				case 'POST':
					$http_args['body'] = (bool) apply_filters( 'wpaas_api_http_post_body_json', false ) ? json_encode( $method_args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : $method_args;

					$response = wp_remote_post( $url, $http_args );

					break;
				case "DELETE":
					$http_args['body'] = (bool) apply_filters( 'wpaas_api_http_post_body_json', false ) ? json_encode( $method_args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : $method_args;
					$response = wp_remote_request($url, array_merge($http_args, [
						'method' => 'DELETE'
					]));
					break;

				default:
					return new WP_Error( 'wpaas_api_invalid_http_verb' );

			}

			$response_code = wp_remote_retrieve_response_code( $response );

			// Check if we aren't on the last iteration and we can try the request again
			if (
				$retries <= $max_retries
				&&
				$this->is_retryable( $response, $response_code )
			) {

				// Give some time for the API to recover
				sleep( (int) apply_filters( 'wpaas_api_retry_delay', 1 ) );

				continue;

			}

			break;

		}

		remove_filter( 'https_ssl_verify', '__return_false' );


		if ( 200 !== $response_code  && 204 !== $response_code ) {
            /** Logg user action */
            $GLOBALS['wpaas_activity_logger']->log_sp_action(get_current_user_id(),
	            sprintf('API call fail URL: %s status_code: %s', $url, $response_code)
            );


			return new WP_Error( 'wpaas_api_bad_status', $response_code );

		}

        /** Logg user action */
        $GLOBALS['wpaas_activity_logger']->log_sp_action(get_current_user_id(),
	        sprintf('API call success URL: %s status_code: %s', $url, $response_code)
        );

		return $response;

	}

	/**
	 * Check if a response is an error and retryable.
	 *
	 * @param  array|WP_Error $response
	 * @param  int   $response_code
	 *
	 * @return bool
	 */
	private function is_retryable( $response, $response_code ) {

		if ( 200 === $response_code ) {

			return false;

		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if (
			isset( $body['status'], $body['type'], $body['code'] )
			&&
			503 === absint( $body['status'] )
			&&
			'error' === $body['type']
			&&
			'RetryRequest' === $body['code']
		) {

			return true;

		}

		return false;

	}

	public function get_failed_smart_updates() {

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );

		$response = $this->call( 'smart-updates');

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );

		return $body;
	}

	public function smart_update_notice_dismiss( $dismiss_id ) {

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$response = $this->call(  'smart-updates/dismiss', [ 'dismissId' => $dismiss_id ], 'POST' );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		return true;
	}

	public function hmt_register($key) {

		add_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		add_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		add_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		$accountUid = defined( 'GD_ACCOUNT_UID' ) ? GD_ACCOUNT_UID : '';

		$data = [
			'mwp_potential_key' => $key
		];
		try {
			$response = $this->call('backup/' . $accountUid . '/register', $data , 'POST');
			$body = json_decode(wp_remote_retrieve_body($response), true);
			$success = !empty($body['success']) ? $body['success'] : null;
		} catch (WP_Error $e) {
			return null;
		}

		remove_filter( 'wpaas_api_url', [ $this, 'wp_public_api_url' ] );
		remove_filter( 'wpaas_api_http_args', [ $this, 'v3_api_http_args' ] );
		remove_filter( 'wpaas_api_http_post_body_json', '__return_true' );

		return $success;
	}

	private function wp_public_api_accountuid ( $path ) {

		$accountUid = defined( 'GD_ACCOUNT_UID' ) ? GD_ACCOUNT_UID : '';

		return sprintf(
			$path,
			$accountUid
		);

	}
}
