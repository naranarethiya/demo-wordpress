<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Configs {

	use Helpers;

	const INFO_FILE = '/info.json';
	/**
	 * Array of config data.
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Object of v2 data.
	 *
	 * @var object
	 */
	private $v2Data;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$path = $this->find_readable_path(
			[
				ABSPATH . 'gd-config.php',
				ABSPATH . '../local/rendered/gd-config.php',
				WPMU_PLUGIN_DIR . '/bin/gd-config.php',
			]
		);

		if ( $path ) {

			require_once $path;

		}
		$this->load_info_json();

	}

	/**
	 * Return the v2 data.
	 *
	 * @return object
	 */
	public function get_v2_data() {
		return $this->v2Data;
	}

	public function load_info_json() {

		if ( ! self::is_wpaas_v2() ) {
			$this->v2Data = new \stdClass;

			return;
		}
		if ( ! file_exists( self::INFO_FILE ) ) {
			error_log( "Couldn't find an info.json file for the domain, ABSPATH: " . ABSPATH . ", __DIR__: " . __DIR__ );
			$this->v2Data = new \stdClass;

			return;
		}
		$this->v2Data = (object) \json_decode( file_get_contents( self::INFO_FILE ) );
	}


	/**
	 * Return the first readable path from an array.
	 *
	 * @param array $paths
	 *
	 * @return string|false
	 */
	private function find_readable_path( array $paths ) {

		foreach ( $paths as $path ) {

			if ( is_readable( $path ) ) {

				return $path;

			}

		}

		return false;

	}
}
