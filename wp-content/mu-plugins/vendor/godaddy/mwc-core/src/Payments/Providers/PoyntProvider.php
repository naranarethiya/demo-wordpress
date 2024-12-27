<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Providers;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\PaymentMethodsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\TransactionsGateway;
use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Payments\Providers\AbstractProvider;
use GoDaddy\WordPress\MWC\Payments\Traits\HasPaymentMethodsTrait;
use GoDaddy\WordPress\MWC\Payments\Traits\HasTransactionsTrait;

/**
 * Poynt payment method provider.
 *
 * @since 2.10.0
 */
class PoyntProvider extends AbstractProvider
{
    use HasPaymentMethodsTrait;
    use HasTransactionsTrait;

    /** @var ApplePayGateway */
    private $applePayGateway;

    /** @var string provider label */
    protected $label;

    /** @var string provider name */
    protected $name = 'poynt';

    /**
     * @throws Exception
     */
    public function __construct()
    {
        // @TODO Apple Pay is not handled by a trait, but follows the pattern set by the other gateway traits here, should we update the handling to a trait of its own in the future {unfulvio 2021-11-23}
        $this->applePayGateway = ApplePayGateway::class;
        $this->paymentMethodsGateway = PaymentMethodsGateway::class;
        $this->transactionsGateway = TransactionsGateway::class;

        $this->label = Worldpay::shouldLoad() ? 'Credit / Debit' : 'GoDaddy Payments';
    }

    /**
     * Gets a new instance of the Apple Pay gateway.
     *
     * @return ApplePayGateway
     */
    public function applePay() : AbstractGateway
    {
        return new $this->applePayGateway();
    }
}
