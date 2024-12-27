<?php
/**
 * The PageMetaServiceProvider class.
 *
 * @package GoDaddy
 */

namespace GoDaddy\WordPress\Plugins\Launch;

use GoDaddy\WordPress\Plugins\Launch\ServiceProvider;

/**
 * PageMetaServiceProvider class.
 */
class PageMetaServiceProvider extends ServiceProvider {
	/**
	 * This method will be used for hooking into WordPress with actions/filters.
	 *
	 * @return void
	 */
	public function boot() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
			add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ) );
			add_action( 'wp_head', array( $this, 'add_meta_tags' ) );
		}
	}

	/**
	 * This method will be used to bind things to the container.
	 *
	 * @return void
	 */
	public function register() {}

	/**
	 * Filters the document title.
	 *
	 * @param string $title The document title. Default empty string.
	 *
	 * @return string The document title.
	 */
	public function filter_document_title( $title ) {
		$post_meta_title = get_post_meta( get_the_ID(), '_yoast_wpseo_title', true );

		if ( ! empty( $post_meta_title ) ) {
			return $post_meta_title;
		}

		return $title;
	}

	/**
	 * Prints data in the head tag on the front end.
	 */
	public function add_meta_tags() {
		$post_meta_description = get_post_meta( get_the_ID(), '_yoast_wpseo_metadesc', true );

		if ( ! empty( $post_meta_description ) ) {
			echo '<meta name="description" content="' . esc_attr( $post_meta_description ) . '">' . PHP_EOL;
		}
	}
}
