<?php


namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Diagnostics {

	public function __construct()
	{
		if( !defined('UTF8_BOM') ){
			define ('UTF8_BOM', chr(0xEF) . chr(0xBB) . chr(0xBF));
		}
	}

	/**
	 * Get list of all plugins, mu-plugins and drop-in plugins
	 * @return array Returns list of plugins
	 */
	public function get_plugins_data(){
		$plugins = get_plugins();
		$mu_plugins = get_mu_plugins();
		$dropin_plugins = get_dropins();
		$plugins_data = [];

		foreach( $plugins as $slug => $plugin ){
			$plugins_data['author-plugins'][] = [
				"name" => $plugin["Name"],
				"slug" => $slug,
				"author" => $plugin["Author"],
				"version" => $plugin["Version"],
				"plugin_active" => is_plugin_active($slug)
			];
		}

		foreach($mu_plugins as $slug => $plugin){
			$plugins_data['mu-plugins'][] = [
				"name" => $plugin["Name"],
				"slug" => $slug,
				"version" => $plugin["Version"],
			];
		}

		foreach($dropin_plugins as $slug => $plugin){
			$plugins_data['drop-ins'][] = [
				"name" => $plugin["Name"],
				"slug" => $slug
			];
		}

		return $plugins_data;
	}

	/**
	 * * Get list of all themes
	 * @return array Returns list of themes
	 */
	public function get_themes_data(){

		$themes = wp_get_themes();
		$themes_data = [];

		foreach ( $themes as $theme ){
			$themes_data[] = [
				"name" => $theme->get('Name'),
				"theme_uri" => $theme->get('ThemeURI'),
				"status" => $theme->get('Status'),
				"author" => $theme->get('Author'),
				"version" => $theme->get('Version')
			];
		}

		return $themes_data;
	}

	public function get_config_data() {
		$gd_config = $this->find_readable_path([ABSPATH . 'gd-config.php', WPMU_PLUGIN_DIR . '/bin/gd-config.php']);
		$wp_config = $this->find_readable_path([ABSPATH . 'wp-config.php']);
		$htaccess = $this->find_readable_path([ABSPATH . '.htaccess']);
		$user_ini = $this->find_readable_path([ABSPATH . '.user.ini']);

		$configs = array_filter(["gd_config" => $gd_config, "wp_config" => $wp_config, "htaccess" => $htaccess, "user_ini" => $user_ini]);
		$file_content = array();

		foreach( $configs as $file_name => $path ){
			$config_content = file_get_contents( $path );
			$first3 = substr($config_content, 0, 3);
			$is_bom = false;

			if( UTF8_BOM === $first3){
				$is_bom = true;
			}

			$file_content[$file_name]['content'] = $config_content;
			$file_content[$file_name]['is_bom'] = $is_bom;
		}

		return $file_content;
	}

	public function get_diagnostics_data(){
		return [
			"plugins" => $this->get_plugins_data(),
			"themes" => $this->get_themes_data(),
			"config_data"=> $this->get_config_data()
		];
	}

	/**
	 * Return the first readable path from an array.
	 *
	 * @param  array $paths
	 *
	 * @return string|false
	 */
	private function find_readable_path( array $paths ) {
		foreach ( $paths as $path ) {
			if ( is_readable( $path ) ) {
				return $path;
			}
		}

		return "";
	}
}