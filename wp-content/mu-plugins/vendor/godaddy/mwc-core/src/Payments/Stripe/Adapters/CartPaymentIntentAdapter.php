<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CurrencyRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\PaymentIntent;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CustomerAdapter;
use WC_Cart;

/**
 * An adapter for handling WC_Cart and Payment Intent Data.
 */
class CartPaymentIntentAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var WC_Cart */
    protected $source;

    /**
     * Constructor.
     *
     * @param WC_Cart $cart
     */
    public function __construct(WC_Cart $cart)
    {
        $this->source = $cart;
    }

    /**
     * Converts the source cart to a payment intent.
     *
     * @param PaymentIntent|null $paymentIntent
     * @return PaymentIntent
     */
    public function convertFromSource(?PaymentIntent $paymentIntent = null) : PaymentIntent
    {
        $paymentIntent = $paymentIntent ?? PaymentIntent::getNewInstance();

        $paymentIntent->setAmount(CurrencyRepository::getStripeAmount(ArrayHelper::get($this->source->get_totals(), 'total', '0')));
        $paymentIntent->setCurrency(WooCommerceRepository::getCurrency());
        $paymentIntent->setCaptureMethod(Configuration::get('payments.stripe.transactionType') === 'authorization' ? 'manual' : 'automatic');

        $customer = CustomerAdapter::getNewInstance($this->source->get_customer())->convertFromSource();

        $paymentIntent->setCustomer($customer);
        $paymentIntent->setShippingAddress($customer->getShippingAddress());

        return $paymentIntent;
    }

    /**
     * @note NO-OP
     *
     * @return void
     */
    public function convertToSource() : void
    {
    }
}
