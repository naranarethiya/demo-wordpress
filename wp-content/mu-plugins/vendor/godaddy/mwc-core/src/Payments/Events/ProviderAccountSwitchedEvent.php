<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Events;

/**
 * Provider Account Switched event class.
 */
class ProviderAccountSwitchedEvent extends AbstractProviderAccountEvent
{
    /**
     * ProviderAccountSwitchedEvent constructor.
     *
     * @param string $providerName
     */
    public function __construct(string $providerName)
    {
        parent::__construct($providerName);

        $this->action = 'switched';
    }
}
