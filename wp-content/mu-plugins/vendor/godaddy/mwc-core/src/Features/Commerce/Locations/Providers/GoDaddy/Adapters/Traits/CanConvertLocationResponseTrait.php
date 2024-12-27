<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\GoDaddy\Adapters\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Contact;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Phone;

trait CanConvertLocationResponseTrait
{
    /**
     * Converts address from response.
     *
     * @param array<mixed> $addressData
     *
     * @return Address[]
     */
    protected function convertAddress(array $addressData) : array
    {
        $data = [];

        if ($address = ArrayHelper::wrap(ArrayHelper::get($addressData, 'address'))) {
            $data['address'] = new Address([
                'address1'   => TypeHelper::string(ArrayHelper::get($address, 'addressLine1'), ''),
                'address2'   => TypeHelper::string(ArrayHelper::get($address, 'addressLine2'), ''),
                'city'       => TypeHelper::string(ArrayHelper::get($address, 'adminArea2'), ''),
                'state'      => TypeHelper::string(ArrayHelper::get($address, 'adminArea1'), ''),
                'postalCode' => TypeHelper::string(ArrayHelper::get($address, 'postalCode'), ''),
                'country'    => TypeHelper::string(ArrayHelper::get($address, 'countryCode'), ''),
            ]);
        }

        return $data;
    }

    /**
     * Converts response contacts.
     *
     * @param array<mixed> $contactsData
     *
     * @return array<string, array<int, Contact>>
     */
    protected function convertContacts(array $contactsData) : array
    {
        $contacts = [];

        foreach ($contactsData as $contact) {
            $contacts['contacts'][] = new Contact([
                'type'  => TypeHelper::string(ArrayHelper::get($contact, 'type'), ''),
                'phone' => $this->convertPhone(TypeHelper::array(ArrayHelper::get($contact, 'phone'), [])),
            ]);
        }

        return $contacts;
    }

    /**
     * Converts Phone from response.
     *
     * @param array<mixed> $data
     *
     * @return Phone
     */
    protected function convertPhone(array $data) : Phone
    {
        // @TODO: review this method on MWC-11826 {acastro1 2023.04.20}
        return new Phone([
            'phone' => TypeHelper::string(ArrayHelper::get($data, 'nationalNumber'), ''),
        ]);
    }

    /**
     * Converts {@see ResponseContract} data into a {@see Location} object.
     *
     * @param array<string, string> $locationData
     *
     * @return Location
     *
     * @throws Exception
     */
    protected function convertLocationResponse(array $locationData) : Location
    {
        $data = ArrayHelper::combine(
            [
                'channelId' => ArrayHelper::get($locationData, 'id'),
                'alias'     => ArrayHelper::get($locationData, 'location.alias', ArrayHelper::get($locationData, 'name', '')),
            ],
            $this->convertContacts(TypeHelper::array(ArrayHelper::get($locationData, 'location.contacts'), [])),
            $this->convertAddress(TypeHelper::array(ArrayHelper::get($locationData, 'location'), [])),
        );

        // @phpstan-ignore-next-line
        return new Location($data);
    }
}
