<?php

namespace GoDaddy\WordPress\MWC\GoogleAnalytics\Tests\Admin;

use GoDaddy\WordPress\MWC\GoogleAnalytics\Plugin;
use SkyVerge\Lumiere\Tests;
use function GoDaddy\WordPress\MWC\GoogleAnalytics\wc_google_analytics_pro;

/**
 * Tests for the plugin action links.
 */
class PluginPageLinksCest extends Tests\Admin\PluginPageLinksCest {


	/**
	 * Gets the plugin instance.
	 *
	 * @return \GoDaddy\WordPress\MWC\GoogleAnalytics\Plugin
	 */
	protected function get_plugin() {

		return wc_google_analytics_pro();
	}


}
