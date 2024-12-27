<?php

namespace WPaaS\CLI;

use \WP_CLI;
use \WP_CLI_Command;
use \WPaaS\API;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class HMT extends WP_CLI_Command {

	/**
	 * Register HMT backups.
	 *
	 *
	 */
	public function register( array $args, array $assoc_args ) {

		if ( ! function_exists( 'mwp_get_potential_key' ) ) {


			WP_CLI::error( 'The function mwp_get_potential_key does not exist.', false );

			return;
		}
		$potential_key = mwp_get_potential_key();

		$api    = new API();
		$result = $api->hmt_register( $potential_key );

		if ( empty( $result ) ) {
			WP_CLI::error( 'Failed to register HMT backups.', false );
		} else {
			WP_CLI::success( 'HMT backups registered successfully.', false );
		}

	}


}
