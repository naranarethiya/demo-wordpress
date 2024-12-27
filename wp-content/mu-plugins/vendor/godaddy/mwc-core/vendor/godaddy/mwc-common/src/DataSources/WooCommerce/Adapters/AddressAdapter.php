<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Address adapter.
 *
 * @method static static getNewInstance(array $source)
 */
class AddressAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> address source */
    private $source;

    /**
     * Address adapter constructor.
     *
     * @param array<string, mixed> $source
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * Converts a WooCommerce address into a native object.
     *
     * @return Address
     */
    public function convertFromSource() : Address
    {
        return Address::getNewInstance()
            ->setBusinessName(StringHelper::ensureScalar(ArrayHelper::get($this->source, 'company', '')))
            ->setFirstname(StringHelper::ensureScalar(ArrayHelper::get($this->source, 'first_name', '')))
            ->setLastname(StringHelper::ensureScalar(ArrayHelper::get($this->source, 'last_name', '')))
            ->setLines(
                array_filter([
                    StringHelper::ensureScalar(ArrayHelper::get($this->source, 'address_1', '')),
                    StringHelper::ensureScalar(ArrayHelper::get($this->source, 'address_2', '')),
                ]))
            ->setLocality(StringHelper::ensureScalar(ArrayHelper::get($this->source, 'city', '')))
            ->setAdministrativeDistricts(
                (array) StringHelper::ensureScalar(ArrayHelper::get($this->source, 'state', []))
            )
            ->setPostalCode(StringHelper::ensureScalar(ArrayHelper::get($this->source, 'postcode', '')))
            ->setCountryCode(StringHelper::ensureScalar(ArrayHelper::get($this->source, 'country', '')))
            ->setPhone(StringHelper::ensureScalar(ArrayHelper::get($this->source, 'phone', '')));
    }

    /**
     * Converts a native address into a WooCommerce address.
     *
     * @param Address|null $address
     * @return array
     */
    public function convertToSource(?Address $address = null) : array
    {
        if (! $address instanceof Address) {
            return $this->source;
        }

        $lines = $address->getLines();
        $districts = $address->getAdministrativeDistricts();

        $this->source = [
            'company'    => $address->getBusinessName(),
            'first_name' => $address->getFirstName(),
            'last_name'  => $address->getLastName(),
            'address_1'  => $lines[0] ?? '',
            'address_2'  => $lines[1] ?? '',
            'city'       => $address->getLocality(),
            'state'      => $districts[0] ?? '',
            'postcode'   => $address->getPostalCode(),
            'country'    => $address->getCountryCode(),
            'phone'      => $address->getPhone(),
        ];

        return $this->source;
    }
}
