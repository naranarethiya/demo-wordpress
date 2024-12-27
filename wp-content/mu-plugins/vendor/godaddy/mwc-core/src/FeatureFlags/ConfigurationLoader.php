<?php

namespace GoDaddy\WordPress\MWC\Core\FeatureFlags;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Models\FeatureFlag;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Repositories\FeatureFlagsRepository;

/**
 * Loads feature flags values into the configuration.
 */
class ConfigurationLoader implements ComponentContract
{
    /**
     * Loads the component.
     */
    public function load()
    {
        $this->maybeUpdateFeaturesConfiguration();
    }

    /**
     * Loads the available feature flags and tries to inject those values into the configuration.
     */
    protected function maybeUpdateFeaturesConfiguration()
    {
        foreach (FeatureFlagsRepository::all() as $featureFlag) {
            $this->maybeUpdateFeatureConfiguration($featureFlag);
        }
    }

    /**
     * Attempts to inject the value of the given feature flag into the configuration.
     *
     * @param FeatureFlag $featureFlag
     */
    protected function maybeUpdateFeatureConfiguration(FeatureFlag $featureFlag)
    {
        if (! $featureId = $featureFlag->getId()) {
            return;
        }

        // return early if the feature has no configuration defined
        if (! ArrayHelper::accessible(Configuration::get("features.{$featureId}"))) {
            return;
        }

        if (is_null($featureFlag->getValue()) || is_null($featureFlag->getValue()->getBoolValue())) {
            return;
        }

        Configuration::set("features.{$featureId}.enabled", $featureFlag->getValue()->getBoolValue());
    }
}
