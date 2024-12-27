<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

/**
 * @deprecated Use Cache_V2
 */
final class Cache {
	/**
	 * Required user capability.
	 *
	 * @var string
	 */
	public static $cap = 'activate_plugins';

	/**
	 * @deprecated This method will be removed in future versions
	 */
	public function do_ban() {
		trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

		$GLOBALS['wpaas_cache_class']->do_ban();
	}

	/**
	 * @deprecated This method will be removed in future versions
	 */
	public function do_purge( $id, $post = null ) {
		trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

		$GLOBALS['wpaas_cache_class']->do_purge();
	}

	/**
	 * @deprecated This method will be removed in future versions
	 */
	public static function has_ban() {
		trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

		return $GLOBALS['wpaas_cache_class']->has_ban();
	}

	/**
	 * @deprecated This method will be removed in future versions
	 */
	public static function has_purge() {
		trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

		return $GLOBALS['wpaas_cache_class']->has_purge();
	}

	/**
	 * @deprecated This method will be removed in future versions
	 */
	public static function ban() {
		trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

		return $GLOBALS['wpaas_cache_class']->ban();

	}

	/**
	 * @deprecated This method will be removed in future versions
	 */
	public static function purge( $urls = [] ) {
		trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);

		return $GLOBALS['wpaas_cache_class']->purge();
	}


}
