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

class GetLocationRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertLocationResponseTrait;

    protected Location $source;

    /**
     * The Adapter constructor.
     * @param Location $source
     */
    public function __construct(Location $source)
    {
        $this->source = $source;
    }

    /**
     * Converts channel location response.
     *
     * @param ResponseContract $response
     * @return Location
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : Location
    {
        return $this->convertLocationResponse(ArrayHelper::wrap($response->getBody()));
    }

    public function convertFromSource() : RequestContract
    {
        return GoDaddyRequest::withAuth()
                             ->setUrl(ManagedWooCommerceRepository::getApiUrl())
                             ->setPath("/channels/{$this->source->channelId}");
    }
}
