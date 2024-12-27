<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataSources\WooCommerce\Adapters\SessionValue;

use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\SessionValue\CheckoutAdapter as CommonCheckoutAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Checkout as CommonCheckout;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;

/**
 * Checkout adapter.
 */
class CheckoutAdapter extends CommonCheckoutAdapter
{
    use CanGetNewInstanceTrait;

    /** @var string override the default method so that an instance of core Checkout is returned */
    protected $checkoutClass = Checkout::class;

    /**
     * Converts checkout source data into a checkout object.
     *
     * @return Checkout
     * @throws BaseException
     */
    public function convertFromSource() : CommonCheckout
    {
        /** @var Checkout $checkout */
        $checkout = parent::convertFromSource();

        if (! empty($wcSessionId = ArrayHelper::get($this->source, 'session_id'))) {
            if (! is_numeric($wcSessionId)) {
                throw new BaseException('Session ID must be numeric.');
            }

            $checkout->setWcSessionId($wcSessionId);
        }

        return $checkout;
    }
}
