<?php

namespace WPaaS\Admin;

use WPaaS\Auto_Updates;
use \WPaaS\Cache_V2;
use \WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Bar {

	/**
	 * Admin bar object.
	 *
	 * @var WP_Admin_Bar
	 */
	private $admin_bar;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'init' ] );

		/**
		 * Initialize NextGen filters.
		 */
		add_filter( 'nextgen_admin_links', [ $this, 'filter_site_link' ], 10, 1 );

	}

	/**
	 * Filter the admin links used for NextGen Logo-Menu.
	 *
	 * @param array $links associative array of name => 'link'.
	 *
	 * @return array
	 */
	public function filter_site_link( $links ) {

		return array_merge(
			$links,
			array(
				'overview'     => esc_url( Plugin::account_url( 'overview' ) ),
				'settings'     => esc_url( Plugin::account_url( 'settings' ) ),
				'changeDomain' => esc_url( Plugin::account_url( 'changedomain' ) ),
				'flush'        => current_user_can( Cache_V2::$cap ) ? Cache_V2::get_flush_url() : '',
			)
		);

	}

	/**
	 * Initialize the script.
	 *
	 * @action init
	 */
	public function init() {

		/**
		 * Filter the user cap required to view the admin bar menu.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		$cap = (string) apply_filters( 'wpaas_admin_bar_cap', 'activate_plugins' );

		if ( ! current_user_can( $cap ) ) {

			return;

		}

		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], PHP_INT_MAX );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

	}

	/**
	 * Admin bar menu.
	 *
	 * @action admin_bar_menu
	 *
	 * @param \WP_Admin_Bar $admin_bar
	 */
	public function admin_bar_menu( \WP_Admin_Bar $admin_bar ) {

		$this->admin_bar = $admin_bar;

		$menus = [
			'gd' => 'gd_menu',
			'mt' => 'mt_menu',
		];

		$menu = Plugin::use_brand_value( $menus, 'reseller_menu' );

		if ( is_callable( [ $this, $menu ] ) ) {

			$this->$menu();

		}

	}

	/**
	 * Enqueue styles.
	 *
	 * @action admin_enqueue_scripts
	 * @action wp_enqueue_scripts
	 */
	public function enqueue_scripts() {

		$rtl    = ! is_rtl() ? '' : '-rtl';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wpaas-admin-bar', Plugin::assets_url( "css/admin-bar{$rtl}{$suffix}.css" ), [], Plugin::version() );

	}

	/**
	 * Return the subdomain to use for the help docs
	 *
	 * @return string Help documents subdomain
	 */
	private function get_help_docs_url() {

		$language  = get_option( 'WPLANG', 'www' );
		$parts     = explode( '_', $language );
		$subdomain = ! empty( $parts[1] ) ? strtolower( $parts[1] ) : strtolower( $language );

		// Overrides
		switch ( $subdomain ) {

			case '':
				$subdomain = 'www'; // Default
				break;

			case 'uk':
				$subdomain = 'ua'; // Ukrainian (Українська)
				break;

			case 'el':
				$subdomain = 'gr'; // Greek (Ελληνικά)
				break;

			case 'gb':
				$subdomain = 'uk'; // United Kingdom
				break;

		}

		/**
		 * Filter the help documentation URL.
		 *
		 * @since 3.11.2
		 *
		 * @var string
		 */
		$url = (string) apply_filters( 'wpaas_help_docs_url', "https://{$subdomain}.godaddy.com/help/wordpress-1000047", $subdomain, $language );

		return esc_url( $url );

	}

	/**
	 * GoDaddy admin menu.
	 */
	private function gd_menu() {

		$this->top_level_menu_item( __( 'GoDaddy Quick Links', 'gd-system-plugin' ), 'gd' );
		$this->hosting_overview_menu_item();
		$this->hosting_settings_menu_item();
		$this->help_and_support_menu_item();
		$this->flush_cache_menu_item();
		$this->enable_auto_updates_menu_item();
		$this->pro_connection_key_menu_item();

		global $submenu;

		if ( empty( $submenu['godaddy'] ) ) {

			return;

		}

		foreach ( $submenu['godaddy'] as $item ) {

			parse_str( $item[2], $var );

			$this->admin_bar->add_menu(
				[
					'parent' => 'wpaas',
					'id'     => 'wpaas-' . sanitize_title( ! empty( $var['tab'] ) ? $var['tab'] : $item[2] ),
					'href'   => $item[2],
					'title'  => $item[0],
				]
			);

		}

	}

	/**
	 * Media Temple admin menu.
	 */
	private function mt_menu() {

		$this->top_level_menu_item( __( 'Managed WordPress', 'gd-system-plugin' ), 'mt' );
		$this->hosting_settings_menu_item();
		$this->flush_cache_menu_item();

	}

	/**
	 * Reseller admin menu.
	 */
	private function reseller_menu() {

		$this->top_level_menu_item( __( 'Managed WordPress', 'gd-system-plugin' ), 'reseller' );
		$this->hosting_overview_menu_item();
		$this->hosting_settings_menu_item();
		$this->flush_cache_menu_item();
		$this->pro_connection_key_menu_item();

	}

	/**
	 * Top-level menu item.
	 *
	 * @param string $label
	 * @param string $icon
	 */
	private function top_level_menu_item( $label, $brand ) {

		switch ( $brand ) {
			default:
			case 'reseller':
			case 'mt':
				$icon = '<span class="ab-icon dashicons dashicons-admin-generic"></span><span class="ab-label">%s</span>';
				break;

			case 'gd':
				$icon = '<span class="ab-icon svg gd-wpaas" style="background-image: url(data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjciIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNyAyNCIgZmlsbD0iI2E3YWFhZCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTIzLjI4OTggMS4wOTU3OUMyMC40ODM5IC0wLjY1MDk2MSAxNi43ODk4IC0wLjIzODU1MSAxMy40OTUyIDEuODI5MjFDMTAuMjEwOSAtMC4yMzc0MDggNi41MTQ1OCAtMC42NTA5NjEgMy43MTIwMyAxLjA5NTc5Qy0wLjcyMTI0NSAzLjg2MTU3IC0xLjI2MDI0IDEwLjk4NTYgMi41MDkzIDE3LjAwNjJDNS4yODc4MiAyMS40NDY3IDkuNjMyOTggMjQuMDQ2OCAxMy41MDA5IDIzLjk5ODlDMTcuMzY4OSAyNC4wNDY4IDIxLjcxNDEgMjEuNDQ2NyAyNC40OTI2IDE3LjAwNjJDMjguMjU3NSAxMC45ODU2IDI3LjcyMzEgMy44NjE1NyAyMy4yODk4IDEuMDk1NzlaTTQuNTQ3NDIgMTUuNzM1OEMzLjc2OTk0IDE0LjUwNTQgMy4xODM0OCAxMy4xNjQ3IDIuODA3OTggMTEuNzU5MUMyLjQ3NDEgMTAuNTM5MyAyLjM0NjQ3IDkuMjcyNSAyLjQzMDM0IDguMDEwODFDMi41OTg1NiA1Ljc4NjUzIDMuNTA2MDQgNC4wNTM0OSA0Ljk4NDU3IDMuMTMwNDJDNi40NjMwOSAyLjIwNzM1IDguNDE3NjYgMi4xNTM2NiAxMC40OTkzIDIuOTc3MzRDMTAuODE1NyAzLjEwNDM3IDExLjEyNTQgMy4yNDczNiAxMS40MjczIDMuNDA1NzRDMTAuMjcwMyA0LjQ1OCA5LjI2NDQ4IDUuNjY0NzQgOC40MzgyNiA2Ljk5MTc3QzYuMTQ5NTMgMTAuNjQ3NSA1LjQ1MTQ3IDE0LjcxNjggNi4yNTAyMyAxNy45NTg5QzUuNjEzOTEgMTcuMjcyOSA1LjA0Mzc4IDE2LjUyODYgNC41NDc0MiAxNS43MzU4Wk0yNC4xOTUgMTEuNzU5MUMyMy44MTkgMTMuMTY0NSAyMy4yMzI2IDE0LjUwNTIgMjIuNDU1NiAxNS43MzU4QzIxLjk1OTQgMTYuNTMwMiAyMS4zODkzIDE3LjI3NiAyMC43NTI4IDE3Ljk2MzVDMjEuNDY2OSAxNS4wNTYxIDIwLjk4MTcgMTEuNDk1MiAxOS4yMzA4IDguMTU0NzVDMTkuMjAzMSA4LjA5OTUxIDE5LjE2NCA4LjA1MDcyIDE5LjExNjEgOC4wMTE1N0MxOS4wNjgyIDcuOTcyNDEgMTkuMDEyNiA3Ljk0Mzc2IDE4Ljk1MjkgNy45Mjc0OEMxOC44OTMyIDcuOTExMjEgMTguODMwOCA3LjkwNzY3IDE4Ljc2OTYgNy45MTcxMUMxOC43MDg0IDcuOTI2NTQgMTguNjQ5OSA3Ljk0ODc0IDE4LjU5NzkgNy45ODIyNUwxMy4xMzkzIDExLjM4NzhDMTMuMDg5NiAxMS40MTg3IDEzLjA0NjQgMTEuNDU5MSAxMy4wMTIzIDExLjUwNjdDMTIuOTc4MyAxMS41NTQzIDEyLjk1NCAxMS42MDgxIDEyLjk0MDggMTEuNjY1MUMxMi45Mjc2IDExLjcyMjEgMTIuOTI1OCAxMS43ODExIDEyLjkzNTUgMTEuODM4OEMxMi45NDUyIDExLjg5NjUgMTIuOTY2MyAxMS45NTE3IDEyLjk5NzQgMTIuMDAxMkwxMy43OTg1IDEzLjI3OTZDMTMuODI5NCAxMy4zMjkzIDEzLjg2OTkgMTMuMzcyMyAxMy45MTc2IDEzLjQwNjRDMTMuOTY1MiAxMy40NDA0IDE0LjAxOTIgMTMuNDY0NyAxNC4wNzYzIDEzLjQ3NzhDMTQuMTMzNCAxMy40OTEgMTQuMTkyNSAxMy40OTI4IDE0LjI1MDMgMTMuNDgzMUMxNC4zMDgxIDEzLjQ3MzQgMTQuMzYzNCAxMy40NTI0IDE0LjQxMyAxMy40MjEzTDE3Ljk1MTQgMTEuMjE1M0MxOC4wNjU4IDExLjU1OCAxOC4xODAzIDExLjg5NSAxOC4yNjYxIDEyLjI0MzRDMTguNTk5OSAxMy40NjMyIDE4LjcyNzUgMTQuNzMgMTguNjQzNyAxNS45OTE3QzE4LjQ3NTUgMTguMjE3MSAxNy41NjggMTkuOTUwMSAxNi4wODk1IDIwLjg3MzJDMTUuMzI2OSAyMS4zMzg3IDE0LjQ1MzEgMjEuNTkxMiAxMy41NTkzIDIxLjYwNDRIMTMuNDQ0OUMxMi41NTExIDIxLjU5MTUgMTEuNjc3MiAyMS4zMzg5IDEwLjkxNDcgMjAuODczMkM5LjQzNSAxOS45NTAxIDguNTI3NTIgMTguMjE3MSA4LjM1OTMgMTUuOTkxN0M4LjI3NjExIDE0LjczIDguNDAzNzMgMTMuNDYzMyA4LjczNjk0IDEyLjI0MzRDOS41MDQyNyA5LjQyMTc1IDExLjA4OTYgNi44ODkyNyAxMy4yOTM4IDQuOTYzOTlDMTQuMjQ0NSA0LjEzMDYgMTUuMzI3MyAzLjQ2MDc3IDE2LjQ5OCAyLjk4MTkxQzE4LjU3MzkgMi4xNTgyMyAyMC41MzE5IDIuMjExOTIgMjIuMDExNiAzLjEzNDk5QzIzLjQ5MTMgNC4wNTgwNiAyNC4zOTc2IDUuNzkxMSAyNC41NjU4IDguMDE1MzhDMjQuNjUwOSA5LjI3NTE2IDI0LjUyNTYgMTAuNTQwMyAyNC4xOTUgMTEuNzU5MVoiIGZpbGw9IiNhN2FhYWQiLz4KPC9zdmc+Cg==) !important;"></span><span class="ab-label">%s</span>';
		}

		$managed_wordpress = [
			'id'     => 'wpaas',
			'title'  => sprintf(
				$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $label )
			),
		];

		$this->admin_bar->add_menu( $managed_wordpress );

	}

	/**
	 * Flush Cache menu item.
	 */
	private function flush_cache_menu_item() {

		if ( ! current_user_can( Cache_V2::$cap ) ) {

			return;

		}

		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-flush-cache',
				'title'  => __( 'Flush Cache', 'gd-system-plugin' ),
				'href'   => Cache_V2::get_flush_url(),
			]
		);

	}

	/**
	 * Hosting Overview menu item.
	 */
	private function hosting_overview_menu_item() {


		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-overview',
				'href'   => esc_url( Plugin::account_url( 'overview' ) ),
				'title'  => sprintf(
					'%s <span class="dashicons dashicons-external"></span>',
					Plugin::is_gd() ? __( 'Back to GoDaddy', 'gd-system-plugin' ) : __( 'Hosting Overview', 'gd-system-plugin' )
				),
				'meta'   => [
					'target' => '_blank',
				],
			]
		);

	}

	/**
	 * Hosting Settings menu item.
	 */
	private function hosting_settings_menu_item() {

		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-settings',
				'href'   => esc_url( Plugin::account_url( 'settings' ) ),
				'title'  => sprintf(
					'%s <span class="dashicons dashicons-external"></span>',
					__( 'Manage Hosting', 'gd-system-plugin' )
				),
				'meta'   => [
					'target' => '_blank',
				],
			]
		);

	}

	/**
	 * Connection Management API key modal menu item.
	 */
	private function pro_connection_key_menu_item() {

		if ( ! is_admin() || filter_input( INPUT_GET, 'showWorker' ) || ! function_exists( 'mwp_get_potential_key' ) ) {

			return;

		}

		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-pro-connection-key',
				'href'   => '#',
				'title'  => sprintf(
					'<span id="mwp-view-connection-key" class="wp-admin-bar-wpaas-pro-connection-key">%s</span>',
					__( 'Connection Management', 'gd-system-plugin' )
				),
			]
		);

	}

	/**
	 * Help & Support menu item
	 */
	private function help_and_support_menu_item() {

		if ( ! is_admin() ) {

			return;

		}

		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-help-and-support',
				'href'   => $this->get_help_docs_url(),
				'title'  => sprintf(
					'%s <span class="dashicons dashicons-external"></span>',
					__( 'WordPress Help', 'gd-system-plugin' )
				),
				'meta'   => [
					'target' => '_blank',
				],
			]
		);

	}

	/**
	 * Enable auto updates for plugins and themes menu item
	 */
	private function enable_auto_updates_menu_item() {

		if ( ! is_admin() ) {

			return;

		}

		$auto_updates_status = get_option( 'mwp_auto_updates_status', 'disabled' );

		$this->admin_bar->add_menu(
			[
				'parent' => 'wpaas',
				'id'     => 'wpaas-enable-auto-updates',
				'href'   => Auto_Updates::get_toggle_auto_update_url( $auto_updates_status ),
				'title'  => $auto_updates_status === 'disabled' ? __( 'Enable auto-updates', 'gd-system-plugin' ) : __( 'Disable auto-updates', 'gd-system-plugin' ),
			]
		);

	}

}
