<?php

namespace GoDaddy\WordPress\MWC\Common\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\TransformerContract;

/**
 * Abstract event transformer class.
 */
abstract class AbstractEventTransformer implements TransformerContract
{
    /**
     * Determines whether the event must be transformed or not.
     *
     * @param EventContract $event
     * @return bool
     */
    abstract public function shouldHandle(EventContract $event) : bool;

    /**
     * Handles and perhaps modifies the event.
     *
     * @param EventContract $event the event, perhaps modified by the method
     */
    abstract public function handle(EventContract $event);
}
