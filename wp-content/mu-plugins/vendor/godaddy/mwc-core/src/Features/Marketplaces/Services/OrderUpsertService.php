<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Services;

use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\DataStores\OrderDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\OrderWebhookPayload;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Repositories\OrdersRepository;
use function GoDaddy\WordPress\MWC\SequentialOrderNumbers\wc_seq_order_number_pro;
use WC_Order;

/**
 * Creates a new order in WooCommerce based on the order built from the GDM webhook payload.
 *
 * @method static OrderUpsertService getNewInstance(OrderWebhookPayload $orderWebhookPayload)
 */
class OrderUpsertService
{
    use CanGetNewInstanceTrait;

    /** @var OrderWebhookPayload */
    protected $orderWebhookPayload;

    /**
     * Constructor.
     *
     * @param OrderWebhookPayload $orderWebhookPayload
     */
    public function __construct(OrderWebhookPayload $orderWebhookPayload)
    {
        $this->orderWebhookPayload = $orderWebhookPayload;
    }

    /**
     * Saves the order in WooCommerce.
     *
     * @return void
     * @throws SentryException|AdapterException
     */
    public function saveOrder() : void
    {
        $order = $this->orderWebhookPayload->getOrder();

        if (! $order instanceof Order) {
            throw new SentryException('Missing expected data from order webhook payload.');
        }

        if (! empty($marketplacesInternalOrderNumber = $order->getMarketplacesInternalOrderNumber())) {
            $existingWcOrder = OrdersRepository::getByMarketplacesInternalOrderNumber($marketplacesInternalOrderNumber);
        }

        // set the id to prevent creating a new WC Order if one already exists
        if (! empty($existingWcOrder)) {
            $order->setId($existingWcOrder->get_id());
            $this->setOrderItemsIds($order);
        }

        $wcOrder = OrderAdapter::getNewInstance($existingWcOrder ?? $this->getNewWcOrder())->convertToSource($order);

        $wcOrder->save();

        // upsert the order number as sequential
        if (function_exists('GoDaddy\WordPress\MWC\SequentialOrderNumbers\wc_seq_order_number_pro')) {
            wc_seq_order_number_pro()->set_sequential_order_number($wcOrder);
        }
    }

    /**
     * Gets a new WC_Order object.
     *
     * @return WC_Order
     */
    protected function getNewWcOrder() : WC_Order
    {
        return new WC_Order();
    }

    /**
     * Matches each order item line with its ID according to the order item reference.
     *
     * @param Order $order
     */
    protected function setOrderItemsIds(Order $order) : void
    {
        $itemReferenceMapping = OrderDataStore::getNewInstance()->getOrderItemReferenceMapping($order);

        /** @var LineItem $lineItem */
        foreach ($order->getLineItems() as $lineItem) {
            $lineItem->setId(ArrayHelper::get($itemReferenceMapping, $lineItem->getOrderItemReference() ?? '', 0));
        }
    }
}
