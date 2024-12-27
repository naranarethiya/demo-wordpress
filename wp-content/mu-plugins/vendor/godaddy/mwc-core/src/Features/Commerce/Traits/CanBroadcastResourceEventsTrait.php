<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\AbstractIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;

/**
 * Trait to facilitate broadcasting events based on commerce capabilities.
 */
trait CanBroadcastResourceEventsTrait
{
    /**
     * (Maybe) broadcasts a resource CRUD event.
     *
     * @param class-string<AbstractIntegration> $integrationClassName
     * @param EventContract $event
     * @return void
     */
    public function maybeBroadcastEvent(string $integrationClassName, EventContract $event) : void
    {
        if ($this->shouldBroadcastEvents($integrationClassName)) {
            Events::broadcast($event);
        }
    }

    /**
     * Determines whether events should be broadcasted.
     *
     * @param class-string<AbstractIntegration> $integrationClassName
     * @return bool
     */
    public function shouldBroadcastEvents(string $integrationClassName) : bool
    {
        return $integrationClassName::hasCommerceCapability(Commerce::CAPABILITY_EVENTS);
    }
}
