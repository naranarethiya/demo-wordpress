<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Events\Transformers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Events\Transformers\AbstractOrderEventTransformer;

/**
 * Transformer to add data to order events.
 */
class OrderEventTransformer extends AbstractOrderEventTransformer
{
    /**
     * Handles and perhaps modifies the event.
     *
     * @param ModelEvent|EventContract $event the event, perhaps modified by the method
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        $data = $event->getData();

        ArrayHelper::set($data, 'resource.currency', WooCommerceRepository::getCurrency());

        $event->setData($data);
    }
}
