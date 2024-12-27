<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidTransactionStatusException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\MissingTransactionStatusException;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\PaymentIntent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Payments\Contracts\TransactionStatusContract;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\DeclinedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\HeldTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\PendingTransactionStatus;

/**
 * An adapter for handling Payment Transaction and Payment Intent Data.
 */
class TransactionPaymentIntentAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var PaymentTransaction paymentTransaction */
    protected $source;

    /**
     * Constructor.
     *
     * @param PaymentTransaction|null $paymentTransaction
     */
    public function __construct(?PaymentTransaction $paymentTransaction = null)
    {
        $this->source = $paymentTransaction ?? PaymentTransaction::getNewInstance();
    }

    /**
     * Converts the source payment transaction to a payment intent.
     *
     * @param PaymentIntent|null $paymentIntent
     * @return PaymentIntent
     */
    public function convertFromSource(?PaymentIntent $paymentIntent = null) : PaymentIntent
    {
        $paymentIntent = $paymentIntent ?? PaymentIntent::getNewInstance();

        if ($currencyAmount = $this->source->getTotalAmount()) {
            $paymentIntent->setCurrency($currencyAmount->getCurrencyCode());
            $paymentIntent->setAmount(($currencyAmount->getAmount()));
        }

        if ($customer = $this->source->getCustomer()) {
            $paymentIntent->setCustomer($customer);
        }

        if ($order = $this->source->getOrder()) {
            /** @var Order $order */
            $paymentIntent = $this->convertOrderFromSource($paymentIntent, $order);
        }

        $paymentIntent->setCaptureMethod($this->source->isAuthOnly() ? 'manual' : 'automatic');

        if ($this->source->shouldTokenize()) {
            $paymentIntent->setSetupFutureUsage('off_session');
        }

        if ($paymentMethod = $this->source->getPaymentMethod()) {
            $paymentIntent->setPaymentMethod($paymentMethod);
        }

        return $paymentIntent;
    }

    /**
     * Converts the given order data into the payment intent data.
     *
     * @param PaymentIntent $paymentIntent
     * @param Order $order
     *
     * @return PaymentIntent
     */
    protected function convertOrderFromSource(PaymentIntent $paymentIntent, Order $order) : PaymentIntent
    {
        $paymentIntent->setShippingAddress($order->getShippingAddress());

        $metaData = [
            'order_id' => $order->getId(),
        ];

        if ($orderNumber = $order->getNumber()) {
            ArrayHelper::set($metaData, 'order_number', $orderNumber);
        }

        return $paymentIntent->setMetaData($metaData);
    }

    /**
     * Converts payment intent to source payment transaction.
     *
     * @param PaymentIntent|null $paymentIntent
     * @return PaymentTransaction
     * @throws Exception
     */
    public function convertToSource(?PaymentIntent $paymentIntent = null) : PaymentTransaction
    {
        if ($paymentIntent instanceof PaymentIntent) {
            $this->source->setTotalAmount(
                CurrencyAmount::getNewInstance()
                    ->setAmount($paymentIntent->getAmount() ?? 0)
                    ->setCurrencyCode($paymentIntent->getCurrency() ?? 'USD')
            );
            $this->source->setCustomer($paymentIntent->getCustomer() ?? Customer::getNewInstance());
            $this->source->setAuthOnly(($paymentIntent->getCaptureMethod() === 'manual'));
            $this->source->setShouldTokenize(($paymentIntent->getSetupFutureUsage() === 'off_session'));

            if ($id = $paymentIntent->getId()) {
                $this->source->setRemoteId($id);
            }
            if ($created = $paymentIntent->getCreated()) {
                $this->source->setCreatedAt((new DateTime())->setTimestamp($created));
            }
            $this->source->setStatus($this->convertStatus($paymentIntent->getStatus()));

            if ($paymentMethod = $paymentIntent->getPaymentMethod()) {
                $this->source->setPaymentMethod($paymentMethod);
            }
        }

        return $this->source;
    }

    /**
     * Converts the payment intent status into the appropriate TransactionStatus object.
     *
     * @param string|null $status
     *
     * @return TransactionStatusContract
     * @throws MissingTransactionStatusException|InvalidTransactionStatusException
     */
    protected function convertStatus(?string $status) : TransactionStatusContract
    {
        if (! $status) {
            throw new MissingTransactionStatusException('The payment intent status is missing');
        }

        switch ($status) {
            case 'requires_payment_method':
                return new DeclinedTransactionStatus();
            case 'requires_confirmation':
            case 'canceled':
                return new PendingTransactionStatus();
            case 'requires_action':
            case 'processing':
            case 'requires_capture':
                return new HeldTransactionStatus();
            case 'succeeded':
                return new ApprovedTransactionStatus();
            default:
                throw new InvalidTransactionStatusException('The payment intent status is invalid');
        }
    }
}
