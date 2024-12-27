<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\Request\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * @method static static getNewInstance(array $source)
 */
class AddressAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> */
    protected $source;

    /**
     * @param array<string, mixed> $source
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * Converts the source data into an Address object.
     *
     * @return Address
     */
    public function convertFromSource() : Address
    {
        return (new Address())
            ->setAdministrativeDistricts($this->convertAdministrativeDistrictsFromSource())
            ->setBusinessName($this->getStringValue($this->source, 'businessName'))
            ->setCountryCode($this->getStringValue($this->source, 'countryCode'))
            ->setFirstname($this->getStringValue($this->source, 'firstName'))
            ->setLastName($this->getStringValue($this->source, 'lastName'))
            ->setLines($this->convertLinesFromSource())
            ->setLocality($this->getStringValue($this->source, 'locality'))
            ->setPhone($this->getStringValue($this->source, 'phoneNumber'))
            ->setPostalCode($this->getStringValue($this->source, 'postalCode'))
            ->setSubLocalities($this->convertSubLocalitiesFromSource());
    }

    /**
     * Gets an array of administrative districts from source.
     *
     * @return non-empty-string[]
     */
    protected function convertAdministrativeDistrictsFromSource() : array
    {
        return $this->getArrayOfStringsValue($this->source, 'administrativeDistricts');
    }

    /**
     * Gets the value at specified key as an array of non-empty strings.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @return non-empty-string[]
     */
    protected function getArrayOfStringsValue(array $data, string $key) : array
    {
        $strings = [];

        foreach (ArrayHelper::wrap(ArrayHelper::get($data, $key)) as $value) {
            if (is_string($value) && trim($value)) {
                $strings[] = trim($value);
            }
        }

        return $strings;
    }

    /**
     * Gets the value at specified key as a string.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @return string
     */
    protected function getStringValue(array $data, string $key) : string
    {
        return (string) StringHelper::ensureScalar(ArrayHelper::get($data, $key));
    }

    /**
     * Gets an array of address lines from source.
     *
     * @return non-empty-string[]
     */
    protected function convertLinesFromSource() : array
    {
        return $this->getArrayOfStringsValue($this->source, 'lines');
    }

    /**
     * Gets an array of sub localities from source.
     *
     * @return non-empty-string[]
     */
    protected function convertSubLocalitiesFromSource() : array
    {
        return $this->getArrayOfStringsValue($this->source, 'subLocalities');
    }

    /**
     * Converts the given address to an array of data.
     *
     * Returns null if no address is given.
     *
     * @param Address|null $address
     * @return array<string, string|string[]|null>|null
     */
    public function convertToSource(?Address $address = null) : ?array
    {
        return $address ? $this->convertAddressToSource($address) : null;
    }

    /**
     * Converts the given address to an array of data.
     *
     * Returns null if all fields are empty.
     *
     * @param Address $address
     * @return array<string, string|string[]|null>|null
     */
    protected function convertAddressToSource(Address $address) : ?array
    {
        $data = [
            'administrativeDistricts' => $address->getAdministrativeDistricts(),
            'businessName'            => $address->getBusinessName() ?: null,
            'countryCode'             => $address->getCountryCode() ?: null,
            'firstName'               => $address->getFirstName() ?: null,
            'lastName'                => $address->getLastName() ?: null,
            'lines'                   => $address->getLines(),
            'locality'                => $address->getLocality() ?: null,
            'phoneNumber'             => $address->getPhone() ?: null,
            'postalCode'              => $address->getPostalCode() ?: null,
            'subLocalities'           => $address->getSubLocalities(),
        ];

        return $this->arrayHasNonEmptyFields($data) ? $data : null;
    }

    /**
     * Determines whether the given associative array has any non-empty values.
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    protected function arrayHasNonEmptyFields(array $data) : bool
    {
        return (bool) ArrayHelper::where($data, function ($value) {
            return ! empty($value);
        });
    }
}
