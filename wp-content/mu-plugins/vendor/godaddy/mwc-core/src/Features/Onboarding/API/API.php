<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\API;

use GoDaddy\WordPress\MWC\Common\API\API as CommonAPI;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\API\Controllers\OnboardingSettingsController;

class API extends CommonAPI
{
    /** @var class-string<ComponentContract>[] controller classes to load/register */
    protected $componentClasses = [
        OnboardingSettingsController::class,
    ];
}
