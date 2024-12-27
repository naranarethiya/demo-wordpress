<?php

namespace WPaaS\Cache;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

interface Cache_Manager {

	/**
	 * Purge cache on specific URL
	 *
	 * @param string $url
	 *
	 * @return void
	 */
	public function purge( $url = null );

	/**
	 * Flush entire cache
	 *
	 * @return void
	 */
	public function ban();


	/**
	 * Check if Full Page Cache is enabled
	 *
	 * @return bool
	 */
	public function is_full_page_cache_enabled();
}