<?php

namespace WPaaS;

use GoDaddy\WordPress\Plugins\NextGen;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

trait Helpers {

	/**
	 * Represent if gd-config.php exists.
	 *
	 * @var boolean
	 */
	private static $gd_config_exists = null;

	/**
	 * Return the plugin version.
	 *
	 * @return string|false
	 */
	public static function version() {

		return Plugin::$data['version'];

	}

	/**
	 * Return the plugin basename.
	 *
	 * @return string|false
	 */
	public static function basename() {

		return Plugin::$data['basename'];

	}

	/**
	 * Return the plugin base directory path (with trailing slash).
	 *
	 * @return string|false
	 */
	public static function base_dir() {

		return Plugin::$data['base_dir'];

	}

	/**
	 * Return the plugin assets URL (with trailing slash).
	 *
	 * @param  string $path (optional)
	 *
	 * @return string|false
	 */
	public static function assets_url( $path = '' ) {

		$path = ( 0 === strpos( $path, '/' ) ) ? $path : '/' . $path;

		return ( Plugin::$data['assets_url'] ) ? untrailingslashit( Plugin::$data['assets_url'] ) . $path : false;

	}

	/**
	 * Return the plugin assets directory path (with trailing slash).
	 *
	 * @param  string $path (optional)
	 *
	 * @return string|false
	 */
	public static function assets_dir( $path = '' ) {

		$path = ( 0 === strpos( $path, '/' ) ) ? $path : '/' . $path;

		return ( Plugin::$data['assets_url'] ) ? untrailingslashit( Plugin::$data['assets_dir'] ) . $path : false;

	}

	/**
	 * Return an array of bundled plugins that have been loaded.
	 *
	 * @return array
	 */
	public static function bundled_plugins_loaded() {

		return ! empty( Plugin::$data['bundled_plugins_loaded'] ) ? (array) Plugin::$data['bundled_plugins_loaded'] : [];

	}

	/**
	 * Return a plugin config.
	 *
	 * @param  string $config
	 *
	 * @deprecated 4.149.0
	 * @return mixed|false
	 */
	public static function config( $config ) {

		return false;

	}
	/**
	 * Check if the site locale is English.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_english() {

		$result = ( 'en' === substr( get_locale(), 0, 2 ) );

		/**
		 * Filter if the site locale is English.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_english', $result );

	}

	/**
	 * Return an array of supported brands.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public static function brands() {

		$brands = [ 'gd', 'mt', 'reseller' ];

		/**
		 * Filter the array of supported brands.
		 *
		 * @since 3.1.0
		 *
		 * @var array
		 */
		return (array) apply_filters( 'wpaas_brands', $brands );

	}

	/**
	 * Return the current brand.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public static function brand() {

		$brand  = ( self::reseller_id() ) ? 'reseller' : null; // Default
		$brands = array_diff( self::brands(), [ 'reseller' ] ); // Non-default

		foreach ( $brands as $brandname ) {

			$callback = 'is_' . trim( $brandname );

			if ( is_callable( [ __CLASS__, $callback ] ) && self::$callback() ) {

				$brand = $brandname;

				break;

			}

		}

		/**
		 * Filter the current brand.
		 *
		 * @since 3.1.0
		 *
		 * @var string
		 */
		return (string) apply_filters( 'wpaas_brand', $brand );

	}

	/**
	 * Return the value whose array key matches the current brand.
	 *
	 * @since 3.1.0
	 *
	 * @param  array $values
	 * @param  mixed $default (optional)
	 *
	 * @return mixed
	 */
	public static function use_brand_value( $values, $default = null ) {

		return isset( $values[ self::brand() ] ) ? $values[ self::brand() ] : $default;

	}

	/**
	 * Check if this is a reseller site.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_reseller() {

		$result = ( 'reseller' === self::brand() );

		/**
		 * Filter if this is a reseller site.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_reseller', $result );

	}

	/**
	 * Check if this is a GD site.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_gd() {

		$result = ( 1 === self::reseller_id() );

		/**
		 * Filter if this is a GD site.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_gd', $result );

	}

	/**
	 * Check if this is a MT site.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_mt() {

		$result = ( 495469 === self::reseller_id() );

		/**
		 * Filter if this is a MT site.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_mt', $result );

	}

	/**
	 * For private labelled brands.
	 *
	 * @return bool
	 */
	public static function is_reseller_brand() {
		$reseller_brands = [
			"Media Temple" => 495469,
			"123 Reg" => 525844,
			"Host Europe" => 525847,
			"Domain Factory" => 525845,
			"TSO Host" => 527397,
			"Heart Internet" => 525848
		];

		if (in_array(GD_RESELLER, $reseller_brands)) {
			return true;
		}

		return false;
	}

    /**
     * Check the customer type to provide the correct wording for the php update alert.
     *
     * @return string
     */
    public static function get_update_php_alert_text() {
        $direct_update_url = wp_get_direct_php_update_url();
        $update_php_button = $direct_update_url ? '
                        <a href=' . $direct_update_url . ' class="button button-small button-primary alignright" target="_blank">
                            <div>
                                ' . __("Update Now") .'
                                <span class="screen-reader-text">' . __("(opens in a new tab)") . '</span>
                                <span aria-hidden="true" class="dashicons dashicons-external"></span>
                            </div>
                        </a>' : '';
        $update_php_title_text = __('Update to the latest version of PHP to make your site faster and more secure', 'gd-system-plugin');
        $update_php_description_text = __('Your current PHP version (' . PHP_VERSION . ') is no longer supported. The update is quick and reduces your risk of your site crashing.', 'gd-system-plugin');
        $format = __('
                <div>
                    <div>
                    ' . $update_php_button . '
                        <div><strong>' . $update_php_title_text . '</strong></div>
                        ' . $update_php_description_text . '
                        %1$s
                    </div>
                </div>
                ');

        /**
         * For GoDaddy customer.
         */
        if (self::is_gd()) {
            $learn_more_link = '<a
                                    href="https://www.godaddy.com/help/change-my-php-version-32202"
                                    target="_blank"
                                    rel="noopener">
                                        ' . __("Learn how to update to the recommended version of PHP for your site.", 'gd-system-plugin') . '
                                </a>';
            return sprintf($format, $learn_more_link );
        }
        /**
         * For private labelled brands.
         */
        if (self::is_reseller_brand()) {
            $learn_more_link = '<a
                                    href="' . 'https://www.secureserver.net/help/a-41164?pl_id=' . GD_RESELLER . '"
                                    target="_blank"
                                    rel="noopener">
                                        ' . __("Learn how to update to the recommended version of PHP for your site.", 'gd-system-plugin') . '
                                </a>';
            return sprintf($format, $learn_more_link );
         }
        /**
         * Default value For resellers.
         */
        $contact_support_text = __('Contact support for more info.', 'gd-system-plugin');

        return sprintf($format, $contact_support_text);
    }

	/**
	 * Check if a given (or current) URL is using 'www' in the domain.
	 *
	 * @param  string $url (optional)
	 *
	 * @return bool
	 */
	public static function is_www_url( $url = '' ) {

		$url = $url ? $url : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' );

		return ( 0 === strpos( wp_parse_url( $url, PHP_URL_HOST ), 'www.' ) );

	}

	/**
	 * Check if the WP Admin should be forced SSL.
	 *
	 * @return bool
	 */
	public static function is_ssl_admin() {

		return ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ); // @codingStandardsIgnoreLine

	}

	/**
	 * Check if the login should be forced SSL.
	 *
	 * @return bool
	 */
	public static function is_ssl_login() {

		return ( defined( 'FORCE_SSL_LOGIN' ) && FORCE_SSL_LOGIN ); // @codingStandardsIgnoreLine

	}

	/**
	 * Check if this is a staging site.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_staging_site() {

		$result = defined( 'GD_STAGING_SITE' ) ? GD_STAGING_SITE : false;

		/**
		 * Filter if this is a staging site.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_staging_site', $result );

	}

	/**
	 * Get the current environment type.
	 *
	 * @return string
	 */
	public static function get_env() {
		$result = 'prod';
		if ( $env = getenv( 'SERVER_ENV' ) ) {
			$result = $env;
		} elseif ( defined('GD_TEMP_DOMAIN') && strpos(GD_TEMP_DOMAIN, '.ide') !== false ) {
			$result =  'test';
		}

		/**
		 * Filter the current environment type.
		 *
		 * @since 2.0.1
		 *
		 * @var string
		 */
		return (string) apply_filters( 'wpaas_get_env', $result );

	}

	/**
	 * Check for a specific environment.
	 *
	 * @param  string|array $env
	 *
	 * @return bool
	 */
	public static function is_env( $env ) {

		$current = self::get_env();
		$result  = is_array( $env ) ? in_array( $current, $env, true ) : ( $env === $current );

		/**
		 * Filter the check for a specific environment.
		 *
		 * @since 2.0.1
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_env', $result );

	}

	/**
	 * Check if this is a temporary domain.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_temp_domain() {

		$result = false;

		if ( self::is_staging_site() ) {

			$result = true;

		}

		/**
		 * Filter if this is a temporary domain.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_temp_domain', $result );

	}

	/**
	 * Check if this site is in multiple domain mode.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_multi_domain_mode() {

		$result = get_option( 'gd_system_multi_domain' );

		/**
		 * Filter if this site is in multiple domain mode.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_is_multi_domain_mode', ( false !== $result ) );

	}

	/**
	 * Checks if gd-config.php exists.
	 *
	 * @return bool
	 */
	private static function gd_config_exists() {

		$gd_config_paths = [
			ABSPATH . 'gd-config.php',
			ABSPATH . '../local/rendered/gd-config.php',
			WPMU_PLUGIN_DIR . '/bin/gd-config.php',
		];

		foreach ( $gd_config_paths as $path ) {

			if ( is_readable( $path ) ) {

				return true;

			}

		}

		return false;
	}

	/**
	 * Check if this site is hosted on WPaaS.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_wpaas() {


		if ( self::$gd_config_exists == null ) {
			self::$gd_config_exists = self::gd_config_exists();
		}
		$platformCLI  = getenv('WPAAS_CLI');
		$platformWeb  = !empty($_SERVER['WPAAS_SITE_ID']) || getenv('WPAAS_SITE_ID') || getenv('WPAAS_V2_SITE_ID');
		$userENV      = getenv('USER');
		$platformSFTP = preg_match('/^site[0-9]+$/', $userENV);

		$result = ($platformWeb || $platformCLI || $platformSFTP) && self::$gd_config_exists;


		/**
		 * Filter if this site is hosted on WPaaS.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'is_wpaas', $result );

	}

	/**
	 * Check if this site is hosted on WPaaS V2.
	 *
	 * @return bool
	 * @since 4.148.0
	 */
	public static function is_wpaas_v2() {
		if ( ! self::is_wpaas() ) { // In case someone skips is_wpaas check
			return false;
		}

		$result = ! empty( getenv( 'WPAAS_V2_SITE_ID' ) );

		return (bool) apply_filters( 'is_wpaas_v2', $result );
	}

	/**
	 * Check if the log is enabled.
	 *
	 * @return bool
	 */
	public static function is_log_enabled() {

		/**
		 * Filter if the log is enabled.
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_log_enabled', true );

	}

	/**
	 * Check if the file editor has been enabled.
	 *
	 * @return bool
	 */
	public static function is_file_editor_enabled() {

		return ( 1 === (int) get_site_option( 'wpaas_file_editor_enabled' ) );

	}

	/**
	 * Return the date this site was created.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return int|string
	 */
	public static function site_created_date( $format = 'U' ) {

		// Use when this constant was introduced as default (Tue, 22 Dec 2015 00:00:00 GMT)
		$time   = defined( 'GD_SITE_CREATED' ) ? (int) GD_SITE_CREATED : 1450742400;
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( 'U' === $format ) ? $time : gmdate( $format, $time );

		/**
		 * Filter the date this site was created.
		 *
		 * @since 2.0.0
		 *
		 * @var int|string
		 */
		return apply_filters( 'wpaas_site_created_date', $date );

	}

	/**
	 * Checks if site is created after certain point in time
	 *
	 * @param int $datetime (timestamp)
	 *
	 * @since 4.16.0
	 *
	 * @return bool
	 */
	public static function is_site_created_after( $datetime ) {

		return defined( 'GD_SITE_CREATED' ) && (int) GD_SITE_CREATED >= $datetime;

	}

	/**
	 * Check if site is in some % based on date of creation
	 * It will look by default from date time of oldest account/site created until now
	 *
	 * @param int   $percentage
	 * @param int   $date_from  // timestamp
	 * @param int   $date_end   // timestamp
	 *
	 * @since 4.35.0
	 *
	 * @return bool
	 */
	public static function is_site_in_percentage( $percentage, $date_from = null, $date_end = null ) {

		if ( ! defined( 'GD_SITE_CREATED' ) || $percentage <= 0 || $percentage > 100 ) {
			return false;
		}

		if ( empty( $date_from ) ) {
			$date_from = 1379462400; // 09.18.2013 00:00:00 GMT - oldest account/site entry in PROD DB
		}

		if ( empty( $date_end ) ) {
			$date_end = time();
		}

		$site_created_timestamp = (int) GD_SITE_CREATED;

		if ( $site_created_timestamp < $date_from || $site_created_timestamp > $date_end ) {
			return false;
		}

		$day           = (int) date( 'd', $site_created_timestamp );
		$days_in_month = (int) date( 't', $site_created_timestamp );

		$number_of_days_allowed = (int) ( $percentage / 100 * $days_in_month );

		return $day <= $number_of_days_allowed;
	}

	/**
	 * Return the date of the first Administrator login.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function first_login_date( $format = 'U' ) {

		$time   = (int) get_option( 'gd_system_first_login' );
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( $time && 'U' === $format ) ? $time : ( $time ? gmdate( $format, $time ) : false );

		return $date;

	}

	/**
	 * Return the date of the last Administrator login.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function last_login_date( $format = 'U' ) {

		$time   = (int) get_option( 'gd_system_last_login' );
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( $time && 'U' === $format ) ? $time : ( $time ? gmdate( $format, $time ) : false );

		return $date;

	}

	/**
	 * Return the date of the first publish activity.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function first_publish_date( $format = 'U' ) {

		$time   = (int) get_option( 'gd_system_first_publish' );
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( $time && 'U' === $format ) ? $time : ( $time ? gmdate( $format, $time ) : false );

		return $date;

	}

	/**
	 * Return the date of the last publish activity.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function last_publish_date( $format = 'U' ) {

		$time   = (int) get_option( 'gd_system_last_publish' );
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( $time && 'U' === $format ) ? $time : ( $time ? gmdate( $format, $time ) : false );

		return $date;

	}

	/**
	 * Return the last cache flush date.
	 *
	 * @param string $format (optional)
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public static function last_cache_flush_date( $format = 'U' ) {

		$time   = (int) get_option( 'gd_system_last_cache_flush' );
		$format = empty( $format ) ? 'U' : $format;
		$date   = ( $time && 'U' === $format ) ? $time : ( $time ? gmdate( $format, $time ) : false );

		/**
		 * Filter the last cache flush date.
		 *
		 * @since 2.0.0
		 *
		 * @var mixed
		 */
		return apply_filters( 'wpaas_last_cache_flush_date', $date );

	}

	/**
	 * Check if this site has used WPEM (not opted-out).
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function has_used_wpem() {

		$result = ( defined( 'GD_EASY_MODE' ) && GD_EASY_MODE && get_option( 'wpem_done' ) && ! get_option( 'wpem_opt_out' ) );

		/**
		 * Filter if this site has used WPEM (not opted-out).
		 *
		 * @since 2.0.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_has_used_wpem', $result );

	}

	/**
	 * Check if this site used WPNUX starter template on-boarding.
	 *
	 * @since 3.11.0
	 *
	 * @return bool
	 */
	public static function has_used_wpnux() {

		$result = ! empty( get_option( 'wpnux_imported' ) );

		/**
		 * Filter if this site used WPNUX starter template on-boarding.
		 *
		 * @since 3.11.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_has_used_wpnux', $result );

	}

	/**
	 * Check if this site should use our simplified UX.
	 *
	 * @since 3.12.0
	 *
	 * @return bool
	 */
	public static function use_simple_ux() {

		$result = defined( 'GD_SIMPLE_UX' ) ? GD_SIMPLE_UX : ( ! WP_DEBUG && 'go' === get_option( 'stylesheet' ) && self::has_used_wpnux() );

		/**
		 * Filter if this site should use our simplified UX.
		 *
		 * @since 3.12.0
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_use_simple_ux', $result );

	}

	/**
	 * Check if a plugin is currently active.
	 *
	 * @since 3.17.0
	 *
	 * @param  string $basename
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $basename ) {

		if ( ! function_exists( 'is_plugin_active' ) ) {

			require_once ABSPATH . 'wp-admin/includes/plugin.php';

		}

		return is_plugin_active( $basename );

	}

	/**
	 * Check if a plugin is currently activating.
	 *
	 * @since 3.17.0
	 *
	 * @param  string $basename
	 *
	 * @return bool
	 */
	public static function is_plugin_activating( $basename ) {

		return ( is_admin() && filter_input( INPUT_GET, 'plugin' ) === $basename && in_array( filter_input( INPUT_GET, 'action' ), [ 'error_scrape', 'activate' ], true ) );

	}

	/**
	 * Check if this site has a particular plan.
	 *
	 * @since 3.11.2
	 *
	 * @param string $plan
	 *
	 * @return bool
	 */
	public static function has_plan( $plan ) {

		$result = defined( 'GD_PLAN_NAME' ) ? ( GD_PLAN_NAME == $plan ) : false; // Loose comparison OK.

		/**
		 * Filter if this site has a particular plan.
		 *
		 * @since 3.11.2
		 *
		 * @param string $plan
		 *
		 * @var bool
		 */
		return (bool) apply_filters( 'wpaas_has_plan', $result, $plan );

	}

	/**
	 * Return the reseller ID.
	 *
	 * @since 2.0.0
	 *
	 * @return int|false
	 */
	public static function reseller_id() {

		return defined( 'GD_RESELLER' ) ? (int) GD_RESELLER : false;

	}

	/**
	 * Return the site domain.
	 *
	 * @return string
	 */
	public static function domain() {

		return wp_parse_url( home_url(), PHP_URL_HOST );

	}

	/**
	 * Return an external URL useful for hosting account management.
	 *
	 * @return string
	 */
	public static function account_url( $path = 'overview' ) {

		$domain = self::domain();

		if ( self::is_mt() ) {

			return in_array( $path, [ 'overview', 'settings' ], true ) ? 'https://ac.mediatemple.net/services/wordpress/plugin-callback/index.mt' : "https://ac.mediatemple.net/services/wordpress/plugin-callback/index.mt?domain={$domain}&action={$path}";

		}

		$env    = Plugin::get_env();
		$prefix = ( 'prod' === $env ) ? '' : "{$env}-";
		$tld    = self::is_gd() ? 'godaddy.com' : 'secureserver.net';

		return "https://host.{$prefix}{$tld}/mwp/sitelookup/?domain={$domain}&path={$path}";

	}

	/**
	 * Return the VIP.
	 *
	 * @since 2.0.0
	 *
	 * @return string|false
	 */
	public static function vip() {

		return defined( 'GD_VIP' ) ? (string) GD_VIP : false;

	}

	/**
	 * Return the account ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function account_id() {

		$account_id = self::is_wp_cli() ? basename( dirname( ABSPATH ) ) : ( ! empty( $_SERVER['REAL_USERNAME'] ) ? $_SERVER['REAL_USERNAME'] : false );

		/**
		 * Filter the account ID.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		return (string) apply_filters( 'wpaas_account_id', $account_id );

	}

	/**
	 * Return the ASAP key.
	 *
	 * @since 2.0.0
	 *
	 * @return string|false
	 */
	public static function asap_key() {

		$asap_key = defined( 'GD_ASAP_KEY' ) ? (string) GD_ASAP_KEY : false;

		/**
		 * Filter the ASAP key.
		 *
		 * @since 2.0.0
		 *
		 * @var string|false
		 */
		return apply_filters( 'wpaas_asap_key', $asap_key );

	}

	/**
	 * Return the XID.
	 *
	 * @since 2.0.0
	 *
	 * @return int|false
	 */
	public static function xid() {

		$xid = self::is_wp_cli() ? (int) substr( substr( self::account_id(), 4 ), 0, -3 ) : ( isset( $_SERVER['XID'] ) ? (int) $_SERVER['XID'] : 0 );
		$xid = ( $xid > 1000000 ) ? $xid : false;

		/**
		 * Filter the XID.
		 *
		 * @since 2.0.0
		 *
		 * @var int|false
		 */
		return apply_filters( 'wpaas_xid', $xid );

	}

	/**
	 * Check if the current process is using WP-CLI.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_wp_cli() {

		return ( defined( 'WP_CLI' ) && WP_CLI );

	}

	/**
	 * Check if the current process is using WP-CLI.
	 *
	 * @since 4.75.0
	 *
	 * @return bool
	 */
	public static function is_wp_cron() {

		return ( defined( 'DOING_CRON' ) && DOING_CRON );

	}

	/**
	 * Base WP-CLI command.
	 *
	 * @return string
	 */
	public static function cli_base_command() {

		$commands = [
			'gd' => 'godaddy',
			'mt' => 'mt',
		];

		$command = self::use_brand_value( $commands, 'wpaas' );

		/**
		 * Filter the base WP-CLI command.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		return (string) apply_filters( 'wpaas_cli_base_command', $command );

	}

	/**
	 * Return a WP-CLI command.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $subcommand
	 * @param  array  $options (optional)
	 * @param  bool   $wp (optional)
	 *
	 * @return string
	 */
	public static function cli_command( $subcommand, array $options = [], $wp = true ) {

		foreach ( $options as $key => &$value ) {

			$value = is_bool( $value ) ? sprintf( '--%s', $key ) : sprintf( '--%s=%s', $key, is_int( $value ) ? $value : escapeshellarg( $value ) );

		}

		return trim(
			sprintf(
				'%s %s %s %s',
				( $wp ) ? 'wp' : null,
				escapeshellcmd( self::cli_base_command() ),
				escapeshellcmd( $subcommand ),
				implode( ' ', $options )
			)
		);

	}

	/**
	 * Return an asyncronous WP-CLI command.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $subcommand
	 * @param  array  $options (optional)
	 * @param  bool   $wp (optional)
	 *
	 * @return string
	 */
	public static function async_cli_command( $subcommand, array $options = [], $wp = true ) {

		return self::cli_command( $subcommand, $options, $wp ) . ' > /dev/null 2>/dev/null &'; // Non-blocking

	}

	/**
	 * Set/update the value of a site transient using a persistent manner. Uses options API.
	 *
	 * You do not need to serialize values, if the value needs to be serialize, then
	 * it will be serialized before it is set.
	 *
	 * @since 2.0.2
	 *
	 * @see set_site_transient()
	 *
	 * @param string $transient  Transient name. Expected to not be SQL-escaped. Must be
	 *                           40 characters or fewer in length.
	 * @param mixed  $value      Transient value. Expected to not be SQL-escaped.
	 * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
	 * @return bool False if value was not set and true if value was set.
	 */
	public static function set_persistent_site_transient( $transient, $value, $expiration = 0 ) {

		$transient_timeout = '_site_transient_timeout_' . $transient;
		$option            = '_site_transient_' . $transient;

		if ( false === get_site_option( $option ) ) {

			if ( $expiration ) {

				add_site_option( $transient_timeout, time() + $expiration );

			}

			return add_site_option( $option, $value );

		}

		if ( $expiration ) {

			update_site_option( $transient_timeout, time() + $expiration );

		}

		return update_site_option( $option, $value );

	}

	/**
	 * Transient function that skips object cache check and fallback to db instead.
	 *
	 * @param string $transient Transient name. Expected to not be SQL-escaped.
	 *
	 * @since 2.0.2
	 *
	 * @see get_site_transient()
	 *
	 * @return bool|mixed
	 */
	public static function get_persistent_site_transient( $transient ) {

		$transient_option  = '_site_transient_' . $transient;
		$transient_timeout = '_site_transient_timeout_' . $transient;
		$timeout           = get_site_option( $transient_timeout );

		if ( false !== $timeout && $timeout < time() ) {

			self::delete_persistent_site_transient( $transient );

			$value = false;

		}

		if ( ! isset( $value ) ) {

			$value = get_site_option( $transient_option );

		}

		return $value;

	}

	/**
	 * Remove the persistent transient value.
	 *
	 * @param string $transient name.
	 * @return void
	 */
	public static function delete_persistent_site_transient( $transient ) {

		$transient_option  = '_site_transient_' . $transient;
		$transient_timeout = '_site_transient_timeout_' . $transient;

		delete_site_option( $transient_option );
		delete_site_option( $transient_timeout );

	}

	/**
	 * Get the WooCommerce extension basename from its slug.
	 *
	 * Sometimes extensions have a non-standard basename so we need this
	 * helper method to ensure those are dealt with appropriately.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public static function get_woo_extension_basename( $slug ) {

		$slug_to_basename = [
			'woocommerce-product-enquiry-form' => 'product-enquiry-form',
		];

		$filename = isset( $slug_to_basename[ $slug ] ) ? $slug_to_basename[ $slug ] : $slug;

		return "{$slug}/{$filename}.php";

	}

	/**
	 * Determine which builder platform was used to create the current page.
	 *
	 * Possible values:
	 *
	 * -- beaver-builder
	 * -- brizy
	 * -- divy
	 * -- elementor
	 * -- oxygen
	 * -- themify-builder
	 * -- visual-composer
	 * -- wp-block-editor (Gutenberg)
	 * -- wp-classic-editor
	 *
	 * Will return NULL when:
	 *
	 * -- The builder plugin used to construct the page is inactive.
	 * -- The WP Block Editor is being used but the page contains no blocks.
	 * -- A page builder platform can't be detected.
	 *
	 * @param WP_Post $post
	 *
	 * @return string|null
	 */
	public static function get_page_builder( $post ) {

		if ( ! is_a( $post, 'WP_Post' ) ) {

			global $post;

		}

		if ( ! isset( $post->post_content ) ) {

			return;

		}

		switch ( true ) {

			case ( class_exists( 'FLBuilderLoader' ) && 1 === (int) get_post_meta( $post->ID, '_fl_builder_enabled', true ) ):
				$builder = 'beaver-builder';

				break;

			case ( defined( 'BRIZY_VERSION' ) && get_post_meta( $post->ID, 'brizy_post_uid', true ) ):
				$builder = 'brizy';

				break;

			case ( defined( 'ET_BUILDER_VERSION' ) && 'on' === get_post_meta( $post->ID, '_et_pb_use_builder', true ) ):
				$builder = 'divi';

				break;

			case ( defined( 'ELEMENTOR_VERSION' ) && 'builder' === get_post_meta( $post->ID, '_elementor_edit_mode', true ) ):
				$builder = 'elementor';

				break;

			case ( defined( 'CT_VERSION' ) && get_post_meta( $post->ID, 'ct_builder_shortcodes', true ) ):
				$builder = 'oxygen';

				break;

			case ( defined( 'THEMIFY_VERSION' ) && get_post_meta( $post->ID, '_themify_builder_settings_json', true ) ):
				$builder = 'themify-builder';

				break;

			case ( defined( 'VCV_VERSION' ) && 'vc' === get_post_meta( $post->ID, '_vcv-page-template-type', true ) ):
				$builder = 'visual-composer';

				break;

			case class_exists( 'Classic_Editor' ):
				// Normalize old options: https://plugins.trac.wordpress.org/browser/classic-editor/trunk/classic-editor.php?rev=2084072#L254
				$default = in_array( get_option( 'classic-editor-replace' ), [ 'block', 'no-replace' ], true ) ? 'block-editor' : 'classic-editor';
				$builder = ( 'allow' === get_option( 'classic-editor-allow-users' ) ) ? get_post_meta( $post->ID, 'classic-editor-remember', true ) : $default;
				$builder = in_array( $builder, [ 'block-editor', 'classic-editor' ], true ) ? $builder : $default;
				$builder = 'wp-' . $builder;

				break;

			default:
				$builder = ( false !== strpos( $post->post_content, '<!-- wp:' ) ) ? 'wp-block-editor' : null;

		}

		return $builder;

	}

	/**
	 * Forcefully unregister an action or filter by its hooked class name and method.
	 *
	 * For those times when a plugin has registered a hook to a class method
	 * without also storing the instance of that class object in a global variable.
	 *
	 * @param string       $hook_name
	 * @param string       $class_name
	 * @param string|array $method_name
	 *
	 * @return void
	 */
	public static function force_remove_hook( $hook_name, $class_name, $method_name ) {

		global $wp_filter;

		if ( empty( $wp_filter[ $hook_name ] ) ) {

			return;

		}

		$wp_hook = (array) $wp_filter[ $hook_name ];

		if ( empty( $wp_hook['callbacks'] ) ) {

			return;

		}

		foreach ( $wp_hook['callbacks'] as $priority => $filters ) {

			foreach ( $filters as $unique_id => $filter ) {

				if ( is_object( $filter['function'] ) || empty( $filter['function'][0] ) || empty( $filter['function'][1] ) ) {

					continue;

				}

				if ( ! is_object( $filter['function'][0] ) || $class_name !== get_class( $filter['function'][0] ) || ! in_array( $filter['function'][1], (array) $method_name, true ) ) {

					continue;

				}

				if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {

					unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );

				} else {

					unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );

				}

			}

		}

	}

	/**
	 * Return the state of RUM.
	 *
	 * @return boolean
	 */
	public static function is_rum_enabled() {

		return (bool) apply_filters( 'wpaas_rum_enabled', defined( 'GD_RUM_ENABLED' ) ? GD_RUM_ENABLED : false );

	}

	/**
	 * Return if GD_PLAN_NAME contains WordPress Design Service plans.
	 *
	 * @return boolean
	 */
	public static function is_wds() {

		// Short circuit. WDS Plugin is active.
		if ( self::is_plugin_active( 'pws-core/core.php' ) ) {

			return true;

		}

		// Plan undefined and plugin not present. No WDS.
		if ( ! defined( 'GD_PLAN_NAME' ) ) {

			return false;

		}

		// Plan is defined. Check if matching these strings.
		$needles = array( 'Design Service', 'WDS' );

		foreach ( $needles as $needle ) {

			if ( false !== stripos( GD_PLAN_NAME, $needle ) ) {

				// Short circuit on true to prevent value switching after match.
				return true;

			};

		}

		// We only reach if no matching strings. No WDS.
		return false;

	}

	/**
	 * Get the first user with admin priviledge.
	 *
	 * @return \WP_User|false
	 */
	public static function get_first_admin_user() {

		$user = get_users(
			[
				'role'   => 'administrator',
				'number' => 1,
				'orderby' => 'ID',
    				'order' => 'ASC'
			]
		);

		return ( isset( $user[0] ) && $user[0] instanceof \WP_User ) ? $user[0] : false;

	}

	/**
	 * Returns the base WPNUX url.
	 */
	public static function get_wpnux_url( $path = '' ) {

		$env    = Plugin::get_env();
		$prefix = ( 'prod' === $env ) ? '' : "{$env}-";
		$path   = ltrim( $path, '/' );

		return "https://wpnux.{$prefix}godaddy.com/$path";

	}

	/**
	 * Check if a option is autoloated and if it is return the value.
	 *
	 * @return boolean
	 */
	public static function is_option_autoloaded( $option_name, $option_value = null, $strict = false ) {

		$alloptions = wp_load_alloptions();

		if ( ! isset( $alloptions[ $option_name ] ) ) {

			return false;

		}

		if ( is_null( $option_value ) ) {

			return $alloptions[ $option_name ];

		}

		return $strict ? get_option( $option_name ) === $option_value : get_option( $option_name ) == $option_value;

	}

	/**
	 * Get V2 APP ID
	 *
	 * @return mixed|string|null
	 *
	 * @since 4.148.0
	 */
	public static function get_v2_app_id() {
		$env = getenv( 'WPAAS_V2_SITE_ID' );
		if ( empty( $env ) ) {
			return null;
		}
		$parts = explode( ':', $env );

		return $parts[1];
	}

	/**
	 * Sing http request body and return it along with sign headers
	 *
	 * @param string $request_body
	 *
	 * @return array
	 */
	public static function sign_http_request( $request_body ) {

		// Gather signature params
		$nonce            = sprintf( '%s_%s', rand(), time() );
		$site_token       = defined( 'GD_SITE_TOKEN' ) ? GD_SITE_TOKEN : null;
		$site_account_id  = defined( 'GD_ACCOUNT_UID' ) ? GD_ACCOUNT_UID : null;
		$domain           = defined( 'GD_TEMP_DOMAIN' ) ? GD_TEMP_DOMAIN : null;
		$sign_request_key = 'wp1_request';

		if ( empty( $site_token ) || empty( $site_account_id ) || empty( $domain ) ) {
			return array();
		}

		// Hash signature params
		$body_payload_hash    = hash( 'sha256', $request_body );
		$token_and_nonce_hash = hash_hmac( 'sha256', $site_token, $nonce );
		$sign_key_data        = hash_hmac( 'sha256', $token_and_nonce_hash, $site_account_id );
		$signature_key        = hash_hmac( 'sha256', $sign_key_data, $sign_request_key );

		// Create signature
		$signature_value = sprintf( '%s%s%s', $nonce, $domain, $body_payload_hash );
		$signature       = hash_hmac( 'sha256', $signature_value, $signature_key );

		if ( empty( $body_payload_hash ) || empty( $token_and_nonce_hash ) || empty( $sign_key_data ) || empty( $signature_key ) || empty( $signature ) ) {
			return array();
		}

		return array(
			'x-wp-nonce'     => $nonce,
			'x-wp-origin'    => $domain,
			'x-wp-signature' => $signature,
			'x-wp-bodyhash'  => $body_payload_hash,
			'x-wp-token'     => substr($site_token, -5)
		);

	}

	/** @var array $subscription_plans_per_env */
	private static $subscription_plans_per_env = array(
		'local'    => 'https://d3rkwznpdz0oz2.cloudfront.net/wp-subscription-plans.json',
		'dev'      => 'https://d3rkwznpdz0oz2.cloudfront.net/wp-subscription-plans.json',
		'test'     => 'https://d2mt2uivr8ua2g.cloudfront.net/wp-subscription-plans.json',
		'myh.test' => 'https://d2mt2uivr8ua2g.cloudfront.net/wp-subscription-plans.json',
		'prod'     => 'https://ds9ywulh7jrls.cloudfront.net/wp-subscription-plans.json',
	);

	/**
	 * Returns all plans translated and mapped to: basic,business,ultimate.
	 *
	 * @retur
	 */
	static function get_plans_transaltions() {
		$subscription_plans = get_transient('wp_subscription_plans');

		if( ! $subscription_plans ) {
			$env                = self::get_env();
			$aws_s3_url         = self::$subscription_plans_per_env[$env];
			$subscription_plans = wp_remote_get($aws_s3_url);

			set_transient( 'wp_subscription_plans', $subscription_plans['body'], 7 * 24 * 60 * 60 );

			return json_decode( $subscription_plans['body'], true );
		}

		return json_decode( $subscription_plans, true );
	}

	/**
	 * Checks if given plan name can be matched, and return: basic|business|ultimate
	 *
	 * @param $plan
	 *
	 * @return string|null
	 */
	static function get_plan_type( $plan ) {
		$subscription_plans = self::get_plans_transaltions();

		if ( ! $subscription_plans[$plan] ) {
			return null;
		}

		return $subscription_plans[$plan];
	}
}
