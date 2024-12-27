<?php

namespace GoDaddy\WordPress\MWC\Core\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Settings\OnboardingSetting;

trait CanCheckIfOnboardingHasInitializedTrait
{
    /**
     * Determines whether the onboarding feature has been initialized yet.
     *
     * This does not check if onboarding has been _completed_, just whether the feature has run its initial set-up yet.
     * This can be used when something needs to happen only once on initial admin load (i.e. brand new sites), but not
     * on subsequent loads.
     *
     * @return bool
     */
    protected function hasOnboardingInitialized() : bool
    {
        $setting = OnboardingSetting::get(OnboardingSetting::SETTING_ID_FIRST_TIME);

        return ! empty($setting->getValue());
    }
}
