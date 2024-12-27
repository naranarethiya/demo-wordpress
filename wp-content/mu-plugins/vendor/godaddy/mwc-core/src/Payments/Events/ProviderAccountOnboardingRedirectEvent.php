<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Events;

/**
 * Provider Account Associated event class.
 */
class ProviderAccountOnboardingRedirectEvent extends AbstractProviderAccountEvent
{
    /**
     * ProviderAccountOnboardingRedirectEvent constructor.
     *
     * @param string $providerName
     */
    public function __construct(string $providerName)
    {
        parent::__construct($providerName);

        $this->action = 'onboarding_redirect';
    }
}
