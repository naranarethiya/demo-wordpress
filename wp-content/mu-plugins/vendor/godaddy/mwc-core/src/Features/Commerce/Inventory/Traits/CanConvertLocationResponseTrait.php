<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;

/**
 * Adds an adapter class the ability to convert a location endpoint response to a {@see Location} object.
 */
trait CanConvertLocationResponseTrait
{
    use CanConvertResponseTrait;

    /**
     * Converts {@see ResponseContract} data into a {@see Location} object.
     *
     * @param array<string, mixed> $inventoryLocationData
     *
     * @return Location
     *
     * @throws Exception
     */
    protected function convertLocationResponse(array $inventoryLocationData) : Location
    {
        $data = ArrayHelper::combine(
            [
                'inventoryLocationId' => ArrayHelper::get($inventoryLocationData, 'inventoryLocationId'),
                'type'                => ArrayHelper::get($inventoryLocationData, 'type'),
                'active'              => ArrayHelper::get($inventoryLocationData, 'active'),
                'priority'            => ArrayHelper::get($inventoryLocationData, 'priority'),
            ],
            $this->convertExternalIds($inventoryLocationData),
            $this->convertAddress($inventoryLocationData),
            $this->convertDateTime($inventoryLocationData, 'createdAt'),
            $this->convertDateTime($inventoryLocationData, 'updatedAt'),
        );

        // @phpstan-ignore-next-line
        return new Location($data);
    }

    /**
     * Converts the Location response address instance (if any).
     *
     * @param array<string, mixed> $body
     * @return array<string, Address>
     */
    protected function convertAddress(array $body) : array
    {
        $data = [];

        if ($address = ArrayHelper::wrap(ArrayHelper::get($body, 'address'))) {
            $data['address'] = new Address([
                'address1'   => TypeHelper::string(ArrayHelper::get($address, 'address1'), ''),
                'address2'   => TypeHelper::string(ArrayHelper::get($address, 'address2'), ''),
                'city'       => TypeHelper::string(ArrayHelper::get($address, 'city'), ''),
                'state'      => TypeHelper::string(ArrayHelper::get($address, 'state'), ''),
                'postalCode' => TypeHelper::string(ArrayHelper::get($address, 'postalCode'), ''),
                'country'    => TypeHelper::string(ArrayHelper::get($address, 'country'), ''),
            ]);
        }

        return $data;
    }
}
