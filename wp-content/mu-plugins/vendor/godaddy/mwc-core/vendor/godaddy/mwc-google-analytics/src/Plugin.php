<?php
/**
 * Google Analytics
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Google Analytics to newer
 * versions in the future. If you wish to customize Google Analytics for your
 * needs please refer to https://help.godaddy.com/help/40882 for more information.
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2015-2024, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\GoogleAnalytics;

use GoDaddy\WordPress\MWC\GoogleAnalytics\Admin\AJAX;
use GoDaddy\WordPress\MWC\GoogleAnalytics\API\API_Client;
use GoDaddy\WordPress\MWC\GoogleAnalytics\Helpers\Identity_Helper;
use GoDaddy\WordPress\MWC\GoogleAnalytics\Helpers\Order_Helper;
use GoDaddy\WordPress\MWC\GoogleAnalytics\Integrations\Subscriptions_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Google Analytics Main Plugin Class.
 *
 * @since 1.0.0
 */
class Plugin extends Framework\SV_WC_Plugin {


	/** plugin version number */
	public const VERSION = '3.0.6';

	/** @var Plugin the singleton instance of the plugin */
	protected static $instance;

	/** the plugin ID */
	public const PLUGIN_ID = 'google_analytics_pro';

	/** @var API_Client the API client for Google APIs */
	protected API_Client $api_client;

	/** @var Properties_Handler the properties handler instance */
	protected Properties_Handler $properties_handler;

	/** @var Tracking the tracking handler instance */
	protected Tracking $tracking;

	/** @var Identity_Helper the identity handler class instance */
	protected Identity_Helper $identity_helper;

	/** @var Order_Helper the order handler class instance */
	protected Order_Helper $order_helper;

	/** @var Integration|null the integration class instance */
	protected ?Integration $integration = null;

	/** @var AJAX|null the AJAX class instance */
	protected ?AJAX $ajax = null;

	/** @var Subscriptions_Integration|null the Subscriptions Integration class instance */
	protected ?Subscriptions_Integration $subscriptions_integration = null;


	/**
	 * Constructs the class and initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			[
				'text_domain'   => 'woocommerce-google-analytics-pro',
				'supported_features' => [
					'hpos'   => true,
					'blocks' => [
						'cart'     => false,
						'checkout' => false,
					],
				],
			]
		);

		// Loads handlers a bit earlier than the standard framework initialization, so that we Subscriptions event names
		// settings are displayed in admin settings page.
		// make sure that the add_action() call in SV_WC_Plugin::add_hooks() in v5_4_1 matches the remove_action() call below
		if ( remove_action( 'plugins_loaded', [ $this, 'init_plugin' ], 15 ) ) {
			add_action( 'after_setup_theme', [ $this, 'init_plugin' ], 0 );
		}

		// add the plugin to available WooCommerce integrations
		add_filter( 'woocommerce_integrations', [ $this, 'load_integration' ], PHP_INT_MAX );
	}


	/** @inheritDoc */
	public function handle_features_compatibility() : void
	{
		/*
		 * no-op
		 * This is an intentional difference between MWC and the community plugin.
		 * Bundled MWC plugins should not declare Woo compatibility, as they are not standalone plugins.
		 * @link https://godaddy-corp.atlassian.net/browse/MWC-16720
		 */
	}


	/**
	 * Loads and initializes the lifecycle handler instance.
	 *
	 * @since 1.6.0
	 */
	protected function init_lifecycle_handler() : void {

		$this->lifecycle_handler = new Lifecycle( $this );
	}


	/**
	 * Initializes the plugin.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 */
	public function init_plugin(): void {

		// NOTE: since the plugin is loaded earlier than usual, we need to make sure the translations textdomain is available before gettext strings are loaded below
		$this->load_plugin_textdomain();

		$this->setup_handlers();
	}


	/**
	 * Instantiates handlers and stores a reference to them.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function setup_handlers() : void {

		// load subscriptions integration before setting up tracking so that we have a chance to filter
		// events and settings
		if ( $this->is_plugin_active( 'woocommerce-subscriptions.php' ) ) {
			$this->subscriptions_integration = new Subscriptions_Integration();
		}

		$this->api_client         = new API_Client();
		$this->properties_handler = new Properties_Handler();
		$this->tracking           = new Tracking();
		$this->order_helper       = new Order_Helper();
		$this->identity_helper    = new Identity_Helper();

		// AJAX includes
		if ( wp_doing_ajax() ) {
			$this->ajax = new AJAX();
		}
	}


	/**
	 * Gets the Google APIs Client instance
	 *
	 * @since 3.0.0
	 *
	 * @return API_Client
	 */
	public function get_api_client_instance() : API_Client {
		return $this->api_client;
	}


	/**
	 * Gets the Properties_Handler instance
	 *
	 * @since 3.0.0
	 *
	 * @return Properties_Handler
	 */
	public function get_properties_handler_instance() : Properties_Handler {
		return $this->properties_handler;
	}


	/**
	 * Gets the Tracking handler instance
	 *
	 * @since 3.0.0
	 *
	 * @return Tracking
	 */
	public function get_tracking_instance() : Tracking {
		return $this->tracking;
	}


	/**
	 * Adds GA Pro as a WooCommerce integration.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $integrations the existing integrations
	 * @return string[]
	 */
	public function load_integration( array $integrations = [] ): array {

		if ( ! in_array( Integration::class, $integrations, true ) ) {
			$integrations = array_merge( [ self::PLUGIN_ID => Integration::class ], $integrations );
		}

		return $integrations;
	}


	/**
	 * Returns the integration class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Integration the integration class instance
	 */
	public function get_integration(): Integration {

		if ( ! $this->integration instanceof Integration) {

			$integrations = null === WC()->integrations ? [] : WC()->integrations->get_integrations();

			if ( isset( $integrations[ self::PLUGIN_ID ] ) && $integrations[ self::PLUGIN_ID ] instanceof Integration ) {

				$this->integration = $integrations[ self::PLUGIN_ID ];

			} else {

				$this->integration = new Integration();
			}
		}

		return $this->integration;
	}


	/**
	 * Returns the integration class instance.
	 *
	 * @since 1.6.0
	 *
	 * @see Plugin::get_integration() alias for backwards compatibility
	 *
	 * @return Integration
	 */
	public function get_integration_instance(): Integration {
		return $this->get_integration();
	}


	/**
	 * Returns the AJAX class instance.
	 *
	 * @since 1.1.0
	 *
	 * @return AJAX the AJAX class instance
	 */
	public function get_ajax_instance(): AJAX {

		return $this->ajax;
	}


	/**
	 * Returns the Subscriptions integration class instance.
	 *
	 * @since 1.5.0
	 *
	 * @return Subscriptions_Integration
	 */
	public function get_subscriptions_integration_instance(): Subscriptions_Integration {

		return $this->subscriptions_integration;
	}


	/**
	 * Returns the plugin name, localized.
	 *
	 * @since 1.0.0
	 *
	 * @return string the plugin name
	 */
	public function get_plugin_name(): string {

		return __( 'Google Analytics', 'woocommerce-google-analytics-pro' );
	}


	/**
	 * Returns the full path and filename of the plugin file.
	 *
	 * @since 1.0.0
	 *
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __DIR__;
	}


	/**
	 * Returns the plugin sales page URL.
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	public function get_sales_page_url() {

		return 'https://woocommerce.com/products/woocommerce-google-analytics-pro/';
	}


	/**
	 * Returns the plugin documentation URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $section the documentation section, defaults to 40882
	 * @return string the plugin documentation URL
	 */
	public function get_documentation_url(string $section = '40882'): string {

		return "https://www.godaddy.com/help/{$section}";
	}


	/**
	 * Returns the plugin support URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string the plugin support URL
	 */
	public function get_support_url() {

		return 'https://woocommerce.com/my-account/marketplace-ticket-form/';
	}


	/**
	 * Returns the settings page URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $_ unused
	 * @return string the settings page URL
	 */
	public function get_settings_url( $_ = '' ) {

		return admin_url( 'admin.php?page=wc-settings&tab=integration&section=google_analytics_pro' );
	}


	/**
	 * Returns deprecated/removed hooks.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	protected function get_deprecated_hooks() {

		$deprecated_hooks = [
			'wc_google_analytics_pro_product_funnel_steps' => [
				'version' => '1.3.0',
				'removed' => true,
			],
		];

		return $deprecated_hooks;
	}


	/**
	 * Determines if viewing the plugin settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool whether viewing the plugin settings page
	 */
	public function is_plugin_settings() {

		return isset( $_GET['page'], $_GET['tab'] )
			&& 'wc-settings' === $_GET['page']
			&& 'integration' === $_GET['tab']
			&& ( ! isset( $_GET['section'] ) || $this->get_id() === $_GET['section'] );
	}


	/**
	 * Logs API requests & responses.
	 *
	 * Overridden to check if debug mode is enabled in the integration settings.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_api_request_logging() {

		$settings = get_option( 'woocommerce_google_analytics_pro_settings', [] );

		if ( ! isset( $settings['debug_mode'] ) || 'no' === $settings['debug_mode'] ) {
			return;
		}

		parent::add_api_request_logging();
	}


	/**
	 * Adds various admin notices to assist with proper setup and configuration.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_admin_notices() {

		$integration = $this->get_integration();

		// onboarding notice
		if ( ! $integration->is_connected() ) {

			if ( $this->is_plugin_settings() ) {

				// just show "read the docs" notice when on settings
				$notice = sprintf(
					/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
					__( '%1$sNeed help setting up Google Analytics?%2$s Please %3$sread the documentation%4$s.', 'woocommerce-google-analytics-pro' ),
					'<strong>',
					'</strong>',
					'<a target="_blank" href="' . esc_url( $this->get_documentation_url() ) . '">',
					'</a>'
				);

				$this->get_admin_notice_handler()->add_admin_notice( $notice, 'onboarding', [
					'dismissible'             => true,
					'notice_class'            => 'updated',
					'always_show_on_settings' => false,
				] );
			}
		}

		// add GA4 compatibility notices
		$this->add_ga4_compatibility_notice();
		$this->add_ua_warning_notice();
		$this->add_optimize_warning_notice();

		// add platform notices
		$this->maybe_show_google_analytics_pro_plugin_notice();
		$this->maybe_show_google_analytics_free_plugin_notice();
	}


	/**
	 * Adds the GA4 compatibility notice when viewing plugins page or GA settings.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	protected function add_ga4_compatibility_notice(): void {
		global $pagenow;

		if ( 'plugins.php' !== $pagenow && ! $this->is_plugin_settings() ) {
			return;
		}

		if ( $this->get_integration()->get_option( 'ga4_property' ) ) {
			return;
		}

		$notice = sprintf(
			/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
			__( '%1$sGoogle Analytics is now compatible with GA4!%2$s You can now add GA4 properties for event tracking.', 'woocommerce-google-analytics-pro' ),
			'<strong>',
			'</strong>',
			'<a href="' . esc_url( $this->get_settings_url() ) . '">',
			'</a>'
		);

		$notice .= '<div>';
		$notice .= '<p><a href="https://woocommerce.com/document/woocommerce-google-analytics-pro/#section-3" target="_blank">' . __( 'How to set up GA4?' ) . '</a></p>';
		$notice .= ! $this->is_plugin_settings() ? '<a class="button-primary" href="' . esc_url( $this->get_settings_url() ) . '">' . __( 'Set up now' ) . '</a>' : '';
		$notice .= '</div>';

		$this->get_admin_notice_handler()->add_admin_notice( $notice, 'ga4-compatibility', [ 'always_show_on_settings' => false, 'dismissible' => true ] );
	}


	/**
	 * Adds the UA warning notice when viewing GA settings.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	protected function add_ua_warning_notice(): void {

		if ( ! $this->is_plugin_settings() || ! $this->get_integration()->get_option( 'property' ) ) {
			return;
		}

		$notice = __( 'Google is retiring Universal Analytics in July 2023. After that, Universal Analytics settings will no longer be available, and your store will no longer track events in Universal Analytics.', 'woocommerce-google-analytics-pro' );

		$notice .= '<div>';
		$notice .= '<p><a href="https://support.google.com/analytics/answer/11583528?hl=en" target="_blank">' . __( 'Learn more from Google' ) . '</a></p>';
		$notice .= '</div>';

		$this->get_admin_notice_handler()->add_admin_notice( $notice, 'ua-warning', [ 'always_show_on_settings' => true, 'notice_class' => 'notice-warning' ] );
	}


	/**
	 * Adds the Google Optimize warning notice when viewing GA settings.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	protected function add_optimize_warning_notice(): void {

		if ( ! $this->is_plugin_settings() || ! wc_string_to_bool( $this->get_integration()->get_option( 'enable_google_optimize' ) ) ) {
			return;
		}

		$notice = __( 'Google is retiring Google Optimize. It will no longer be available after September 30, 2023.', 'woocommerce-google-analytics-pro' );

		$notice .= '<div>';
		$notice .= '<p><a href="https://support.google.com/optimize/answer/12979939?hl=en" target="_blank">' . __( 'Learn more from Google' ) . '</a></p>';
		$notice .= '</div>';

		$this->get_admin_notice_handler()->add_admin_notice( $notice, 'optimize-warning', [ 'always_show_on_settings' => true, 'notice_class' => 'notice-warning' ] );
	}


	/**
	 * Maybe add a notice to Google Analytics plugin users.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	protected function maybe_show_google_analytics_pro_plugin_notice() {

		$current_screen    = get_current_screen();
		$current_screen_id = ! empty( $current_screen ) ? $current_screen->id : '';

		// only show in WC > Settings > Integration, Plugins and WooCommerce > Extensions pages, and only if the option is set
		if ( ( ! $this->is_plugin_settings() && ! in_array( $current_screen_id, [ 'plugins', 'woocommerce_page_wc-addons' ] ) )
			|| 'yes' !== get_option( 'mwc_google_analytics_show_notice_ga_pro_users' ) ) {
			return;
		}

		$notice_id = $this->get_id_dasherized() . '-ga-pro-users';

		ob_start();

		?>
		<p id="<?php echo esc_attr( "woocommerce-{$notice_id}-notice-buttons" ); ?>">
			<a class="button button-primary" href="<?php echo esc_url( $this->get_settings_url() ); ?>"><?php esc_html_e( 'View settings', 'woocommerce-google-analytics-pro' ); ?></a>
		</p>
		<?php

		$notice_buttons = ob_get_clean();

		$this->get_admin_notice_handler()->add_admin_notice(
			sprintf(
			/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
				__( '%1$sGoogle Analytics is now built-in%2$s – no plugins required! Your Google Analytics settings have been migrated so your tracking is uninterrupted. We’ve deactivated Google Analytics. To make changes, reconnect your Google Analytics account.', 'woocommerce-sequential-order-numbers-pro' ) ,
				'<strong>',
				'</strong>'
			) . $notice_buttons,
			$notice_id,
			[
				'always_show_on_settings' => false,
				'notice_class'            => 'notice-info',
			]
		);
	}


	/**
	 * Maybe add a notice to Google Analytics Free plugin users.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	protected function maybe_show_google_analytics_free_plugin_notice() {

		if ( $this->is_plugin_settings() && $this->is_plugin_active( 'woocommerce-google-analytics-integration.php' ) ) {

			$this->get_admin_notice_handler()->add_admin_notice(
				sprintf(
				/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
					__( '%1$sDeactivate the WooCommerce Google Analytics plugin%2$s before connecting to Google Analytics on this page to avoid tracking events multiple times.', 'woocommerce-google-analytics-pro' ),
					'<a href="' . esc_url( admin_url( 'plugins.php?plugin_status=active' ) ) . '">',
					'</a>'
				),
				$this->get_id_dasherized() . '-ga-free-users',
				[
					'notice_class' => 'notice-warning',
					'dismissible'  => false,
				]
			);
		}
	}


	/**
	 * Adds delayed admin notices on the Integration page if Analytics profile settings are not correct.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_delayed_admin_notices() {

		// warn about deprecated javascript function name
		if ( get_option( 'woocommerce_google_analytics_upgraded_from_gatracker' ) && '__gaTracker' === $this->get_integration()->get_option( 'function_name' ) ) {

			$this->get_admin_notice_handler()->add_admin_notice(
				/* translators: %1$s - function name, %2$s, %4$s - opening <a> tag, %3$s, %5$s - closing </a> tag */
				sprintf( esc_html__( 'Please update any custom tracking code & switch the Google Analytics javascript tracker function name to %1$s in the %2$sGoogle Analytics settings%3$s. You can %4$slearn more from the plugin documentation%5$s.', 'woocommerce-google-analytics-pro' ), '<code>ga</code>', '<a href="' . $this->get_settings_url() . '#woocommerce_google_analytics_pro_additional_settings_section">', '</a>', '<a href="' . $this->get_documentation_url() . '">', '</a>' ),
				'update_function_name',
				[
					'dismissible'             => true,
					'notice_class'            => 'error',
					'always_show_on_settings' => true
				]
			);
		}
	}


	/**
	 * Returns the plugin singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @see wc_google_analytics_pro()
	 *
	 * @return Plugin the plugin singleton instance
	 */
	public static function instance() : self {

		if ( null === self::$instance ) {

			self::$instance = new self();
		}

		return self::$instance;
	}

}


class_alias( Plugin::class, 'WC_Google_Analytics_Pro' );
