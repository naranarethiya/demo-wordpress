<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListProductsByLocalIdService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\CanGenerateIdContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\WooOrderCartIdProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\WooCommerce\OrderDataStore;
use WC_Abstract_Order_Data_Store_Interface;
use WC_Object_Data_Store_Interface;
use WC_Order_Data_Store_CPT;
use WC_Order_Data_Store_Interface;

class OrderDataStoreHandler extends AbstractInterceptorHandler
{
    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        $defaultDataStore = array_shift($args);

        $compatibleDefaultDataStore = $this->tryGetCompatibleDefaultDataStore($defaultDataStore);

        if ($compatibleDefaultDataStore &&
            $mwcOrderDataStore = $this->tryGetOrderDataStoreFromContainer($compatibleDefaultDataStore)) {
            return $mwcOrderDataStore;
        }

        return $defaultDataStore;
    }

    /**
     * Tries to get a new {@see OrderDataStore} instance with constructor params from container.
     *
     * @param WC_Object_Data_Store_Interface&WC_Abstract_Order_Data_Store_Interface&WC_Order_Data_Store_Interface $defaultDataStoreInstance
     *
     * @return OrderDataStore|null
     */
    protected function tryGetOrderDataStoreFromContainer(WC_Order_Data_Store_Interface $defaultDataStoreInstance) : ?OrderDataStore
    {
        $container = ContainerFactory::getInstance()->getSharedContainer();

        try {
            return new OrderDataStore(
                $defaultDataStoreInstance,
                $container->get(OrdersServiceContract::class),
                $container->get(OrderReservationsServiceContract::class),
                $container->get(WooOrderCartIdProvider::class),
                $container->get(CanGenerateIdContract::class),
                $container->get(BatchListProductsByLocalIdService::class)
            );
        } catch (ContainerException $exception) {
            SentryException::getNewInstance('Could not instantiate '.OrderDataStore::class, $exception);
        }

        return null;
    }

    /**
     * Checks is the default data store given by the woocommerce hook compatible with {@see OrderDataStore}.
     *
     * @param mixed $defaultDataStore
     *
     * @return bool
     *
     * @phpstan-assert-if-true WC_Object_Data_Store_Interface $defaultDataStore
     * @phpstan-assert-if-true WC_Abstract_Order_Data_Store_Interface $defaultDataStore
     * @phpstan-assert-if-true WC_Order_Data_Store_Interface $defaultDataStore
     */
    protected function isCompatibleDataStoreInstance($defaultDataStore) : bool
    {
        return $defaultDataStore instanceof WC_Object_Data_Store_Interface &&
            $defaultDataStore instanceof WC_Abstract_Order_Data_Store_Interface &&
            $defaultDataStore instanceof WC_Order_Data_Store_Interface;
    }

    /**
     * Try to get WC order data store instance of a compatible type, or null.
     *
     * @param mixed $defaultDataStore
     *
     * @return (WC_Object_Data_Store_Interface&WC_Abstract_Order_Data_Store_Interface&WC_Order_Data_Store_Interface)|null
     */
    protected function tryGetCompatibleDefaultDataStore($defaultDataStore) : ?WC_Order_Data_Store_Interface
    {
        if ($this->isCompatibleDataStoreInstance($defaultDataStore)) {
            return $defaultDataStore;
        }

        if (is_string($defaultDataStore) &&
            is_a($defaultDataStore, WC_Order_Data_Store_CPT::class, true)) {
            return new $defaultDataStore();
        }

        return null;
    }
}
