<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\PaymentMethods\PaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\PaymentIntent;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

/**
 * An adapter for handling stripe payment intent data.
 */
class PaymentIntentAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var PaymentIntent paymentIntent */
    protected $source;

    /**
     * Constructor.
     *
     * @param PaymentIntent|null $paymentIntent
     */
    public function __construct(?PaymentIntent $paymentIntent = null)
    {
        $this->source = $paymentIntent ?? PaymentIntent::getNewInstance();
    }

    /**
     * Converts the source payment intent to a data array.
     *
     * @return array<string, mixed>
     */
    public function convertFromSource() : array
    {
        $data = [];

        if ($amount = $this->source->getAmount()) {
            ArrayHelper::set($data, 'amount', $amount);
        }

        if ($currency = $this->source->getCurrency()) {
            ArrayHelper::set($data, 'currency', strtolower($currency));
        }

        if (($customer = $this->source->getCustomer()) && $customer->getRemoteId()) {
            ArrayHelper::set($data, 'customer', $customer->getRemoteId());
        }

        if ($captureMethod = $this->source->getCaptureMethod()) {
            ArrayHelper::set($data, 'capture_method', $captureMethod);
        }

        ArrayHelper::set($data, 'setup_future_usage', $this->source->getSetupFutureUsage());
        ArrayHelper::set($data, 'metadata', $this->source->getMetaData());

        return $this->convertShippingData($data);
    }

    /**
     * Converts the source's shipping dress into Stripe API shipping data.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function convertShippingData(array $data) : array
    {
        $addressData = AddressAdapter::getNewInstance($this->source->getShippingAddress())->convertFromSource();

        // don't add shipping values until we have the minimum we need
        if (
            ! ArrayHelper::get($addressData, 'name') ||
            ! ArrayHelper::get($addressData, 'address.line1') ||
            ! ArrayHelper::get($addressData, 'address.postal_code')
        ) {
            return $data;
        }

        ArrayHelper::set($data, 'shipping', $addressData);

        return $data;
    }

    /**
     * Converts payment intent data from Stripe to source structure.
     *
     * @param array<string, mixed>|null $data
     * @return PaymentIntent
     */
    public function convertToSource(array $data = null) : PaymentIntent
    {
        if (empty($data)) {
            return $this->source;
        }

        $this->source->setId(ArrayHelper::get($data, 'id', ''));
        $this->source->setAmount(ArrayHelper::get($data, 'amount', 0));
        $this->source->setClientSecret(ArrayHelper::get($data, 'client_secret', ''));
        if ($currency = ArrayHelper::get($data, 'currency')) {
            $this->source->setCurrency(strtoupper(TypeHelper::string($currency, '')));
        }

        if ($customerRemoteId = ArrayHelper::get($data, 'customer')) {
            $customer = $this->source->getCustomer() ?? Customer::getNewInstance();

            $customer->setRemoteId($customerRemoteId);

            $this->source->setCustomer($customer);
        }

        $this->source->setCaptureMethod(ArrayHelper::get($data, 'capture_method', ''));
        $this->source->setCreated(ArrayHelper::get($data, 'created'));
        $this->source->setSetupFutureUsage((string) ArrayHelper::get($data, 'setup_future_usage', ''));
        $this->source->setStatus(ArrayHelper::get($data, 'status'));

        if (($paymentMethodData = ArrayHelper::wrap(ArrayHelper::get($data, 'payment_method'))) && $paymentMethod = PaymentMethodAdapter::getNewInstance()->convertToSource($paymentMethodData)) {
            $this->source->setPaymentMethod($paymentMethod);
        }

        return $this->source;
    }
}
