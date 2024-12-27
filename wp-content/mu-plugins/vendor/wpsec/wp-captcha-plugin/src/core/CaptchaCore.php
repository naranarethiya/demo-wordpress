<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site.
 *
 * @since      1.0.0
 *
 * @package    Wpsec
 * @subpackage Wpsec/core
 */

namespace Wpsec\captcha\core;

use Wpsec\captcha\handlers\EventLoaderHandler;
use Wpsec\captcha\service\CaptchaService;

/**
 * The core plugin class.
 *
 * This is used to define internationalization and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wpsec
 * @subpackage Wpsec/core
 */
class CaptchaCore {


	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Event handler
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      EventLoaderHandler event_loader_handler
	 */
	private $event_loader_handler;

	/**
	 * The config parameters for capthca plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array $config The config parameters for capthca plugin.
	 */
	protected $config;

	/**
	 * @var CaptchaService
	 */
	private $captcha_service;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale and
	 * the public-facing side of the site.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @since   1.0.0
	 */
	public function __construct( $plugin_name ) {
		$this->captcha_service = new CaptchaService();

		$this->set_config_parameters();
		if ( ! $this->should_activate() ) {
			return;
		}

		$this->version     = defined( 'WPSEC_WP_CP_VERSION' ) ? WPSEC_WP_CP_VERSION : '1.0.0';
		$this->plugin_name = $plugin_name;

		$this->event_loader_handler = new EventLoaderHandler();
		$this->event_loader_handler->load_events();

		add_filter( 'login_form_middle', array( $this->captcha_service, 'get_captcha_html_wrapper_login_form' ), 10, 2 );
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return  string    The version number of the plugin.
	 * @since   1.0.0
	 *
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Check if there are others brute-force/captcha login plugins before we activate our Captcha plugin
	 *
	 * @return  bool Whether should activate plugin or not
	 * @since   1.0.0
	 *
	 */
	private function should_activate() {
		if ( $this->captcha_service->is_xmlrpc_request() ) {
			return false;
		}

		if ( ! $this->captcha_service->is_wpsec_captcha_enabled() ) {
			return false;
		}

		$active_plugins = get_option( 'active_plugins' );
		return empty( $active_plugins ) || empty( array_intersect( $active_plugins, $this->get_whitelisted_captcha_plugins() ) );
	}

	/**
	 * Retrieve whitelisted captcha & bruteforce plugins.
	 *
	 * @return  array    List of whitelisted captcha & bruteforce plugins.
	 * @since   1.0.0
	 *
	 */
	private function get_whitelisted_captcha_plugins() {
		return array_merge( $this->config['whitelisted_captha_plugins'], $this->config['whitelisted_bruteforce_plugins'] );
	}

	/**
	 * Get config parameters
	 * @since   1.0.0
	 *
	 */
	private function set_config_parameters() {
		$this->config = array(
			'whitelisted_captha_plugins'     => array( 'really-simple-captcha/really-simple-captcha.php', 'advanced-nocaptcha-recaptcha/advanced-nocaptcha-recaptcha.php', 'google-captcha/google-captcha.php', 'captcha-code-authentication/wpCaptcha.php', 'wordfence-login-security/wordfence-login-security.php', 'goodbye-captcha/goodbye-captcha.php', 'wp-recaptcha-integration/wp-recaptcha-integration.php', 'captcha-bank/captcha-bank.php', 'wp-captcha/wp-captcha.php', 'simple-login-captcha/simple-login-captcha.php', 'hcaptcha-for-forms-and-more/hcaptcha.php', 'no-captcha-recaptcha-for-woocommerce/woocommerce-no-captcha-recaptcha.php', 'captcha-bws/captcha-bws.php', 'formcraft-recaptcha/formcraft-captcha.php', 'captcha-them-all/captcha-them-all.php', 'wp-captcha-booster/wp-captcha-booster.php', 'wp-advanced-math-captcha/wp-math-captcha.php', 'wc-captcha/wc-captcha.php', 'wp-forms-puzzle-captcha/wp-forms-puzzle-captcha.php', 'security-antivirus-firewall/index.php', 'content-protector/content-protector.php', 'invisible-recaptcha/invisible-recaptcha.php' ),
			'whitelisted_bruteforce_plugins' => array( 'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php', 'siteguard/siteguard.php', 'wp-fail2ban/wp-fail2ban.php', 'ip-geo-block/ip-geo-block.php', 'security-malware-firewall/security-malware-firewall.php', 'http-auth/http-auth.php', 'security-protection/security-protection.php', 'protection-against-ddos/protection-against-ddos.php', 'cwis-antivirus-malware-detected/cwis-antivirus-malware-detected.php' ),
		);
	}
}
