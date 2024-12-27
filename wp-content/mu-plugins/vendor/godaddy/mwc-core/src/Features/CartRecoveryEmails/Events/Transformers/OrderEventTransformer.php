<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Events\Transformers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\Events\Transformers\AbstractOrderEventTransformer;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Repositories\WooCommerce\OrdersRepository;

/**
 * Transforms order model events to include recovery status in the resource data.
 */
class OrderEventTransformer extends AbstractOrderEventTransformer
{
    /**
     * Adds the order recovery status to the model event data.
     *
     * @param ModelEvent $event
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        /** @var Order $order */
        $order = $event->getModel();
        $data = $event->getData();

        /* @TODO confirm the payload data structure added here {unfulvio 2022-03-23} */
        ArrayHelper::set($data, 'resource.recovery', [
            'status'     => OrdersRepository::getOrderRecoveryStatus($order->getId()),
            'checkoutId' => OrdersRepository::getOrderRecoverableCheckoutId($order->getId()),
        ]);

        $event->setData($data);
    }
}
