<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Events;

/**
 * Provider Account Associated event class.
 */
class ProviderAccountAssociatedEvent extends AbstractProviderAccountEvent
{
    /**
     * ProviderAccountAssociatedEvent constructor.
     *
     * @param string $providerName
     */
    public function __construct(string $providerName)
    {
        parent::__construct($providerName);

        $this->action = 'associated';
    }
}
