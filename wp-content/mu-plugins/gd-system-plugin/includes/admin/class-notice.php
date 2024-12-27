<?php

namespace WPaaS\Admin;
use WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Notice {

	/**
	 * Message to display in the notice.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Array of classes for the notice.
	 *
	 * @var array
	 */
	private $classes;

	/**
	 * Required user capability.
	 *
	 * @var string
	 */
	private $cap;

	/**
	 * Should this be permanent dismiss
	 *
	 * @var bool
	 */
	private $dismiss;

	/**
	 * Class constructor.
	 *
	 * @param string $message
	 * @param array  $classes (optional)
	 * @param string $cap     (optional)
	 */
	public function __construct( $message, array $classes = [ 'updated' ], $cap = 'activate_plugins', $dismiss = false ) {

		/**
		 * Filter the admin notice message.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		$this->message = (string) apply_filters( 'wpaas_admin_notice_message', $message );

		if ( empty( $message ) ) {

			return;

		}
		$this->dismiss = $dismiss;

		/**
		 * Filter the admin notice classes.
		 *
		 * @since 2.0.0
		 *
		 * @var array
		 */
		$this->classes = (array) apply_filters( 'wpaas_admin_notice_classes', $classes );

		/**
		 * Filter the user cap required to view the admin notice.
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		$this->cap = (string) apply_filters( 'wpaas_admin_notice_cap', $cap );

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );


		add_action( 'admin_notices', [ $this, 'display' ], -PHP_INT_MAX );
		add_action( 'wpaas_admin_notices', [ $this, 'display' ], -PHP_INT_MAX );
	}

	/**
	 * Display admin notice.
	 *
	 * @action admin_notices
	 */
	public function display() {

		if ( ! current_user_can( $this->cap ) || ! $this->message ) {

			return;

		}
		$dismiss_id = md5($this->message);

		if (  get_option( "wpaas_dismissed_".md5($this->message) ) ) {
            return;
		}
		?>
		<div class="wpaas-notice notice <?php echo esc_attr( implode( ' ', $this->classes ) );
		if ( $this->dismiss ) {
			echo ' is-dismissible" data-dismiss-id="' . $dismiss_id;
		} ?>">

			<p><?php echo wp_kses_post( $this->message ); ?></p>
		</div>
		<?php

	}

	/**
	 * @return void
	 */
    public function admin_enqueue_scripts() {
		wp_enqueue_script( 'wpaas-notice', Plugin::assets_url( 'js/wpaas-notice.js' ), ['wp-api-request']);
	}

}
