<?php

namespace GoDaddy\WordPress\MWC\Common\Events\Contracts;

/**
 * Event handler contract.
 */
interface EventHandlerContract
{
    /**
     * Handles and perhaps modifies the event.
     *
     * @param EventContract $event
     */
    public function handle(EventContract $event);
}
