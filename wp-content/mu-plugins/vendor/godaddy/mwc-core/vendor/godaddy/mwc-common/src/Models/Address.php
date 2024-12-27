<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * An object representation of an address.
 */
class Address extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;
    use CanGetNewInstanceTrait;

    /** @var string[] array of administrative districts */
    protected $administrativeDistricts;

    /** @var string business name */
    protected $businessName;

    /** @var string 2-letter Unicode CLDR country code */
    protected $countryCode;

    /** @var string first name */
    protected $firstName;

    /** @var string last name */
    protected $lastName;

    /** @var string[] address lines */
    protected $lines;

    /** @var string locality */
    protected $locality;

    /** @var string postcode */
    protected $postalCode;

    /** @var string phone number */
    protected $phone;

    /** @var string[] sub-localities */
    protected $subLocalities;

    /**
     * Gets the administrative districts.
     *
     * @return string[]
     */
    public function getAdministrativeDistricts() : array
    {
        return is_array($this->administrativeDistricts) ? $this->administrativeDistricts : [];
    }

    /**
     * Gets the business name.
     *
     * @return string
     */
    public function getBusinessName() : string
    {
        return is_string($this->businessName) ? $this->businessName : '';
    }

    /**
     * Gets the country code.
     *
     * @return string 2-letter Unicode CLDR country code
     */
    public function getCountryCode() : string
    {
        return is_string($this->countryCode) ? $this->countryCode : '';
    }

    /**
     * Gets the first name.
     *
     * @return string
     */
    public function getFirstName() : string
    {
        return is_string($this->firstName) ? $this->firstName : '';
    }

    /**
     * Gets the last name.
     *
     * @return string
     */
    public function getLastName() : string
    {
        return is_string($this->lastName) ? $this->lastName : '';
    }

    /**
     * Gets the address lines.
     *
     * @return string[]
     */
    public function getLines() : array
    {
        return is_array($this->lines) ? $this->lines : [];
    }

    /**
     * Gets the locality.
     *
     * @return string
     */
    public function getLocality() : string
    {
        return is_string($this->locality) ? $this->locality : '';
    }

    /**
     * Gets the postcode.
     *
     * @return string
     */
    public function getPostalCode() : string
    {
        return is_string($this->postalCode) ? $this->postalCode : '';
    }

    /**
     * Gets the phone number.
     *
     * @return string
     */
    public function getPhone() : string
    {
        return is_string($this->phone) ? $this->phone : '';
    }

    /**
     * Gets the sub-localities.
     *
     * @return string[]
     */
    public function getSubLocalities() : array
    {
        return is_array($this->subLocalities) ? $this->subLocalities : [];
    }

    /**
     * Sets the administrative districts.
     *
     * @param array $administrativeDistricts
     * @return $this
     */
    public function setAdministrativeDistricts(array $administrativeDistricts) : Address
    {
        $this->administrativeDistricts = $administrativeDistricts;

        return $this;
    }

    /**
     * Sets the business name.
     *
     * @param string $businessName
     * @return $this
     */
    public function setBusinessName(string $businessName) : Address
    {
        $this->businessName = $businessName;

        return $this;
    }

    /**
     * Sets the country code.
     *
     * @param string $countryCode a 2-letter Unicode CLDR country code
     * @return $this
     */
    public function setCountryCode(string $countryCode) : Address
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Sets the first name.
     *
     * @param string $firstName
     * @return $this
     */
    public function setFirstname(string $firstName) : Address
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Sets the last name.
     *
     * @param string $lastName
     * @return $this
     */
    public function setLastName(string $lastName) : Address
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Sets the address lines.
     *
     * @param string[] $lines
     * @return $this
     */
    public function setLines(array $lines) : Address
    {
        $this->lines = $lines;

        return $this;
    }

    /**
     * Sets the locality.
     *
     * @param string $locality
     * @return $this
     */
    public function setLocality(string $locality) : Address
    {
        $this->locality = $locality;

        return $this;
    }

    /**
     * Sets the postcode.
     *
     * @param string $postalCode
     * @return $this
     */
    public function setPostalCode(string $postalCode) : Address
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Sets the phone number.
     *
     * @param string $phone
     * @return $this
     */
    public function setPhone(string $phone) : Address
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Set the sub-localities.
     *
     * @param array $subLocalities
     * @return $this
     */
    public function setSubLocalities(array $subLocalities) : Address
    {
        $this->subLocalities = $subLocalities;

        return $this;
    }
}
