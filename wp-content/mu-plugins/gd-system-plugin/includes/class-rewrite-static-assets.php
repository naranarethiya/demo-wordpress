<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}
final class Rewrite_Static_Assets {


	public function __construct() {

		if ( $GLOBALS['wpaas_feature_flag']->get_feature_flag_value('cdn_rewrite', false) ) {
			self::schedule_rewrite_static_assets_url();
		} else if ( wp_next_scheduled( 'wpaas_rewrite_static_assets', array() ) ) {
			wp_clear_scheduled_hook('wpaas_rewrite_static_assets');

		}
	}

	public function schedule_rewrite_static_assets_url() {

		add_action( 'wpaas_rewrite_static_assets', array( $this, 'should_rewrite_static_assets_url' ), 10, 0 );

		if ( ! wp_next_scheduled( 'wpaas_rewrite_static_assets', array() ) ) {
			wp_schedule_event( time() + rand( 0, 86400 ), 'daily', 'wpaas_rewrite_static_assets', array() );
		}
	}

	public function rewrite_static_assets_url( $base_url ) {

		global $wpdb;

		$site_url = get_site_url();

		$search_replacer = new Search_Replacer( $base_url, $site_url, true );

		$search_replacer->php_handle_col( 'post_content', $wpdb->prefix . 'posts', $base_url, $site_url );

		$search_replacer->php_handle_col( 'option_value', $wpdb->prefix . 'options', $base_url, $site_url );

		$search_replacer->php_handle_col( 'meta_value', $wpdb->prefix . 'postmeta', $base_url, $site_url );

	}


	public function should_rewrite_static_assets_url() {

		$base_url = CDN::get_cdn_base();

		if ( empty( $base_url ) ) {
			return;
		}

		if ( defined( 'GD_CDN_FULLPAGE' ) ) {

			if ( true === GD_CDN_FULLPAGE && ! get_option( 'wpaas_cdn_fullpage', true ) ) {
				delete_option( 'wpaas_cdn_fullpage' );
				delete_option( 'wpaas_cdn_enabled' );

				$this->rewrite_static_assets_url( $base_url );

			} elseif ( false === GD_CDN_FULLPAGE ) {
				add_option( 'wpaas_cdn_fullpage', false );
			}
		}

		if ( defined( 'GD_CDN_ENABLED' ) ) {

			if ( false === GD_CDN_ENABLED && get_option( 'wpaas_cdn_enabled', false ) ) {
				delete_option( 'wpaas_cdn_enabled' );
				$this->rewrite_static_assets_url( $base_url );

			} elseif ( true === GD_CDN_ENABLED ) {
				update_option( 'wpaas_cdn_enabled', true );
			}
		}
	}

}
