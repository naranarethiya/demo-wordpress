<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * An adapter for handling stripe payment intent data.
 */
class CreatePaymentIntentAdapter extends PaymentIntentAdapter
{
    /**
     * Converts the source payment intent to a data array.
     *
     * @return array
     */
    public function convertFromSource() : array
    {
        $data = parent::convertFromSource();

        // always use automatic payments
        ArrayHelper::set($data, 'automatic_payment_methods.enabled', true);

        // Stripe doesn't allow sending empty future usage params when creating
        if (! ArrayHelper::get($data, 'setup_future_usage')) {
            ArrayHelper::remove($data, 'setup_future_usage');
        }

        return $data;
    }
}
