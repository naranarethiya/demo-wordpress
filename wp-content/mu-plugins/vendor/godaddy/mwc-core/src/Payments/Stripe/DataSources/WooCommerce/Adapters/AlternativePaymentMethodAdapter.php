<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataSources\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataSources\WooCommerce\AlternativePaymentToken;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\AlternativePaymentMethod;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\StripeGateway;

class AlternativePaymentMethodAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var AlternativePaymentToken */
    protected $source;

    /**
     * AlternativePaymentMethodAdapter constructor.
     *
     * @param AlternativePaymentToken $paymentToken
     */
    public function __construct(AlternativePaymentToken $paymentToken)
    {
        $this->source = $paymentToken;
    }

    /**
     * Converts the AlternativePaymentMethod from source.
     *
     * @return AlternativePaymentMethod
     */
    public function convertFromSource() : AlternativePaymentMethod
    {
        $type = (string) $this->source->get_meta('type');

        /** @var AlternativePaymentMethod */
        $alternativePaymentMethod = AlternativePaymentMethod::getNewInstance()
            ->setType($type)
            ->setLastFour((string) $this->source->get_meta('lastFour'))
            ->setLabel(ArrayHelper::get(StripeGateway::getKnownPaymentMethods(), $type, ''))
            ->setId($this->source->get_id())
            ->setProviderName($this->source->get_gateway_id())
            ->setRemoteId($this->source->get_token())
            ->setCustomerId((string) $this->source->get_user_id());

        return $alternativePaymentMethod;
    }

    /**
     * Converts an AlternativePaymentMethod to source.
     *
     * @param AlternativePaymentMethod|null $paymentMethod
     * @return AlternativePaymentToken
     */
    public function convertToSource(AlternativePaymentMethod $paymentMethod = null) : AlternativePaymentToken
    {
        if (! empty($paymentMethod)) {
            $this->source->set_id((int) $paymentMethod->getId());
            $this->source->set_gateway_id($paymentMethod->getProviderName());
            $this->source->set_token((string) $paymentMethod->getRemoteId());
            $this->source->set_user_id((int) $paymentMethod->getCustomerId());

            $this->source->update_meta_data('type', (string) $paymentMethod->getType());
            $this->source->update_meta_data('lastFour', (string) $paymentMethod->getLastFour());
        }

        return $this->source;
    }
}
