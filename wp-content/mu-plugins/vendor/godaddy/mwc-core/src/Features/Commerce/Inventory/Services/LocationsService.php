<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingLocationInformationException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingLocationRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListLocationsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLocationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataSources\Adapters\LocationAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLocationResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListLocationsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadLocationResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\CreateOrUpdateLocationResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ListLocationsResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;

class LocationsService implements LocationsServiceContract
{
    protected CommerceContextContract $commerceContext;
    protected LocationMappingServiceContract $locationMappingService;
    protected InventoryProviderContract $provider;

    /**
     * The Locations Service constructor.
     */
    public function __construct(
        CommerceContextContract $commerceContext,
        LocationMappingServiceContract $locationMappingService,
        InventoryProviderContract $provider
    ) {
        $this->commerceContext = $commerceContext;
        $this->locationMappingService = $locationMappingService;
        $this->provider = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateLocation() : CreateOrUpdateLocationResponseContract
    {
        $existingRemoteId = $this->locationMappingService->getRemoteId();

        $upsertLocationInput = $this->getUpsertLocationInput($existingRemoteId);

        if (! $this->hasValidAddress($upsertLocationInput)) {
            throw MissingLocationInformationException::withDefaultMessage();
        }

        $location = $this->provider->locations()->createOrUpdate($upsertLocationInput);

        if (! $location->inventoryLocationId) {
            throw MissingLocationRemoteIdException::withDefaultMessage();
        }

        if (! $existingRemoteId) {
            $this->locationMappingService->saveRemoteId($location->inventoryLocationId);
        }

        return new CreateOrUpdateLocationResponse($location);
    }

    /**
     * Determines whether the input has a valid address inside its location object.
     *
     * @param UpsertLocationInput $input
     * @return bool
     */
    protected function hasValidAddress(UpsertLocationInput $input) : bool
    {
        $data = $input->toArray();

        $address1 = ArrayHelper::get($data, 'location.address.address1', null);
        $city = ArrayHelper::get($data, 'location.address.city', null);
        $postalCode = ArrayHelper::get($data, 'location.address.postalCode', null);

        return
            ! empty($address1) &&
            ! empty($city) &&
            ! empty($postalCode);
    }

    /**
     * Gets the upsert input for the store's location.
     *
     * @param string|null $remoteId
     *
     * @return UpsertLocationInput
     */
    protected function getUpsertLocationInput(?string $remoteId) : UpsertLocationInput
    {
        return new UpsertLocationInput([
            'storeId'  => $this->commerceContext->getStoreId(),
            'location' => $this->buildLocationData($remoteId),
        ]);
    }

    /**
     * Builds location data.
     *
     * @param string|null $remoteId
     *
     * @return Location
     */
    protected function buildLocationData(?string $remoteId) : Location
    {
        $location = LocationAdapter::getNewInstance()->convertToSource();

        $location->inventoryLocationId = $remoteId;
        $location->externalIds = [
            new ExternalId([
                'type'  => 'STORE',
                'value' => $this->commerceContext->getStoreId(),
            ]),
        ];

        return $location;
    }

    /**
     * {@inheritDoc}
     */
    public function readLocation() : ReadLocationResponseContract
    {
        // TODO: Implement method in MWC-11158. {ssmith1 2023-03-20}
        throw new Exception('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function listLocations() : ListLocationsResponseContract
    {
        $locations = $this->provider->locations()->list(new ListLocationsInput([
            'storeId' => $this->commerceContext->getStoreId(),
        ]));

        return new ListLocationsResponse($locations);
    }
}
