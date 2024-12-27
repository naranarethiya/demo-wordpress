<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects;

class Address extends AbstractDataObject
{
    public string $address1;
    public string $address2;
    public string $city;
    public string $state;
    public string $postalCode;
    public string $country;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     address1: string,
     *     address2: string,
     *     city: string,
     *     state: string,
     *     postalCode: string,
     *     country: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
