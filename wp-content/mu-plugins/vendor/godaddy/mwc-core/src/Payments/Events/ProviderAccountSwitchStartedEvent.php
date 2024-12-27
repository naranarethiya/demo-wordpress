<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Events;

/**
 * Provider Account Switch Started event class.
 */
class ProviderAccountSwitchStartedEvent extends AbstractProviderAccountEvent
{
    /**
     * ProviderAccountAssociatedEvent constructor.
     *
     * @param string $providerName
     */
    public function __construct(string $providerName)
    {
        parent::__construct($providerName);

        $this->action = 'switch_started';
    }
}
