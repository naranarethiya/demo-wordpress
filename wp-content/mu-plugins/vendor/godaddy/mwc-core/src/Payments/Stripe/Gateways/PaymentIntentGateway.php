<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways;

use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\CreatePaymentIntentAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\PaymentIntentAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\PaymentIntent;
use Stripe\Exception\ApiErrorException;

/**
 * Payment Intent gateway.
 */
class PaymentIntentGateway extends StripeGateway
{
    /**
     * Returns a related PaymentIntent model.
     *
     * @param string $id
     *
     * @return PaymentIntent
     *
     * @throws ApiErrorException
     */
    public function get(string $id) : PaymentIntent
    {
        $args = [
            'expand' => [
                'payment_method',
            ],
        ];

        $this->maybeLogApiRequest(__METHOD__, ['id' => $id] + $args);
        $response = $this->getClient()->paymentIntents->retrieve($id, $args);
        $this->maybeLogApiResponse(__METHOD__, $response);

        return PaymentIntentAdapter::getNewInstance()->convertToSource($response->toArray());
    }

    /**
     * Creates or updates a payment intent in Stripe.
     *
     * @param PaymentIntent $paymentIntent
     *
     * @return PaymentIntent
     *
     * @throws ApiErrorException
     */
    public function upsert(PaymentIntent $paymentIntent) : PaymentIntent
    {
        return $paymentIntent->getId() ? $this->update($paymentIntent) : $this->create($paymentIntent);
    }

    /**
     * Creates and returns a PaymentIntent model.
     *
     * @param PaymentIntent $paymentIntent
     *
     * @return PaymentIntent
     *
     * @throws ApiErrorException
     */
    public function create(PaymentIntent $paymentIntent) : PaymentIntent
    {
        $args = ($adapter = CreatePaymentIntentAdapter::getNewInstance($paymentIntent))->convertFromSource();

        $this->maybeLogApiRequest(__METHOD__, $args, $paymentIntent);

        $response = $this->getClient()->paymentIntents->create($args);

        $this->maybeLogApiResponse(__METHOD__, $response);

        return $adapter->convertToSource($response->toArray());
    }

    /**
     * Updates and returns a PaymentIntent model.
     *
     * @param PaymentIntent $paymentIntent
     *
     * @return PaymentIntent
     *
     * @throws ApiErrorException
     */
    public function update(PaymentIntent $paymentIntent) : PaymentIntent
    {
        $args = ($adapter = PaymentIntentAdapter::getNewInstance($paymentIntent))->convertFromSource();

        $this->maybeLogApiRequest(__METHOD__, $args, $paymentIntent);

        $response = $this->getClient()->paymentIntents->update(
            $paymentIntent->getId(),
            $args
        );

        $this->maybeLogApiResponse(__METHOD__, $response);

        return $adapter->convertToSource($response->toArray());
    }

    /**
     * Confirms and returns a PaymentIntent model.
     *
     * @param PaymentIntent $paymentIntent
     * @param string $return_url
     *
     * @return PaymentIntent
     *
     * @throws ApiErrorException
     */
    public function confirm(PaymentIntent $paymentIntent, string $return_url) : PaymentIntent
    {
        $args = [
            'payment_method' => $paymentIntent->getPaymentMethod() ? $paymentIntent->getPaymentMethod()->getRemoteId() : '',
            'return_url'     => $return_url,
        ];

        $this->maybeLogApiRequest(__METHOD__, $args, $paymentIntent);
        $response = $this->getClient()->paymentIntents->confirm(
            $paymentIntent->getId() ?? '',
            $args
        );
        $this->maybeLogApiResponse(__METHOD__, $response);

        return PaymentIntentAdapter::getNewInstance($paymentIntent)->convertToSource($response->toArray());
    }

    /**
     * Cancels and returns a related PaymentIntent model.
     *
     * @param string $id
     *
     * @return PaymentIntent
     *
     * @throws ApiErrorException
     */
    public function cancel(string $id) : PaymentIntent
    {
        $this->maybeLogApiRequest(__METHOD__, ['id' => $id]);
        $response = $this->getClient()->paymentIntents->cancel($id);
        $this->maybeLogApiResponse(__METHOD__, $response);

        return PaymentIntentAdapter::getNewInstance()->convertToSource($response->toArray());
    }
}
