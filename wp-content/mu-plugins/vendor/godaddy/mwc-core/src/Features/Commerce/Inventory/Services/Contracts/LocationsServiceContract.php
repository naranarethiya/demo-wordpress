<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLocationResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListLocationsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadLocationResponseContract;

interface LocationsServiceContract
{
    /**
     * Create or update the site's location.
     *
     * @return CreateOrUpdateLocationResponseContract
     *
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function createOrUpdateLocation() : CreateOrUpdateLocationResponseContract;

    /**
     * Read the site's location.
     *
     * @return ReadLocationResponseContract
     *
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function readLocation() : ReadLocationResponseContract;

    /**
     * List the site's available locations.
     *
     * @return ListLocationsResponseContract
     *
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function listLocations() : ListLocationsResponseContract;
}
