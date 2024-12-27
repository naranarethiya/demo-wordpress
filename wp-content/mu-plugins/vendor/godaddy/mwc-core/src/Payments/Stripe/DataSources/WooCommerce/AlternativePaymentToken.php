<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataSources\WooCommerce;

use WC_Payment_Token;

/**
 * Compatibility class representing Stripe's alt payment methods.
 */
class AlternativePaymentToken extends WC_Payment_Token
{
    /** @var string type */
    public $type = 'mwc_stripe';
}
