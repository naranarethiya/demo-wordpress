<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Providers;

use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\AccountGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\CustomersGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\PaymentIntentGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\SetupIntentGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\TransactionsGateway;
use GoDaddy\WordPress\MWC\Payments\Providers\AbstractProvider;
use GoDaddy\WordPress\MWC\Payments\Traits\HasTransactionsTrait;

/**
 * Stripe payment method provider.
 */
class StripeProvider extends AbstractProvider
{
    use HasTransactionsTrait;

    /** @var string provider name */
    protected $name = 'stripe';

    public function __construct()
    {
        $this->label = __('Stripe', 'mwc-core');
        $this->transactionsGateway = TransactionsGateway::class;
    }

    /**
     * Gets a new instance of the Customers gateway.
     *
     * @return CustomersGateway
     */
    public function customers() : CustomersGateway
    {
        return CustomersGateway::getNewInstance();
    }

    /**
     * Gets a new instance of the Payment Intent gateway.
     *
     * @return PaymentIntentGateway
     */
    public function paymentIntent() : PaymentIntentGateway
    {
        return PaymentIntentGateway::getNewInstance();
    }

    /**
     * Gets a new instance of the Setup Intent gateway.
     *
     * @return SetupIntentGateway
     */
    public function setupIntent() : SetupIntentGateway
    {
        return SetupIntentGateway::getNewInstance();
    }

    /**
     * Gets a new instance of the Account gateway.
     *
     * @return AccountGateway
     */
    public function account() : AccountGateway
    {
        return AccountGateway::getNewInstance();
    }
}
