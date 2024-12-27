<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Configuration;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;

/**
 * Loads when the Commerce integration is loaded, to disable incompatible features at runtime.
 */
class DisableIncompatibleFeaturesAction implements ComponentContract
{
    /**
     * Disables features that are incompatible with the Commerce feature.
     *
     * @return void
     */
    public function load() : void
    {
        // replaced by the Commerce integration, which keeps catalog in sync
        Configuration::set('features.bopit_sync.enabled', false);
    }
}
