<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataStores\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\PaymentMethodDataStore as BasePaymentMethodDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\PaymentMethods\AlternativePaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataSources\WooCommerce\AlternativePaymentToken;

class PaymentMethodDataStore extends BasePaymentMethodDataStore
{
    /**
     * Sets the supported payment methods adapters.
     *
     * @param array<string, class-string> $paymentMethodAdapters
     * @return $this
     */
    protected function setPaymentMethodAdapters(array $paymentMethodAdapters) : BasePaymentMethodDataStore
    {
        parent::setPaymentMethodAdapters($paymentMethodAdapters);

        if (! ArrayHelper::exists($this->paymentMethodAdapters, 'Stripe')) {
            $this->paymentMethodAdapters['Stripe'] = AlternativePaymentMethodAdapter::class;
        }

        return $this;
    }

    /**
     * Gets a matching WooCommerce payment token class to the given token type.
     *
     * @param string $tokenType
     *
     * @return string
     */
    protected function getMatchingWooTokenClass(string $tokenType) : string
    {
        return ($tokenType === 'mwc_stripe')
                ? AlternativePaymentToken::class
                : parent::getMatchingWooTokenClass($tokenType);
    }
}
