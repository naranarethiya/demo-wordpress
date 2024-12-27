<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CartRepository;
use GoDaddy\WordPress\MWC\Core\Features\Stripe\Stripe as StripeFeature;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\CartPaymentIntentAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataStores\WooCommerce\SessionPaymentIntentDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways\PaymentIntentGateway;

class CartInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('woocommerce_cart_updated')
            ->setHandler([$this, 'updateCart'])
            ->execute();
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return StripeFeature::shouldLoad() && Stripe::isConnected();
    }

    /**
     * Update Cart.
     *
     * @return void
     */
    public function updateCart() : void
    {
        try {
            $cartPaymentIntentAdapter = CartPaymentIntentAdapter::getNewInstance(CartRepository::getInstance());
            $paymentIntent = ($dataStore = SessionPaymentIntentDataStore::getNewInstance())->read();
            $gateway = PaymentIntentGateway::getNewInstance();

            if ($paymentIntent === null && ! CartRepository::isEmpty()) {
                $dataStore->save($gateway->create($cartPaymentIntentAdapter->convertFromSource()));

                return;
            }

            if (! CartRepository::isEmpty()) {
                $gateway->update($cartPaymentIntentAdapter->convertFromSource($paymentIntent));

                return;
            }

            if ($paymentIntent !== null) {
                $dataStore->delete($paymentIntent);

                // try to cancel the remote payment intent
                if ($gateway->get($paymentIntent->getId() ?: '')->isCancelable()) {
                    $gateway->cancel($paymentIntent->getId() ?: '');
                }

                return;
            }
        } catch (Exception $exception) {
        }
    }
}
