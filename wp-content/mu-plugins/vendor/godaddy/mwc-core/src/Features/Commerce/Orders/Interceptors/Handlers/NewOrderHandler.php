<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\WooOrderCartIdProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanCheckWooCommerceOrderTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Order;

class NewOrderHandler extends AbstractInterceptorHandler
{
    use CanCheckWooCommerceOrderTrait;

    protected OrdersMappingServiceContract $ordersMappingService;
    protected WooOrderCartIdProvider $wooOrderCartIdProvider;

    public function __construct(
        OrdersMappingServiceContract $ordersMappingService,
        WooOrderCartIdProvider $wooOrderCartIdProvider
    ) {
        $this->ordersMappingService = $ordersMappingService;
        $this->wooOrderCartIdProvider = $wooOrderCartIdProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        $wooOrder = ArrayHelper::get($args, '1');

        if (! $this->canWriteWooCommerceOrderInPlatform($wooOrder) ||
            $this->isWooCommerceOrderIncomplete($wooOrder)) {
            return;
        }

        $this->tryToMapOrderToPlatform($wooOrder);
    }

    /**
     * Attempts to map the given WooCommerce order with a remote resource in the Commerce platform.
     */
    protected function tryToMapOrderToPlatform(WC_Order $wooOrder) : void
    {
        try {
            $this->mapOrderToPlatform($wooOrder);
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance(
                'An error occurred trying to save the remote ID for an order.',
                $exception
            );
        }
    }

    /**
     * Map the given WooCommerce order with a remote resource in the Commerce platform.
     *
     * @throws CommerceExceptionContract
     */
    protected function mapOrderToPlatform(WC_Order $wooOrder) : void
    {
        $order = $this->convertOrderForPlatform($wooOrder);

        $this->ordersMappingService->saveRemoteId($order, (string) $this->ordersMappingService->getRemoteId($order));
    }

    /**
     * Converts the given WooCommerce order into an Order object that can be mapped to a remote resource.
     *
     * @throws CommerceExceptionContract
     */
    protected function convertOrderForPlatform(WC_Order $wooOrder) : Order
    {
        if (! $cartId = $this->wooOrderCartIdProvider->getCartId($wooOrder)) {
            throw new CommerceException('The order has no cartId.');
        }

        return (new Order())->setId($wooOrder->get_id())->setCartId($cartId);
    }
}
