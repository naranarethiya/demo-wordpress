<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\PaymentMethods;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\AlternativePaymentMethod;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\StripeGateway;

/**
 * An adapter for handling stripe alternative payment method data.
 */
class AlternativePaymentMethodAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var string */
    protected $type;

    /**
     * Constructor.
     *
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * This method is no-op.
     */
    public function convertFromSource() : void
    {
        // no-op
    }

    /**
     * Converts the given data to a card payment method.
     *
     * @param array<string, mixed>|null $data
     *
     * @return AlternativePaymentMethod
     */
    public function convertToSource(array $data = null) : AlternativePaymentMethod
    {
        $paymentMethod = new AlternativePaymentMethod();

        $paymentMethod->setType($this->type);

        $paymentMethod->setLabel(ArrayHelper::get(StripeGateway::getKnownPaymentMethods(), $this->type, ''));

        /* @phpstan-ignore-next-line */
        if ($lastFour = ArrayHelper::get($data, 'last4')) {
            $paymentMethod->setLastFour($lastFour);
        }

        return $paymentMethod;
    }
}
