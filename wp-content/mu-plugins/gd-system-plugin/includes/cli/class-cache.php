<?php

namespace WPaaS\CLI;

use \WP_CLI;
use \WP_CLI_Command;
use \WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Cache extends WP_CLI_Command {

	/**
	 * Flush the cache.
	 *
	 * ## OPTIONS
	 *
	 * [<type>...]
	 * : One or more cache types to flush. Accepted values: varnish, object,
	 * transient, cdn. All cache types will be flushed by default.
	 *

	 */
	public function flush( array $args, array $assoc_args ) {

		$allowed = [ 'transient', 'object', 'varnish', 'cdn' ];

		foreach ( array_diff( $args, $allowed ) as $type ) {

			WP_CLI::error( sprintf( '`%s` is not a supported cache type.', $type ), false );

		}

		$args = empty( $args ) ? $allowed : $args;

		foreach ( $allowed as $type ) {

			// Making sure we flush in the right sequence
			if ( ! in_array( $type, $args, true ) ) {

				continue;

			}

			switch ( $type ) {

				case 'transient':
					$transient = $this->flush_transient_cache( $assoc_args );
					break;
				case 'object':
					$object = $this->flush_object_cache();
					break;
				case 'varnish':
					$varnish = $this->flush_varnish_cache();
					break;
				case 'cdn':
					$cdn = $this->flush_cdn_cache();
					break;

			}
		}

		if ( ! empty( $varnish ) || ! empty( $object ) || ! empty( $transient ) || ! empty( $cdn ) ) {
			update_option( 'gd_system_last_cache_flush', time() );
		}

	}

	/**
	 * Date of the last cache flush.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : PHP date format. Default: c
	 *
	 * @alias      last-flushed
	 *
	 * @subcommand last-flush
	 */
	public function last_flush( array $args, array $assoc_args ) {

		$format = WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'c' );
		$date   = Plugin::last_cache_flush_date( $format );

		if ( ! $date ) {

			WP_CLI::warning( 'The last cache flush date is unknown.' );

			return;

		}

		WP_CLI::line( Plugin::last_cache_flush_date( $format ) );

	}

	/**
	 * Flush the Varnish/Nginx cache.
	 *
	 *
	 * @return bool
	 */
	private function flush_varnish_cache() {

		$GLOBALS['wpaas_cache_class']->do_ban();

		WP_CLI::success( 'The Varnish page cache was flushed.' );

		return true;
	}

	/**
	 * Flush the object cache.
	 *
	 * @return bool
	 */
	private function flush_object_cache() {

		wp_cache_flush();

		WP_CLI::success( 'The object cache was flushed.' );

		return true;
	}

	/**
	 * Flush the transient cache.
	 *
	 * @param array $assoc_args (optional)
	 *
	 * @return bool
	 */
	private function flush_transient_cache( array $assoc_args = [] ) {

		$result = $GLOBALS['wpaas_cache_class']->flush_transients();

		if ( false === $result ) {

			WP_CLI::error( 'The transient cache could not be flushed.', false );

			return false;

		}

		WP_CLI::success( sprintf( '%d transients deleted from the database.', $result ) );

		return true;

	}

	/**
	 * Flush the CDN cache.
	 *
	 * @return bool
	 */
	private function flush_cdn_cache() {

		$GLOBALS['wpaas_cache_class']->flush_cdn();

		WP_CLI::success( 'The cdn cache was flushed.' );

		return true;

	}

}
