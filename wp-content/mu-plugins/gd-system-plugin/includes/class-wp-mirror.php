<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class WP_Mirror {

	const WP_API_REGEX_URL = '/http(s)?:\/\/api\.wordpress\.org/';
	const WP_DOWNLOADS_REGEX_URL = '/http(s)?:\/\/downloads\.wordpress\.org/';

	const WP_API_REGEX_HOST = '/api\.wordpress\.org/';
	const WP_DOWNLOADS_REGEX_HOST = '/downloads\.wordpress\.org/';

	const WP_MIRROR_FF = 'wpm_enabled';

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( ! $this->should_load_wp_mirror() ) {
			return;
		}

		add_filter( 'http_request_host_is_external', [ $this, 'allow_wp_mirror' ], 10, 2 );

		add_action( 'http_api_curl', [ $this, 'http_api_curl_rewrite_wp_mirror' ], 1000, 3 );

	}

	/**
	 * Checks if requested hosts matches WP mirror if so it returns true and allow external hosts.
	 *
	 * @param $allow
	 * @param $host
	 *
	 * @return bool|mixed
	 */
	public function allow_wp_mirror( $allow, $host ) {
		if ( preg_match( WP_Mirror::WP_API_REGEX_HOST, $host ) ||
		     preg_match( WP_Mirror::WP_DOWNLOADS_REGEX_HOST, $host )
		) {
			return true;
		}

		return $allow;
	}

	/**
	 * Rewrites curl_setopt by changing api.wordpress.org and downloads.wordpress.org with wp mirror URLs.
	 *
	 * @param $handle
	 * @param $parsed_args
	 * @param $url
	 *
	 * @return void
	 */
	public function http_api_curl_rewrite_wp_mirror( $handle, $parsed_args, $url ) {

		if ( preg_match( WP_Mirror::WP_API_REGEX_URL, $url ) ) {
			$api_url_replaced = preg_replace( WP_Mirror::WP_API_REGEX_URL, $this->get_wp_mirror_api_url(), $url );
			curl_setopt( $handle, CURLOPT_URL, $api_url_replaced );

		} else if ( preg_match( WP_Mirror::WP_DOWNLOADS_REGEX_URL, $url ) ) {
			$download_url_replaced = preg_replace( WP_Mirror::WP_DOWNLOADS_REGEX_URL, $this->get_wp_mirror_download_url(), $url );
			curl_setopt( $handle, CURLOPT_URL, $download_url_replaced );
		}

	}

	/**
	 * Return WP mirror API url based on env.
	 *
	 * @return string
	 */
	public function get_wp_mirror_api_url() {
		$env = Plugin::get_env();
		if ( in_array( $env, array( 'myh.test' ) ) ) {
			$env = 'test';
		}

		if ( in_array( $env, [ 'dev', 'test' ], true ) ) {
			return "https://api.celestias.xyz";
		}

		return "https://api.tw2.org";
	}

	/**
	 * Return WP mirror downloads url based on env.
	 *
	 * @return string
	 */
	public function get_wp_mirror_download_url() {
		$env = Plugin::get_env();
		if ( in_array( $env, array( 'myh.test' ) ) ) {
			$env = 'test';
		}

		if ( in_array( $env, [ 'dev', 'test' ], true ) ) {
			return "https://downloads.celestias.xyz";
		}

		return "https://downloads.tw2.org";
	}

	/**
	 * Decides should boot WP mirror based on FeatureFlag and WPM_DISABLED.
	 *
	 * @return bool
	 */
	public function should_load_wp_mirror() {

		return ! ( defined( 'WPM_DISABLED' ) && WPM_DISABLED ) && $GLOBALS['wpaas_feature_flag']->get_feature_flag_value( WP_Mirror::WP_MIRROR_FF, false );
	}

}