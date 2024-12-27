<?php
/**
 * The SiteDesign class.
 *
 * @package GoDaddy
 */

namespace GoDaddy\WordPress\Plugins\Launch\PublishGuide\GuideItems;

/**
 * The SiteDesign class.
 */
class SiteDesign extends GuideItemAbstract {
	/**
	 * Determins if the guide item should be enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		if ( ! empty( get_option( 'coblocks_site_design_controls_enabled' ) ) && $this->has_go_active() ) {
			return true;
		}

		if ( wp_is_block_theme() ) {
			return true;
		}

		return false;
	}

	/**
	 * Return if the guide item has been completed.
	 *
	 * @return bool
	 */
	public function is_complete() {

		if ( wp_is_block_theme() ) {

			$theme_slug = get_option( 'stylesheet' );

			$block_styles_query = new \WP_Query(
				array(
					'post_type'      => 'wp_global_styles',
					'name'           => "wp-global-styles-{$theme_slug}",
					'post_status'    => 'publish',
					'posts_per_page' => 1,
				)
			);

			if ( ! empty( $block_styles_query->posts ) ) {
				$post_date     = strtotime( $block_styles_query->posts[0]->post_date );
				$modified_date = strtotime( $block_styles_query->posts[0]->post_modified );

				return $modified_date > $post_date;
			}

			return false;

		}

		if ( get_option( $this->option_name() ) ) {
			return true;
		}

		$conditions = array(
			$this->has_theme_mods(),
		);

		$has_incomplete = array_filter(
			$conditions,
			function( $val ) {
				return empty( $val );
			}
		);

		return empty( $has_incomplete );
	}

	/**
	 * Returns the option_name of the GuideItem used in the wp_options table.
	 *
	 * @return string
	 */
	public function option_name() {
		return 'gdl_pgi_site_design';
	}

	/**
	 * Returns the milestone name of the GuideItem used in the nux api.
	 *
	 * @return string
	 */
	public function milestone_name() {
		return 'site-design';
	}

	/**
	 * Filters ignores keys from theme_mods array.
	 *
	 * @param array $saved_theme_mods The theme mods.
	 * @param array $ignored_keys The keys to ignore.
	 *
	 * @return array
	 */
	private function filter_ignored_keys( $saved_theme_mods, $ignored_keys ) {
		return array_filter(
			$saved_theme_mods,
			function ( $key ) use ( $ignored_keys ) {
				return ! empty( $key ) && ! in_array( $key, $ignored_keys, true );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Determine if the Go theme has been customized.
	 *
	 * @return bool
	 */
	private function has_theme_mods() {

		$saved_theme_mods = get_theme_mods();

		$default_ignored_keys = array(
			'nav_menu_locations',
			'custom_css_post_id',
			'custom_logo',
		);

		$theme_mods = $this->filter_ignored_keys( $saved_theme_mods, $default_ignored_keys );

		$wpnux_export_data = json_decode( get_option( 'wpnux_export_data', '{}' ), true );

		if (
			! empty( $wpnux_export_data ) &&
			! empty( $wpnux_export_data['content'] ) &&
			! empty( $wpnux_export_data['content']['theme_mods'] )
		) {
			$template_nux_data = $wpnux_export_data['content']['theme_mods'];

			$filtered_template_theme_mods = $this->filter_ignored_keys( $template_nux_data, $default_ignored_keys );

			$theme_mods = array_diff(
				array_values( array_map( 'wp_json_encode', $theme_mods ) ),
				array_values( array_map( 'wp_json_encode', $filtered_template_theme_mods ) )
			);
		}

		return ! empty( $theme_mods );
	}

	/**
	 * Determine if the Go theme is active.
	 *
	 * @return bool
	 */
	private function has_go_active() {
		return 'Go' === wp_get_theme()->get( 'Name' );
	}
}
