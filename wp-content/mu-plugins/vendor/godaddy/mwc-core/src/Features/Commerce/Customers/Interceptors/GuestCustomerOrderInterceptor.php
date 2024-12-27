<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\DataSources\WooCommerce\Builders\OrderToCustomerBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Operations\CreateOrUpdateCustomerOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\GuestCustomer;
use WC_Order;

class GuestCustomerOrderInterceptor extends AbstractInterceptor
{
    /**
     * @var CustomersServiceContract
     */
    protected CustomersServiceContract $customersService;

    /**
     * @var CustomersMappingServiceContract
     */
    protected CustomersMappingServiceContract $customersMappingService;

    /**
     * @param CustomersServiceContract $customersService
     * @param CustomersMappingServiceContract $customersMappingService
     */
    public function __construct(CustomersServiceContract $customersService, CustomersMappingServiceContract $customersMappingService)
    {
        $this->customersService = $customersService;
        $this->customersMappingService = $customersMappingService;
    }

    /**
     * {@inheritDoc}
     */
    public function addHooks() : void
    {
        try {
            Register::action()
                ->setGroup('woocommerce_checkout_create_order')
                ->setHandler([$this, 'pushGuestCustomerData'])
                ->setArgumentsCount(2)
                ->execute();

            Register::action()
                ->setGroup('woocommerce_checkout_order_created')
                ->setHandler([$this, 'mapCustomerToPlatform'])
                ->execute();
        } catch (Exception $exception) {
            // silently ignore exceptions from RegisterAction::execute()
        }
    }

    /**
     * Pushes the guest customer data to the commerce platform.
     *
     * @param mixed $order
     * @return void
     */
    public function pushGuestCustomerData($order) : void
    {
        if (! $order instanceof WC_Order) {
            return;
        }

        $this->tryToPushGuestCustomerDataFromOrder($order);
    }

    /**
     * Attempts to extract guest customer data from the given order and use it to create a remote record.
     *
     * @param WC_Order $order
     * @return void
     */
    protected function tryToPushGuestCustomerDataFromOrder(WC_Order $order) : void
    {
        $customer = OrderToCustomerBuilder::getNewInstance($order)->build();

        if (! $customer instanceof GuestCustomer) {
            return;
        }

        $this->tryToPushGuestCustomerData($customer);
    }

    /**
     * Attempts to create a remote record for the given guest customer.
     *
     * @param GuestCustomer $customer
     * @return void
     */
    protected function tryToPushGuestCustomerData(GuestCustomer $customer) : void
    {
        try {
            $this->customersService->createOrUpdateCustomer(CreateOrUpdateCustomerOperation::fromCustomer($customer));
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance('An error occurred trying to create a remote record for a guest customer: '.$exception->getMessage(), $exception);
        }
    }

    /**
     * Maps the guest customer's local ID to the remote ID.
     *
     * @param mixed $wooOrder
     * @return void
     */
    public function mapCustomerToPlatform($wooOrder) : void
    {
        if (! $wooOrder instanceof WC_Order) {
            return;
        }

        if (! $customer = OrderToCustomerBuilder::getNewInstance($wooOrder)->build()) {
            return;
        }

        if (! $remoteId = $this->customersMappingService->getRemoteId($customer)) {
            return;
        }

        try {
            $this->customersMappingService->saveRemoteId($customer, $remoteId);
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance('An error occurred trying to save the remote ID for a guest customer: '.$exception->getMessage(), $exception);
        }
    }
}
