<?php

namespace Wpsec\captcha\utils;

use Wpsec\captcha\constants\LoggingConstants;

class Logger {

	/**
	 * Logs message into captcha plugin log.
	 *
	 * @param string $message
	 * @param array $data
	 *
	 * @return void
	 */
	public static function log( $message, $data = array() ) {

		try {
			if ( ! self::should_log() ) {
				return;
			}

			if ( is_array( $data ) || is_object( $data ) ) {
				$data = wp_json_encode( $data );

				if ( empty( $data ) ) {
					return;
				}
			}

			$log_filename = WP_CONTENT_DIR . '/captcha-plugin-log';

			if ( ! file_exists( $log_filename ) ) {
				mkdir( $log_filename, 0755, true );
			}

			$log_file_data = $log_filename . '/log_' . gmdate( 'Y-m-d' ) . '.log';

			$message_to_log = '[' . gmdate( 'Y-m-d H:i:s' ) . '] - ' . $message . ' - ' . $data;

			file_put_contents( $log_file_data, $message_to_log . "\n", FILE_APPEND );
		} catch ( \Throwable $e ) {
			// catch error silently
		}

	}

	/**
	 * Is logging for captcha plugin enabled.
	 *
	 * @return bool
	 */
	private static function should_log() {
		return get_option( LoggingConstants::CAPTCHA_LOG_ENABLED, 'disabled' ) === 'enabled';
	}
}
