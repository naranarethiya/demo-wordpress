<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Traits\CanCheckIfOnboardingHasInitializedTrait;

/**
 * Interceptor to handle the site (default) store ID.
 */
class StoreIdInterceptor extends AbstractInterceptor
{
    use CanCheckIfOnboardingHasInitializedTrait;

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeSetDefaultStoreId'])
            ->execute();
    }

    /**
     * Maybe sets the default store ID.
     *
     * @internal
     *
     * @return void
     */
    public function maybeSetDefaultStoreId() : void
    {
        if ($this->shouldDetermineDefaultStoreId()) {
            $this->setDefaultStoreId();
        }
    }

    /**
     * Determines if we should set the default store ID.
     *
     * @return bool
     */
    protected function shouldDetermineDefaultStoreId() : bool
    {
        try {
            $shouldDetermineDefaultSiteId = $this->isEssentialsPlan()
                ? true
                : Configuration::get('godaddy.store.shouldDetermineDefaultSiteId', false);

            return true === $shouldDetermineDefaultSiteId
                && ! WordPressRepository::isAjax()
                && ! $this->hasOnboardingInitialized()
                && empty(Commerce::getStoreId());
        } catch (Exception $e) {
            // catch all exceptions in a hook callback
            return false;
        }
    }

    /**
     * Determines if current plan is an Essentials Plan.
     *
     * @throws PlatformRepositoryException
     */
    protected function isEssentialsPlan() : bool
    {
        $platformRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();

        return in_array($platformRepository->getPlan()->getName(), ['essentials', 'essentialsCA', 'essentials_GDGCPP'], true);
    }

    /**
     * Sets the default store ID.
     *
     * @return void
     */
    protected function setDefaultStoreId() : void
    {
        try {
            $storeRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getStoreRepository();
            $defaultStoreId = $storeRepository->determineDefaultStoreId();

            if (empty($defaultStoreId)) {
                return;
            }

            $storeRepository->setDefaultStoreId($defaultStoreId);
        } catch (Exception $exception) {
            new SentryException('Could not set the default store ID.', $exception);
        }
    }
}
