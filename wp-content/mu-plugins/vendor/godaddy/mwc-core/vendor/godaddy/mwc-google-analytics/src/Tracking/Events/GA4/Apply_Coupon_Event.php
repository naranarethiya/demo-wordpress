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

namespace GoDaddy\WordPress\MWC\GoogleAnalytics\Tracking\Events\GA4;

use GoDaddy\WordPress\MWC\GoogleAnalytics\Helpers\Identity_Helper;
use GoDaddy\WordPress\MWC\GoogleAnalytics\Tracking\Events\GA4_Event;

defined( 'ABSPATH' ) or exit;

/**
 * The "apply coupon" event.
 *
 * @since 3.0.0
 */
class Apply_Coupon_Event extends GA4_Event {


	/** @var string the event ID */
	public const ID = 'apply_coupon';

	/** @var string the event trigger action hook  */
	protected string $trigger_hook = 'woocommerce_applied_coupon';


	/**
	 * @inheritdoc
	 */
	public function get_form_field_title(): string {

		return __( 'Apply Coupon', 'woocommerce-google-analytics-pro' );
	}


	/**
	 * @inheritdoc
	 */
	public function get_form_field_description(): string {

		return __( 'Triggered when a customer applies a coupon.', 'woocommerce-google-analytics-pro' );
	}


	/**
	 * @inheritdoc
	 */
	public function get_default_name(): string {

		return 'apply_coupon';
	}


	/**
	 * @inheritdoc
	 *
	 * @param string $coupon_code the coupon code that is being applied
	 */
	public function track( $coupon_code = null ): void {

		$this->record_via_api( [
			'category'    => 'Coupons',
			'coupon_code' => $coupon_code,
		] );
	}


}
