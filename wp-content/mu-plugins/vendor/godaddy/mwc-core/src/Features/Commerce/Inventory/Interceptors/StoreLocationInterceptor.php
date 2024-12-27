<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingLocationInformationException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationsServiceContract;

/**
 * Interceptor to handle the store location.
 */
class StoreLocationInterceptor extends AbstractInterceptor
{
    /**
     * @var LocationMappingServiceContract
     */
    protected LocationMappingServiceContract $locationMappingService;

    /**
     * @var LocationsServiceContract
     */
    protected LocationsServiceContract $locationsService;

    /**
     * @param LocationsServiceContract $locationsService
     * @param LocationMappingServiceContract $locationMappingService
     */
    public function __construct(LocationsServiceContract $locationsService, LocationMappingServiceContract $locationMappingService)
    {
        $this->locationsService = $locationsService;
        $this->locationMappingService = $locationMappingService;
    }

    /**
     * Adds the hook to register.
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
                ->setGroup('admin_init')
                ->setHandler([$this, 'storeLocation'])
                ->execute();
    }

    /**
     * Attempts to store location.
     *
     * @internal
     */
    public function storeLocation() : void
    {
        // if we already mapped a location, we're done
        if ($this->locationMappingService->getRemoteId()) {
            return;
        }

        try {
            $locations = $this->locationsService->listLocations()->getLocations();

            $locationId = null;
            $priorities = [];

            foreach ($locations as $location) {
                $priorities[] = $location->priority;
            }

            // choose the location ID with top priority (the lowest number)
            if (! empty($priorities)) {
                $highestPriority = min($priorities);
                foreach ($locations as $location) {
                    if ($location->priority === $highestPriority) {
                        $locationId = TypeHelper::string($location->inventoryLocationId, '');
                        break;
                    }
                }
            }

            // if there is a location available, persist its ID then we're done
            if ($locationId) {
                $this->locationMappingService->saveRemoteId($locationId);

                return;
            }

            // no locations otherwise available, so create one
            $this->locationsService->createOrUpdateLocation();
        } catch (MissingLocationInformationException $exception) {
            // do not report to Sentry. A location will be auto-created for the site by the service when the first inventory is created
        } catch (CommerceExceptionContract|Exception $exception) {
            SentryException::getNewInstance(sprintf('An error occurred trying to associate the site with an inventory location: %s', $exception->getMessage()), $exception);
        }
    }
}
