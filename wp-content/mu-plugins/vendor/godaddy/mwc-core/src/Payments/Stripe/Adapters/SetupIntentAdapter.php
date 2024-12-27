<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\PaymentMethods\PaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\SetupIntent;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

/**
 * An adapter for handling stripe SetupIntent data.
 */
class SetupIntentAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var SetupIntent account */
    protected $source;

    /**
     * Constructor.
     *
     * @param SetupIntent $setupIntent
     */
    public function __construct(SetupIntent $setupIntent)
    {
        $this->source = $setupIntent;
    }

    /**
     * Converts a core SetupIntent model to a Stripe SetupIntent data array.
     *
     * @return array<string, mixed>
     */
    public function convertFromSource() : array
    {
        $data = [
            'usage' => 'off_session',
        ];

        if ($this->source->getCustomer() && $remoteId = $this->source->getCustomer()->getRemoteId()) {
            ArrayHelper::set($data, 'customer', $remoteId);
        }

        return $data;
    }

    /**
     * Converts SetupIntent data from Stripe to source structure.
     *
     * @param array<string, mixed>|null $data
     * @return SetupIntent
     */
    public function convertToSource(array $data = null) : SetupIntent
    {
        if (empty($data)) {
            return $this->source;
        }

        if ($customerRemoteId = ArrayHelper::get($data, 'customer')) {
            $customer = $this->source->getCustomer() ?? Customer::getNewInstance();

            $customer->setRemoteId($customerRemoteId);

            $this->source->setCustomer($customer);
        }

        if ($id = ArrayHelper::get($data, 'id')) {
            $this->source->setId($id);
        }

        if ($status = ArrayHelper::get($data, 'status')) {
            $this->source->setStatus($status);
        }

        if ($clientSecret = ArrayHelper::get($data, 'client_secret')) {
            $this->source->setClientSecret($clientSecret);
        }

        if (($paymentMethodData = ArrayHelper::wrap(ArrayHelper::get($data, 'payment_method'))) && $paymentMethod = PaymentMethodAdapter::getNewInstance()->convertToSource($paymentMethodData)) {
            $this->source->setPaymentMethod($paymentMethod);
        }

        return $this->source;
    }
}
