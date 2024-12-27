<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Events\Transformers\Traits;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;

/**
 * Includes methods event transformers to handle an event only if it is for a Product model.
 */
trait IsProductEventTransformerTrait
{
    /**
     * Determines whether the event must be transformed or not.
     *
     * @param ModelEvent $event
     * @return bool
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return $this->isProductEvent($event);
    }

    /**
     * Determines whether the given event is a {@see ModelEvent} for products.
     *
     * @param EventContract $event
     * @return bool
     */
    protected function isProductEvent(EventContract $event) : bool
    {
        return $event instanceof ModelEvent && 'product' === $event->getResource();
    }
}
