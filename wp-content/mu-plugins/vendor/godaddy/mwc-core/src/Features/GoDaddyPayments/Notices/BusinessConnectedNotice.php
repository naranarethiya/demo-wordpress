<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Interceptors\AutoConnectInterceptor;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;

class BusinessConnectedNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_SUCCESS;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-connected';

    public function __construct()
    {
        if (Poynt::isEnabled()) {
            $this->setTitle(__('GoDaddy Payments successfully enabled!', 'mwc-core'));
            $this->setContent(__('GoDaddy Payments is now available to your customers at checkout.', 'mwc-core'));
        } else {
            $this->setTitle(__('GoDaddy Payments is now connected to your store!', 'mwc-core'));
            $this->setButtonUrl(OnboardingEventsProducer::getEnablePaymentMethodUrl());
            $this->setButtonText(__('Enable GoDaddy Payments', 'mwc-core'));
            $this->setContent(__('Enable the payment method to add it to your checkout.', 'mwc-core'));
        }

        $this->setRenderCondition(fn () => ! AutoConnectInterceptor::wasConnected());
    }
}
