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
use GoDaddy\WordPress\MWC\GoogleAnalytics\Helpers\Order_Helper;
use GoDaddy\WordPress\MWC\GoogleAnalytics\Tracking;
use GoDaddy\WordPress\MWC\GoogleAnalytics\Tracking\Adapters\Order_Event_Data_Adapter;
use GoDaddy\WordPress\MWC\GoogleAnalytics\Tracking\Adapters\Order_Item_Event_Data_Adapter;
use GoDaddy\WordPress\MWC\GoogleAnalytics\Tracking\Events\GA4_Event;

defined( 'ABSPATH' ) or exit;

/**
 * The "purchase" event.
 *
 * @since 3.0.0
 */
class Purchase_Event extends GA4_Event {


	/** @var string the event ID */
	public const ID = 'purchase';

	/** @var bool whether this is an admin event */
	protected bool $admin_event = true;

	/** @var bool whether this is a GA4 recommended event */
	protected bool $recommended_event = true;


	/**
	 * @inheritdoc
	 */
	public function get_form_field_title(): string {

		return __( 'Purchase', 'woocommerce-google-analytics-pro' );
	}


	/**
	 * @inheritdoc
	 */
	public function get_form_field_description(): string {

		return __( 'Triggered when a customer completes a purchase.', 'woocommerce-google-analytics-pro' );
	}


	/**
	 * @inheritdoc
	 */
	public function get_default_name(): string {

		return 'purchase';
	}


	/**
	 * @inheritdoc
	 */
	public function register_hooks(): void {

		add_action( 'woocommerce_order_status_on-hold', [ $this, 'purchase_on_hold' ] );

		add_action( 'woocommerce_payment_complete', [ $this, 'track' ] );
		add_action( 'woocommerce_order_status_processing', [ $this, 'track' ] );
		add_action( 'woocommerce_order_status_completed', [ $this, 'track' ] );

		// catch orders processed through payment gateways such as COD
		add_action( 'woocommerce_thankyou', [ $this, 'track' ] );
	}


	/**
	 * Checks 'On Hold' orders to see if we should record a completed transaction or not.
	 *
	 * Currently, the only reason we might want to do this is if PayPal returns On Hold
	 * from the IPN. This is usually due to an email address mismatch, and the payment has
	 * technically already been captured at this point.
	 *
	 * @see https://github.com/skyverge/wc-plugins/issues/2332
	 *
	 * @since 3.0.0
	 *
	 * @param int $order_id
	 */
	public function purchase_on_hold( $order_id ): void {

		$order = wc_get_order( $order_id );

		if ('paypal' === $order->get_payment_method()) {
			$this->track( $order_id );
		}
	}


	/**
	 * @inheritdoc
	 *
	 * @param int $order_id the order ID
	 */
	public function track( $order_id = null ): void {

		/**
		 * Filters whether the completed purchase event should be tracked or not.
		 *
		 * @since 1.1.5
		 *
		 * @see Tracking\Events\Universal_Analytics\Completed_Purchase_Event::track() for same filter reference
		 *
		 * @param bool $do_not_track true to not track the event, false otherwise
		 * @param int $order_id the order ID
		 */
		if ( true === apply_filters( 'wc_google_analytics_pro_do_not_track_completed_purchase', false, $order_id ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		// can't track an order that doesn't exist
		if ( ! $order instanceof \WC_Order) {
			return;
		}

		/**
		 * Filters whether the purchase event should be tracked for a given order.
		 *
		 * Normally only paid orders are tracked, by default.
		 *
		 * @since 3.0.8
		 *
		 * @see Tracking\Events\Universal_Analytics\Completed_Purchase_Event::track() for same filter reference
		 *
		 * @param bool $order_is_paid
		 * @param \WC_Order $order
		 */
		if ( false === apply_filters( 'wc_google_analytics_track_completed_purchase_for_order', $order->is_paid(), $order ) ) {
			return;
		}

		// bail if tracking is disabled but not if the status is being manually changed by the admin
		if ( ! Tracking::is_tracking_enabled_for_user_role( $order->get_customer_id() ) ) {
			return;
		}

		// don't track order when its already tracked
		if ( Order_Helper::is_order_tracked( $order_id ) ) {
			return;
		}

		// don't track order when we haven't tracked the 'placed' event - this prevents tracking old orders that were placed before GA Pro was active
		if ( ! Order_Helper::was_order_placed_while_ga_enabled( $order_id ) ) {
			return;
		}

		$properties = array_merge(
			[ 'category' => 'Checkout' ],
			( new Order_Event_Data_Adapter( $order ) )->convert_from_source(),
			Order_Helper::get_order_ga_session_params( $order->get_id() )
		);

		$identities = Order_Helper::get_order_identities( $order );

		if ( $this->record_via_api( $properties, $identities ) ) {

			// mark order as tracked
			Order_Helper::set_order_tracked( $order->get_id() );
		}
	}


}
