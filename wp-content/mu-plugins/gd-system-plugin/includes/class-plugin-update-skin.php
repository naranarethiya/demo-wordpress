<?php

namespace WPaaS;

use WP_Upgrader_Skin;

final class Plugin_Update_Skin extends WP_Upgrader_Skin {

	/**
	 * Holds the plugin slug in the Plugin Directory.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	public $plugin = '';

	/**
	 * Whether the plugin is active.
	 *
	 * @since 2.8.0
	 *
	 * @var bool
	 */
	public $plugin_active = false;

	/**
	 * Whether the plugin is active for the entire network.
	 *
	 * @since 2.8.0
	 *
	 * @var bool
	 */
	public $plugin_network_active = false;

	/**
	 * Constructor.
	 *
	 * Sets up the plugin upgrader skin.
	 *
	 * @since 2.8.0
	 *
	 * @param array $args Optional. The plugin upgrader skin arguments to
	 *                    override default options. Default empty array.
	 */
	public function __construct( $args = array() ) {
		$defaults = array(
			'url'    => '',
			'plugin' => '',
			'nonce'  => ''
		);
		$args     = wp_parse_args( $args, $defaults );

		$this->plugin = $args['plugin'];

		$this->plugin_active         = is_plugin_active( $this->plugin );
		$this->plugin_network_active = is_plugin_active_for_network( $this->plugin );

		parent::__construct( $args );
	}

	public function feedback($feedback, ...$args) {
		// Leave this empty since we don't want html output
	}

	public function header()
	{
		// Leave this empty since we don't want html output
	}

	public function footer()
	{
		// Leave this empty since we don't want html output
	}
}
