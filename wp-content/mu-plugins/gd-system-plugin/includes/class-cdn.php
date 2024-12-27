<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}
final class CDN {

	use Helpers;
	private static $file_ext = [ '7z', 'aac', 'ai', 'asf', 'avi', 'bmp', 'bz2', 'css', 'doc', 'docx', 'eot', 'eps', 'fla', 'flv', 'gif', 'gz', 'ico', 'indd', 'jpeg', 'jpg', 'js', 'm4a', 'm4v', 'mkv', 'mov', 'mp3', 'mp4', 'mpeg', 'mpg', 'oga', 'ogg', 'ogv', 'ogx', 'otf', 'pdf', 'png', 'ppt', 'pptx', 'psd', 'rar', 'rtf', 's7z', 'svg', 'svgz', 'tar', 'tgz', 'tiff', 'ttf', 'txt', 'wav', 'webp', 'woff', 'woff2', 'xls', 'xlsx', 'xml', 'zip', 'zipx' ];

	private static $base_url;

	private $pattern;

	public function __construct() {

		self::full_page_cdn_notice();

		if ( self::is_wpaas_v2() || ! self::is_enabled() || ( defined('GD_CDN_FULLPAGE') &&  true === GD_CDN_FULLPAGE ) ) {

			return;

		}

		self::set_cdn_base();

		if ( empty(self::$base_url) ) {
			return;
		}

		add_action( 'init', function () {

			$hosts = [
				filter_input( INPUT_SERVER, 'HTTP_HOST' ),
				wp_parse_url( home_url(), PHP_URL_HOST ),
				wp_parse_url( site_url(), PHP_URL_HOST ),
				GD_TEMP_DOMAIN,
			];

			$preg_quote_callback = function ( $string ) {

				return preg_quote( $string, '~' );

			};

			$this->pattern = sprintf(
				'~(?:(?:https?:)?//(?:%s))/(\S+?\.(?:%s))~i',
				implode( '|', array_map( $preg_quote_callback, array_unique( array_filter( $hosts ) ) ) ),
				implode( '|', array_map( $preg_quote_callback, self::get_file_ext() ) )
			);

		}, 0 );

		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		add_action( 'wp_head',           [ $this, 'wp_head' ], 2 );

		add_filter( 'script_loader_src',     [ $this, 'replace_base_url' ] );
		add_filter( 'style_loader_src',      [ $this, 'replace_base_url' ] );
		add_filter( 'wp_get_attachment_url', [ $this, 'replace_base_url' ] );
		add_filter( 'theme_file_uri',        [ $this, 'replace_base_url' ] );
		add_filter( 'includes_url',          [ $this, 'replace_base_url' ] );

	}

	/**
	 * @return void
	 */
	public static function full_page_cdn_notice() {

		if ( defined( 'GD_CDN_FULLPAGE' ) && true === GD_CDN_FULLPAGE ) {
			new Admin\Notice(
				self::get_notice_text(),
				array( 'notice-success' ),
				'activate_plugins',
				true
			);
		}
	}

	public static function get_cdn_base() {

		if ( false === self::get_dc_full_id() || ! defined( 'GD_TEMP_DOMAIN' ) ) {
			return;
		}

		$dc = strtolower( substr( self::get_dc_full_id(), 0, 2 ) );

		$domain_suffix = '.secureserver.net';
		$temp_domain   = explode( '.', GD_TEMP_DOMAIN );
		$new_host      = null;

		if ( 'myftpupload' === $temp_domain[2] ) {
			$new_host = sprintf( '%scdn1%s', $dc, $domain_suffix );
		}

		if ( 'mwp' === $temp_domain[2] && 'accessdomain' === $temp_domain[3] ) {
			$new_host = sprintf( '%scdn2%s', $dc, $domain_suffix );
		}

		if ( empty( $new_host ) ) {
			return;
		}

		return sprintf( 'https://%s%s.%s', $temp_domain[0], $temp_domain[1], $new_host );
	}

	public static function set_cdn_base() {

		$cdn_base = self::get_cdn_base();

		if ( ! empty( $cdn_base ) ) {
			self::$base_url = $cdn_base;
		}

	}

	/**
	 * Return one of DC strings
	 *
	 * @return false|string
	 */
	public static function get_dc_full_id() {
		if ( defined('GD_DC_ID') ) {
			return GD_DC_ID;
		}
		if ( ! empty($_ENV['WPAAS_POD']) ) {
			return $_ENV['WPAAS_POD'];
		}
		if ( ! empty($_SERVER['WPAAS_POD']) ) {
			return $_SERVER['WPAAS_POD'];
		}

		return false;
	}

	public static function get_file_ext() {

		return (array) apply_filters( 'wpaas_cdn_file_ext', self::$file_ext );

	}

	public static function get_base_url() {

		return apply_filters( 'wpaas_cdn_base_url', self::$base_url );

	}

	public static function is_enabled( $skip_admin_check = false ) {

		global $pagenow;

		$vip         = defined( 'GD_VIP' ) ? GD_VIP : null;
		$temp_domain = defined( 'GD_TEMP_DOMAIN' ) ? GD_TEMP_DOMAIN : null;
		$is_rest     = defined( 'REST_REQUEST' ) ? REST_REQUEST : false;
		$is_nocache  = (bool) filter_input( INPUT_GET, 'nocache' );
		$is_gddebug  = (bool) filter_input( INPUT_GET, 'gddebug' );
		$is_bb       = ! is_null( filter_input( INPUT_GET, 'fl_builder' ) ); // Beaver Builder.
		$cdn_enabled = (bool) apply_filters( 'wpaas_cdn_enabled', defined( 'GD_CDN_ENABLED' ) ? GD_CDN_ENABLED : false );
		$is_admin    = (bool) $skip_admin_check ? false : is_admin();
		$is_login    = ( ! empty( $_SERVER['REQUEST_URI'] ) && wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) === wp_parse_url( wp_login_url(), PHP_URL_PATH ) );
		$is_debug	 = defined('GD_WP_DEBUG_CDN') ? GD_WP_DEBUG_CDN : WP_DEBUG;

		return ( $vip && $temp_domain && $cdn_enabled && self::get_file_ext() && ! $is_debug && ! $is_admin && ! $is_login && ! $is_rest && ! $is_nocache && ! $is_gddebug && ! $is_bb );

	}

	public function template_redirect() {

		if ( ! self::is_enabled() || ! $this->pattern ) {

			return;

		}

		ob_start( function ( $content ) {

			$base_url = self::get_base_url();

			return preg_replace( $this->pattern, "{$base_url}/$1", $content );

		} );

	}

	public function wp_head() {

		if ( ! self::is_enabled() ) {

			return;

		}

		$url = wp_parse_url( self::get_base_url() );

		if ( ! empty( $url['scheme'] ) && ! empty( $url['host'] ) ) {

			printf( // xss ok.
				"<link rel='preconnect' href='%s://%s' crossorigin />\n",
				$url['scheme'],
				$url['host']
			);

		}

	}

	public function replace_base_url( $url ) {

		if ( ! self::is_enabled() || ! $this->pattern ) {

			return $url;

		}

		$base_url = self::get_base_url();

		$url  = preg_replace( $this->pattern, "{$base_url}/$1", $url, -1, $count );
		$time = Plugin::last_cache_flush_date();

		return ( $count && $time ) ? add_query_arg( 'time', $time, $url ) : $url;

	}

	/**
	 * Check the customer type to provide the correct wording for the notice
	 *
	 * @return string
	 */
	public static function get_notice_text() {

		/**
		 * For GoDaddy customer.
		 */
		if (Plugin::is_gd()) {
			$title = __('Your website just got a boost!');
			$message = __('We\'ve updated your GoDaddy CDN with new features that make your site faster and safer.');
			$message .= '<a href="https://godaddy.com/help/what-is-a-cdn-32168" target="_blank" rel="noopener">
                                        ' . __("Learn more") . '
                                </a>';

		} elseif (Plugin::is_reseller_brand()) {
			$title = __('Your website just got a boost!');
			$message = __('We have updated your CDN with new features that make your site faster and safer.');
		} else {
			$title = __('Your website just got a boost!');
			$message = __('We have updated your CDN with new features that make your site faster and safer.');
		}

		$format = '
                <div>
                    <div>
                        <div><strong>'.$title.'</strong></div>
                        ' . $message . '
                    </div>
                </div>
                ';
		return $format;
	}





}
