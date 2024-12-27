<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\PatchProductCategoryIdsJobStatusHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\StoreIdInterceptor;
use GoDaddy\WordPress\MWC\Core\Interceptors\Interceptors;
use GoDaddy\WordPress\MWC\Core\Traits\CanCheckIfOnboardingHasInitializedTrait;

/**
 * Configures default settings on brand new sites the first time they load.
 *
 * IMPORTANT: This interceptor needs to be loaded outside of the Commerce feature {@see Interceptors::$componentClasses}
 * in order to trigger early enough in the lifecycle. The Commerce integration doesn't load until at least page load #2,
 * due to configuring the store ID via {@see StoreIdInterceptor}. We need to load on page load #1, before onboarding has
 * initialized, as that's how we determine if this is the "first page load" or not (i.e. a brand new site). Therefore
 * all code in this class loads even if the Commerce integration never ends up loading for some reason.
 */
class SetDefaultsOnNewInstallsInterceptor extends AbstractInterceptor
{
    use CanCheckIfOnboardingHasInitializedTrait;

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeConfigureDefaultSettings'])
            ->execute();
    }

    /**
     * Configures default settings if onboarding has not been initialized (new installs).
     *
     * @internal
     * @return void
     */
    public function maybeConfigureDefaultSettings() : void
    {
        // if onboarding has NOT initialized then this is a brand new site and we can configure the default settings
        if (! $this->hasOnboardingInitialized()) {
            $this->configureDefaultSettings();
        }
    }

    /**
     * Configures the default settings on brand new installs.
     *
     * @return void
     */
    protected function configureDefaultSettings() : void
    {
        // the patch job does not have to run on brand new installs, so we'll mark it as having been run
        PatchProductCategoryIdsJobStatusHelper::setHasRun();
    }
}
