<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\PaymentMethods;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

/**
 * An adapter for handling stripe payment intent data.
 */
class PaymentMethodAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var AbstractPaymentMethod|null */
    protected $source;

    /**
     * Constructor.
     *
     * @param AbstractPaymentMethod|null $paymentMethod
     */
    public function __construct(AbstractPaymentMethod $paymentMethod = null)
    {
        $this->source = $paymentMethod;
    }

    /**
     * This method is no-op.
     */
    public function convertFromSource() : void
    {
        // no-op
    }

    /**
     * Converts the given data to a payment method object.
     *
     * @param array<string, mixed>|null $data
     *
     * @return AbstractPaymentMethod|null
     */
    public function convertToSource(array $data = null) : ?AbstractPaymentMethod
    {
        if (empty($data)) {
            return null;
        }

        $type = ArrayHelper::get($data, 'type', '');

        if ($typeAdapter = $this->getAdapterForType($type)) {
            $this->source = $typeAdapter->convertToSource(ArrayHelper::get($data, $type)); // @phpstan-ignore-line
        }

        if ($this->source && $remoteId = ArrayHelper::get($data, 'id')) {
            $this->source->setRemoteId($remoteId);
        }

        return $this->source;
    }

    /**
     * Gets the adapter to use for the given payment method type.
     *
     * @param string $type
     *
     * @return DataSourceAdapterContract|null
     */
    protected function getAdapterForType(string $type) : ?DataSourceAdapterContract
    {
        switch ($type) {
            case 'card':
                return CardPaymentMethodAdapter::getNewInstance($this->source);

            default:
                return empty($type) ? null : AlternativePaymentMethodAdapter::getNewInstance($type);
        }
    }
}
