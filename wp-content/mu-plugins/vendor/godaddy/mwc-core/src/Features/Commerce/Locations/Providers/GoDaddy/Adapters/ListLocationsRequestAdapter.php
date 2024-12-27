<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Http\GoDaddyRequest;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\GoDaddy\Adapters\Traits\CanConvertLocationResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

class ListLocationsRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertLocationResponseTrait;

    protected string $storeId;

    /**
     * The ListLocationsRequestAdapter constructor.
     *
     * @param string $storeId
     */
    public function __construct(string $storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * @param ResponseContract $response
     * @return Location[]
     * @throws Exception
     */
    public function convertResponse(ResponseContract $response) : array
    {
        $locations = [];

        $responseLocations = ArrayHelper::wrap($response->getBody());

        foreach ($responseLocations as $responseLocation) {
            if (ArrayHelper::get($responseLocation, 'type', '') === Location::TYPE_RETAIL) {
                $locations[] = $this->convertLocationResponse($responseLocation);
            }
        }

        return $locations;
    }

    /**
     * @return RequestContract
     */
    public function convertFromSource() : RequestContract
    {
        return GoDaddyRequest::withAuth()
            ->setUrl(ManagedWooCommerceRepository::getApiUrl())
            ->setPath('/channels')
            ->setQuery(['registeredStores.storeId' => $this->storeId]);
    }
}
