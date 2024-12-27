<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Transformers;

use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;

/**
 * Transformer for setting the context of the event.
 */
class EventContextTransformer extends AbstractEventTransformer
{
    /**
     * {@inheritDoc}
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof EventBridgeEventContract;
    }

    /**
     * Sets the context for this event, if we have one.
     *
     * @param EventBridgeEventContract $event
     * @return void
     */
    public function handle(EventContract $event)
    {
        if ($context = $this->getEventContext()) {
            $event->setContext($context);
        }
    }

    /**
     * Retrieves the event context if it's set.
     *
     * @return string|null Context string if set, null if not.
     */
    protected function getEventContext() : ?string
    {
        return defined('MWC_EVENT_CONTEXT') ? MWC_EVENT_CONTEXT : null;
    }
}
