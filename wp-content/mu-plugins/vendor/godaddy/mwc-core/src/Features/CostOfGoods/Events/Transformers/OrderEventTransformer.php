<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\Events\Transformers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Events\Transformers\AbstractOrderEventTransformer;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Repositories\OrdersRepository;

/**
 * Transformer to add Cost of Goods related data to order events.
 */
class OrderEventTransformer extends AbstractOrderEventTransformer
{
    /**
     * Handles and perhaps modifies the event.
     *
     * @param ModelEvent|EventContract $event the event, perhaps modified by the method
     */
    public function handle(EventContract $event) : void
    {
        $data = $event->getData();

        ArrayHelper::set(
            $data,
            'resource.productTotalCost',
            $this->resolveTotalCostFromData($data)
        );

        $event->setData($data);
    }

    /**
     * Get total cost of order from given data.
     *
     * @param array<mixed> $data
     * @return string
     */
    protected function resolveTotalCostFromData(array $data) : string
    {
        if (! $orderId = TypeHelper::int(ArrayHelper::get($data, 'resource.id'), 0)) {
            return '';
        }

        return $this->getTotalCost($orderId) ?? '';
    }

    /**
     * Gets the total cost of goods for the order identified with the given ID.
     *
     * Returns null if there is no order with the given ID.
     *
     * @param int $orderId
     * @return string|null
     */
    protected function getTotalCost(int $orderId) : ?string
    {
        if ($order = OrdersRepository::get($orderId)) {
            return TypeHelper::string($order->get_meta('_wc_cog_order_total_cost'), '');
        }

        return null;
    }
}
