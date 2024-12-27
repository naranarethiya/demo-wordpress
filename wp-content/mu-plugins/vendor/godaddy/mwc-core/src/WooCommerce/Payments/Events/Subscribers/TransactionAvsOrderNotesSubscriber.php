<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\InvalidTransactionAvsEvent;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Payments;

/**
 * This subscriber add order notes for transactions with fail AVS checks.
 *
 * {@see InvalidTransactionAvsEvent} for more context.
 */
class TransactionAvsOrderNotesSubscriber extends TransactionOrderNotesSubscriber
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function getNotes(EventContract $event) : array
    {
        $notes = [];

        /** @var InvalidTransactionAvsEvent $event */
        $paymentMethod = $event->getTransaction()->getPaymentMethod();

        if ($paymentMethod instanceof CardPaymentMethod) {
            /* translators: Placeholders: %1s - the provider label, %2s - the payment method brand label, %3$s - the last four digits of the customer's credit card */
            $notes[] = sprintf(__('%1s transaction declined due to billing address mismatch for %2s ending in %3$s.', 'mwc-core'),
                $this->getProviderLabel($event->getTransaction()->getProviderName()),
                $paymentMethod->getBrand() ? $paymentMethod->getBrand()->getLabel() : __('Card', 'mwc-core'),
                $paymentMethod->getLastFour()
            );
        }

        return $notes;
    }

    /**
     * Gets the provider label for a given provider name.
     *
     * @param string|null $providerName
     * @return string
     * @throws Exception if provider not found
     */
    protected function getProviderLabel(?string $providerName) : string
    {
        $payments = Payments::getInstance();

        return $payments->provider($providerName ?? '')->getLabel();
    }

    /**
     * {@inheritDoc}
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof InvalidTransactionAvsEvent && $event->getTransaction()->getOrder();
    }
}
