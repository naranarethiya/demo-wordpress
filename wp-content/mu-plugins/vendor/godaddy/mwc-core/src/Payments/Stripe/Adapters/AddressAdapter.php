<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class AddressAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var Address */
    protected $source;

    /**
     * @param Address $address
     */
    public function __construct(Address $address)
    {
        $this->source = $address;
    }

    /**
     * Converts the source MWC address to Stripe API data.
     *
     * @return array<string, mixed>
     */
    public function convertFromSource() : array
    {
        $addressLines = $this->source->getLines();

        return [
            'address' => [
                'city'        => $this->source->getLocality(),
                'country'     => $this->source->getCountryCode(),
                'line1'       => ArrayHelper::get($addressLines, '0', ''),
                'line2'       => ArrayHelper::get($addressLines, '1', ''),
                'postal_code' => $this->source->getPostalCode(),
                'state'       => ArrayHelper::get($this->source->getAdministrativeDistricts(), '0', ''),
            ],
            'name'  => trim($this->source->getFirstName().' '.$this->source->getLastName()),
            'phone' => $this->source->getPhone(),
        ];
    }

    /**
     * No-op method.
     */
    public function convertToSource() : void
    {
        // no-op
    }
}
