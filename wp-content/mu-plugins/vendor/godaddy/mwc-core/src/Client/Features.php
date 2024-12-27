<?php

namespace GoDaddy\WordPress\MWC\Core\Client;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\CartRecoveryEmails;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Dashboard as OnboardingDashboard;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Onboarding;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Shipping;

/**
 * Handler for feature flags.
 */
class Features
{
    use CanGetNewInstanceTrait;

    /**
     * A key-value array of feature flags.
     *
     * @return array [string => bool, ...]
     */
    public function featureFlags() : array
    {
        return [
            'isCartRecoveryEnabled'        => CartRecoveryEmails::shouldLoad(),
            'isOnboardingEnabled'          => Onboarding::shouldLoad(),
            'isOnboardingDashboardEnabled' => OnboardingDashboard::shouldLoad(),
            'isShippingLabelsEnabled'      => Shipping::shouldLoad(),
            'isShippingAccountConnected'   => Shipping::shouldLoad() && Shipping::isAccountConnected(),
        ];
    }
}
