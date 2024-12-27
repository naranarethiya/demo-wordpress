<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Contact;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\GoDaddy\Adapters\GetLocationRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\GoDaddy\Adapters\ListLocationsRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Repositories\OrdersRepository;

class LocationsService
{
    /** @var CommerceContextContract */
    protected CommerceContextContract $commerceContext;

    /** @var Location[]|null used to store locations temporarily to prevent multiple API calls on the same request */
    protected ?array $locations;

    /**
     * The Locations Service constructor.
     */
    public function __construct(
        CommerceContextContract $commerceContext
    ) {
        $this->commerceContext = $commerceContext;
    }

    /**
     * Retrieves the locations.
     *
     * @return Location[]
     * @throws CommerceExceptionContract|Exception
     */
    public function getLocations() : array
    {
        if (! isset($this->locations)) {
            $adapter = ListLocationsRequestAdapter::getNewInstance($this->commerceContext->getStoreId());
            $request = $adapter->convertFromSource();

            $this->locations = $adapter->convertResponse($request->send());
        }

        return $this->locations;
    }

    /**
     * Retrieves a Location for a given channelId.
     *
     * @param string $channelId
     * @return Location
     * @throws CommerceExceptionContract|Exception
     */
    public function getLocation(string $channelId) : Location
    {
        $location = Location::getNewInstance(['channelId' => $channelId]);
        $adapter = GetLocationRequestAdapter::getNewInstance($location);
        $request = $adapter->convertFromSource();

        /** @var Location $location */
        $location = $adapter->convertToSource($request->send());

        return $location;
    }

    /**
     * Retrieves locations from local pickup shipping method instance.
     *
     * @param array<string, mixed> $data
     * @return Location[]
     */
    public function getLocationsForShippingMethodInstance(array $data) : array
    {
        $locations = [];

        foreach (ArrayHelper::wrap(ArrayHelper::get($data, 'godaddy_commerce_locations')) as $channelId) {
            try {
                $locations[] = $this->getLocation($channelId);
            } catch (CommerceExceptionContract|Exception $exception) {
            }
        }

        return $locations;
    }

    /**
     * Retrieves locations for an order.
     *
     * @param Order $order
     * @return Location[]
     * @throws Exception
     */
    public function getLocationsForOrder(Order $order) : array
    {
        $locations = [];

        foreach (OrdersRepository::getPickupLocations($order) as $shippingItemId => $channelId) {
            try {
                $locations[$shippingItemId] = $this->getLocation($channelId);
            } catch (CommerceExceptionContract|Exception $exception) {
            }
        }

        return $locations;
    }

    /**
     * Formats the address with a human-readable format.
     *
     * @param Address $address
     * @return string
     */
    public function formatAddress(Address $address) : string
    {
        $filterCallback = static function (string $addressPart) {
            return ! empty($addressPart);
        };

        $end = implode(' ', array_filter([$address->city, $address->state, $address->postalCode], $filterCallback));

        return implode(', ', array_filter([$address->address1, $address->address2, $end], $filterCallback));
    }

    /**
     * Gets a list of display-ready strings containing the contact phone number.
     *
     * @param Contact[] $contacts
     * @return string[]
     */
    public function getFormattedContacts(array $contacts) : array
    {
        $formattedContacts = [];

        foreach ($contacts as $contact) {
            if (Contact::TYPE_WORK === $contact->type && $contact->phone->phone) {
                $formattedContacts[] = $contact->phone->phone;
            }
        }

        return $formattedContacts;
    }
}
