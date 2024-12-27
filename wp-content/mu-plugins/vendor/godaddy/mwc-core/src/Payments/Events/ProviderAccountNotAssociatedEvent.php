<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Events;

/**
 * Provider Account Not Associated event class.
 */
class ProviderAccountNotAssociatedEvent extends AbstractProviderAccountEvent
{
    /**
     * ProviderAccountNotAssociatedEvent constructor.
     *
     * @param string $providerName
     */
    public function __construct(string $providerName)
    {
        parent::__construct($providerName);

        $this->action = 'not_associated';
    }
}
