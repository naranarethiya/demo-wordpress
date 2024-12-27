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

namespace GoDaddy\WordPress\MWC\GoogleAnalytics\API\Admin_API;

use stdClass;

defined( 'ABSPATH' ) or exit;

/**
 * Handles responses from the Google Analytics Admin API Properties routes.
 *
 * @link https://developers.google.com/analytics/devguides/config/admin/v1/rest/v1beta/properties
 *
 * @since 3.0.0
 */
class Properties_Response extends Response {


	/**
	 * Returns a GA4 property object
	 *
	 * @link https://developers.google.com/analytics/devguides/config/admin/v1/rest/v1beta/properties/get
	 *
	 * @since 3.0.0
	 *
	 * @return stdClass
	 */
	public function get(): stdClass {

		return $this->response_data;
	}


}
