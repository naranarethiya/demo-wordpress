<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address as CommerceCustomerAddress;

class AddressAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     */
    public function convertFromSource()
    {
        // No-op
    }

    /**
     * Convert a common address to a commerce customer address.
     *
     * @param Address|null $address
     *
     * @return CommerceCustomerAddress|null
     */
    public function convertToSource(?Address $address = null) : ?CommerceCustomerAddress
    {
        if (! $address) {
            return null;
        }

        $convertedAddressData = [
            'address1'   => TypeHelper::string(ArrayHelper::get($address->getLines(), '0'), ''),
            'address2'   => TypeHelper::string(ArrayHelper::get($address->getLines(), '1'), ''),
            'city'       => $address->getLocality(),
            'state'      => TypeHelper::string(ArrayHelper::get($address->getAdministrativeDistricts(), '0'), ''),
            'postalCode' => $address->getPostalCode(),
            'country'    => $address->getCountryCode(),
        ];

        if (empty(array_filter($convertedAddressData))) {
            return null;
        }

        return new CommerceCustomerAddress($convertedAddressData);
    }
}
