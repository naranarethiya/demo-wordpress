<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Helpers;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;

class AddressHelper
{
    /**
     * Sanitizes the address for communicating with the commerce API.
     *
     * @return Address
     */
    public static function sanitizeAddress(Address $address) : Address
    {
        $address->address1 = TypeHelper::string(preg_replace("/[^a-zA-Z0-9\-.,'#*@\/& ]/", '', StringHelper::substring($address->address1, 0, 100)), '');
        $address->address2 = TypeHelper::string(preg_replace("/[^a-zA-Z0-9\-.,'#*@\/& ]/", '', StringHelper::substring($address->address2, 0, 100)), '');
        $address->city = TypeHelper::string(preg_replace("/[^a-zA-Z0-9\-.,' ]/", '', StringHelper::substring($address->city, 0, 34)), '');
        $address->state = TypeHelper::string(preg_replace("/[^a-zA-Z0-9\- ]/", '', StringHelper::substring($address->state, 0, 43)), '');
        $address->postalCode = TypeHelper::string(preg_replace("/[^a-zA-Z0-9 .\-]/", '', StringHelper::substring($address->postalCode, 0, 10)), '');
        $address->country = TypeHelper::string(preg_replace('/[^A-Z][^A-Z]$/', '', $address->country), '');

        return $address;
    }
}
