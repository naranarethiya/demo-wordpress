<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Transformers;

use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * Abstract class for transforming {@see Order} model events.
 */
abstract class AbstractOrderEventTransformer extends AbstractEventTransformer
{
    /**
     * Determines whether the event must be transformed or not.
     *
     * @param ModelEvent|EventContract $event
     * @return bool
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof ModelEvent && 'order' === $event->getResource();
    }

    /**
     * Handles and perhaps modifies the event.
     *
     * @param EventContract $event the event, perhaps modified by the method
     * @return mixed
     */
    abstract public function handle(EventContract $event);
}
