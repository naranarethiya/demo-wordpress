<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\SessionValue;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Checkout;
use GoDaddy\WordPress\MWC\Common\Models\User;

/**
 * Adapter to convert from the `woocommerce_sessions` database table row into a native Checkout object.
 */
class CheckoutAdapter implements DataSourceAdapterContract
{
    /** @var array checkout data */
    protected $source;

    /** @var string the checkout class name - must be Checkout or a descendent of Checkout */
    protected $checkoutClass = Checkout::class;

    /**
     * Checkout adapter constructor.
     *
     * The expected source data is based on the `session_value` from the `woocommerce_sessions` table, and optionally the `checkout_id` value.
     *
     * @param array $data checkout data
     */
    public function __construct(array $data)
    {
        $this->source = $data;
    }

    /**
     * Converts checkout source data into a native checkout object.
     *
     * @return Checkout
     * @throws BaseException
     */
    public function convertFromSource() : Checkout
    {
        /** @var Checkout $checkout */
        $checkout = (new $this->checkoutClass())
            ->setCart((new CartAdapter($this->source))->convertFromSource());

        if (! empty($id = ArrayHelper::get($this->source, 'checkout_id'))) {
            if (! is_numeric($id)) {
                throw new BaseException('Checkout ID must be numeric.');
            }

            $checkout->setId((int) $id);
        }

        /* @NOTE the email address may not match the actual user's, so we don't set it here {unfulvio 2022-02-15} */
        if (! empty($customerUserId = ArrayHelper::get($this->source, 'customer.id'))) {
            if (! is_numeric($customerUserId) || ! $user = User::get((int) $customerUserId)) {
                throw new BaseException('Customer user ID is invalid.');
            }

            $checkout->setCustomer($user);
        } elseif (! empty($firstName = ArrayHelper::get($this->source, 'customer.first_name'))) {
            // create a new Customer when the customer is not logged in but have placed an order before (will have their
            // data populated in the session)
            $checkout->setCustomer(
                (new User())
                    ->setFirstName($firstName)
                    ->setLastName(ArrayHelper::get($this->source, 'customer.last_name'))
            );
        }

        return $checkout;
    }

    /**
     * @note NO-OP
     */
    public function convertToSource(?Checkout $checkout = null) : array
    {
        // no-op, we will never write to the woocommerce_sessions table
        return [];
    }
}
