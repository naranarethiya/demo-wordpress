<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\AbstractWebhookReceivedEvent;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Subscribers\AbstractWebhookReceivedSubscriber;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DateTimeRepository;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\PoyntWebhookInvalidSignatureException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidOrderException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\GetOrderRequest;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Emails\ReadyForPickupEmail;
use WC_Order;

/**
 * Webhook subscriber.
 */
class WebhookSubscriber extends AbstractWebhookReceivedSubscriber implements ComponentContract
{
    /** @var string order cancelled webhook event type */
    const ORDER_CANCELLED_EVENT_TYPE = 'ORDER_CANCELLED';

    /** @var string order completed webhook event type */
    const ORDER_COMPLETED_EVENT_TYPE = 'ORDER_COMPLETED';

    /** @var string order updated webhook event type */
    const ORDER_UPDATED_EVENT_TYPE = 'ORDER_UPDATED';

    /** @var string resource value for catalogs webhooks */
    const EVENT_RESOURCE_CATALOGS = '/catalogs';

    /** @var string resource value for products webhooks */
    const EVENT_RESOURCE_PRODUCTS = '/products';

    /** @var string resource value for transactions webhooks */
    const EVENT_RESOURCE_TRANSACTIONS = '/transactions';

    /** @var string resource value for orders webhooks */
    const EVENT_RESOURCE_ORDERS = '/orders';

    /**
     * Required for the {@see ComponentContract} interface.
     */
    public function load()
    {
        // no-op
    }

    /**
     * Determines whether the event should be handled.
     *
     * @param EventContract $event
     * @return bool
     * @throws Exception
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return Configuration::get('payments.poynt.active')
               && Configuration::get('payments.poynt.webhooks')
               && parent::shouldHandle($event);
    }

    /**
     * Validates a webhook.
     *
     * @param AbstractWebhookReceivedEvent $event
     * @return bool
     * @throws Exception
     */
    public function validate(AbstractWebhookReceivedEvent $event) : bool
    {
        $signature = base64_encode(hash_hmac('sha1', $event->getPayload(), Poynt::getWebhookSecret(), true));

        if (! hash_equals($signature, ArrayHelper::get($event->getHeaders(), 'HTTP_POYNT_WEBHOOK_SIGNATURE'))) {
            throw new PoyntWebhookInvalidSignatureException('Invalid webhook signature');
        }

        return StringHelper::isJson($event->getPayload());
    }

    /**
     * Handles the event payload.
     *
     * @param AbstractWebhookReceivedEvent $event
     * @throws Exception
     */
    public function handlePayload(AbstractWebhookReceivedEvent $event) : void
    {
        $data = $event->getPayloadDecoded();
        $eventType = ArrayHelper::get($data, 'eventType');
        $eventResource = ArrayHelper::get($data, 'resource');

        // TODO: consider refactoring this routing, potentially define a property with $resource => $subscriberClass definitions to loop through {@cwiseman 2022-02-10}
        if (static::EVENT_RESOURCE_CATALOGS === $eventResource) {
            $this->handleCatalogEvent($event);
        } elseif (static::EVENT_RESOURCE_PRODUCTS === $eventResource) {
            $this->handleProductEvent($event);
        } elseif (static::EVENT_RESOURCE_TRANSACTIONS === $eventResource) {
            $this->handleTransactionEvent($event);
        } elseif (static::EVENT_RESOURCE_ORDERS === $eventResource) {
            if (! $wcOrder = $this->findOrderForPayload($data)) {
                return;
            }

            if (static::ORDER_CANCELLED_EVENT_TYPE === $eventType) {
                $this->handleOrderCancelledEvent($wcOrder);
            } elseif (static::ORDER_COMPLETED_EVENT_TYPE === $eventType) {
                $this->handleOrderCompletedEvent($wcOrder);
            } elseif (static::ORDER_UPDATED_EVENT_TYPE === $eventType) {
                $this->handleOrderUpdatedEvent($data, $wcOrder);
            }
        }
    }

    /**
     * Handles a catalog event.
     *
     * @param EventContract $event
     */
    protected function handleCatalogEvent(EventContract $event)
    {
        (new CatalogWebhookReceivedSubscriber())->handle($event);
    }

    /**
     * Handles a product event.
     *
     * @param EventContract $event
     */
    protected function handleProductEvent(EventContract $event)
    {
        (new ProductWebhookReceivedSubscriber())->handle($event);
    }

    /**
     * Handles a transaction event.
     *
     * @param EventContract $event
     * @throws Exception
     */
    protected function handleTransactionEvent(EventContract $event)
    {
        (new TransactionWebhookReceivedSubscriber())->handle($event);
    }

    /**
     * Find the corresponding WC Order based on the webhook payload data.
     *
     * @param array $data
     * @return WC_Order|null
     */
    protected function findOrderForPayload(array $data) : ?WC_Order
    {
        if (WooCommerceRepository::isCustomOrdersTableUsageEnabled()) {
            return $this->findOrderForPayloadUsingCustomerOrdersTable($data);
        }

        return $this->findOrderForPayloadUsingCustomPosts($data);
    }

    /**
     * Finds an order stored in WooCommerce's tables that is associated with the resource ID included in the given payload.
     *
     * @param array<string, mixed> $data
     * @return WC_Order|null
     */
    protected function findOrderForPayloadUsingCustomerOrdersTable(array $data) : ?WC_Order
    {
        $orders = OrdersRepository::query([
            'return'     => 'objects',
            'meta_query' => $this->getFindOrderForPayloadMetaQuery($data),
        ]);

        return $orders ? array_shift($orders) : null;
    }

    /**
     * Finds an order stored as custom post type that is associated with the resource ID included in the given payload.
     *
     * @param array<string, mixed> $data
     * @return WC_Order|null
     */
    protected function findOrderForPayloadUsingCustomPosts(array $data) : ?WC_Order
    {
        $results = get_posts([
            'post_type'   => 'shop_order',
            'fields'      => 'ids',
            'post_status' => 'any',
            'meta_query'  => $this->getFindOrderForPayloadMetaQuery($data),
        ]);

        return $results ? OrdersRepository::get($results[0]) : null;
    }

    /**
     * Gets the meta_query fields to find orders by their remote ID.
     *
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    protected function getFindOrderForPayloadMetaQuery(array $data) : array
    {
        return [
            [
                'key'     => '_poynt_order_remoteId',
                'value'   => ArrayHelper::get($data, 'resourceId'),
                'compare' => '=',
            ],
        ];
    }

    /**
     * Handles an order cancelled event.
     *
     * @param WC_Order $wcOrder
     */
    protected function handleOrderCancelledEvent(WC_Order $wcOrder)
    {
        if ('refunded' !== $wcOrder->get_status()) {
            $wcOrder->update_status('cancelled');
        }
    }

    /**
     * Handles an order completed event.
     *
     * @param WC_Order $wcOrder
     */
    protected function handleOrderCompletedEvent(WC_Order $wcOrder)
    {
        if (! ArrayHelper::contains(['refunded', 'cancelled'], $wcOrder->get_status())) {
            $wcOrder->update_status('completed');
        }
    }

    /**
     * Handles an order updated event.
     *
     * @param array $data
     * @param WC_Order $wcOrder
     * @throws Exception
     */
    protected function handleOrderUpdatedEvent(array $data, WC_Order $wcOrder)
    {
        $this->maybeHandleOrderReadyForPickup($data, $wcOrder);
    }

    /**
     * Gets the remote order data from Poynt API.
     *
     * @param array $data
     * @return array
     * @throws InvalidOrderException|Exception
     */
    protected function getRemoteOrderData(array $data) : array
    {
        $poyntOrderId = ArrayHelper::get($data, 'resourceId');
        $response = (new GetOrderRequest($poyntOrderId))->send();

        if ($response->isError() || $response->getStatus() !== 200) {
            $errorMessage = ArrayHelper::get($response->getBody(), 'developerMessage');

            throw new InvalidOrderException("Could not retrieve order {$poyntOrderId} from Poynt ({$response->getStatus()}): {$errorMessage}");
        }

        if ($response->isSuccess()) {
            return $response->getBody();
        }

        return [];
    }

    /**
     * May handle the event received when an order is marked as ready for pickup.
     *
     * @param array $data
     * @param WC_Order $wcOrder
     * @throws Exception
     */
    protected function maybeHandleOrderReadyForPickup(array $data, WC_Order $wcOrder)
    {
        if ($wcOrder->get_meta('_poynt_order_status_ready_at')) {
            return;
        }

        if (! empty($orderData = $this->getRemoteOrderData($data)) && $this->isOrderReadyForPickup($orderData)) {
            $this->handleOrderReadyForPickup($orderData, $wcOrder);
        }
    }

    /**
     * Checks if the order is ready for pickup.
     *
     * @param array $orderData from response body
     * @return bool
     */
    protected function isOrderReadyForPickup(array $orderData) : bool
    {
        if ('OPENED' != ArrayHelper::get($orderData, 'statuses.status')) {
            return false;
        }

        foreach (ArrayHelper::get($orderData, 'orderShipments', []) as $shipment) {
            if ('PICKUP' === ArrayHelper::get($shipment, 'deliveryMode')
                && 'AWAITING_PICKUP' === ArrayHelper::get($shipment, 'status')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handles communication when an order is marked as ready for pickup.
     *
     * @param array $orderData from response body
     * @param WC_Order $wcOrder
     * @throws Exception
     */
    protected function handleOrderReadyForPickup(array $orderData, WC_Order $wcOrder)
    {
        if ($wcOrder->get_meta('_poynt_order_status_ready_at')) {
            return;
        }

        $readyAtEvent = current(ArrayHelper::where(ArrayHelper::get($orderData, 'orderHistories', []), function ($value) {
            return 'AWAITING_PICKUP' === ArrayHelper::get($value, 'event');
        }));

        $readyAt = ArrayHelper::get($readyAtEvent, 'timestamp') ?: ArrayHelper::get($orderData, 'updatedAt');

        $wcOrder->add_meta_data('_poynt_order_status_ready_at', $readyAt);
        $wcOrder->save_meta_data();

        if ($timestamp = (int) strtotime(TypeHelper::string($readyAt, ''))) {
            $wcOrder->add_order_note(sprintf(
                /* translators: Placeholders: %1$s - date, %2$s time */
                __('Order marked ready on terminal on %1$s at %2$s', 'mwc-core'),
                DateTimeRepository::getLocalizedDate(DateTimeRepository::getDateFormat(), $timestamp),
                DateTimeRepository::getLocalizedDate(DateTimeRepository::getTimeFormat(), $timestamp)
            ));
        }

        (new ReadyForPickupEmail())->trigger($wcOrder->get_id(), $wcOrder);
    }
}
