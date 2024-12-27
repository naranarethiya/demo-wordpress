<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Models\Weight;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\DataSources\Adapters\ShippingRateAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http\Request;
use GoDaddy\WordPress\MWC\Shipping\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Shipping\Contracts\CalculateShippingRatesOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\ShippingException;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\CreatedPackageStatus;

class CalculateShippingRatesRequestAdapter extends AbstractGatewayRequestAdapter
{
    protected const ADDRESS_TYPE_ORIGIN = 'origin';

    protected const ADDRESS_TYPE_DESTINATION = 'destination';

    protected CalculateShippingRatesOperationContract $operation;

    public function __construct(CalculateShippingRatesOperationContract $operation)
    {
        $this->operation = $operation;
    }

    /** {@inheritdoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setPath('/shipping/proxy/shipengine/v1/rates')
            ->setMethod('post')
            ->setBody([
                'externalAccountId' => $this->operation->getAccount()->getId(),
                'data'              => [
                    'shipment'     => $this->getShipmentDataForRequest(),
                    'rate_options' => [
                        'carrier_ids' => $this->getCarrierIdsForRequest(),
                    ],
                ],
            ]);
    }

    /**
     * Gets shipment data in the format needed for the request.
     *
     * @return array<string, mixed>
     */
    protected function getShipmentDataForRequest() : array
    {
        return [
            'validate_address' => 'no_validation',
            'ship_to'          => $this->getShipToAddressForRequest($this->operation->getShipment()->getDestinationAddress()),
            'ship_from'        => $this->getShipFromAddressForRequest($this->operation->getShipment()->getOriginAddress()),
            'packages'         => $this->getPackagesForRequest(),
        ];
    }

    /**
     * Gets data from the given Address to be used as the ship_to address for the request.
     *
     * The destination address has address_residential_indicator set to "yes".
     *
     * @param Address|null $address
     * @return array<string, string|null>|null
     */
    protected function getShipToAddressForRequest(?Address $address) : ?array
    {
        return $this->getAddressForRequest($address, static::ADDRESS_TYPE_DESTINATION);
    }

    /**
     * Gets data from the given Address object in the format needed for the request.
     *
     * @param Address|null $address
     * @param static::ADDRESS_TYPE* $type
     * @return array<string, string|null>|null
     */
    protected function getAddressForRequest(?Address $address, string $type) : ?array
    {
        if (! $address) {
            return null;
        }

        $data = [
            'name'                          => null,
            'company_name'                  => null,
            'phone'                         => $address->getPhone(),
            'address_line1'                 => null,
            'address_line2'                 => null,
            'address_line3'                 => null,
            'city_locality'                 => $address->getLocality(),
            'state_province'                => $this->getAddressStateOrProvinceForRequest($address),
            'postal_code'                   => $address->getPostalCode(),
            'country_code'                  => $address->getCountryCode(),
            'address_residential_indicator' => null,
        ];

        $addressNames = $this->getAddressNamesForRequest($address, $type);
        $addressLines = $this->getAddressLinesForRequest($address);
        $addressResidentialIndicator = $this->getAddressResidentialIndicator($addressNames, $type);

        try {
            $data = ArrayHelper::combine(
                $data,
                $addressNames,
                $addressLines,
                $addressResidentialIndicator
            );
        } catch (Exception $exception) {
            // ignore Exception instance that is never thrown if both parameters to ArrayHelper::combine() are arrays
        }

        return array_filter($data, static function ($item) {
            return is_string($item) || is_null($item);
        });
    }

    /**
     * Gets the value of the name and company_name fields for an address in the request.
     *
     * @param Address $address
     * @param static::ADDRESS_TYPE* $type
     * @return array<string, string|null>
     */
    protected function getAddressNamesForRequest(Address $address, string $type) : array
    {
        $fullName = trim("{$address->getFirstName()} {$address->getLastName()}") ?: null;
        $businessName = $address->getBusinessName() ?: null;

        if ($type === static::ADDRESS_TYPE_ORIGIN) {
            $companyName = $businessName ?: $fullName;
        } else {
            $companyName = $fullName ? $businessName : null;
        }

        return [
            'name'         => $fullName ?: $businessName,
            'company_name' => $companyName,
        ];
    }

    /**
     * Gets the value of the residential indicator field for an address in the request.
     *
     * @param array<string, string|null> $addressNames
     * @param static::ADDRESS_TYPE* $type
     * @return array<string, string|null>
     */
    protected function getAddressResidentialIndicator(array $addressNames, string $type) : array
    {
        $addressResidentialIndicator = 'no';

        if ($type === static::ADDRESS_TYPE_DESTINATION && ! ArrayHelper::get($addressNames, 'company_name')) {
            $addressResidentialIndicator = 'yes';
        }

        return ['address_residential_indicator' => $addressResidentialIndicator];
    }

    /**
     * Gets the state or province value for the given address.
     *
     * @param Address $address
     * @return string
     */
    protected function getAddressStateOrProvinceForRequest(Address $address) : string
    {
        return (string) StringHelper::ensureScalar(ArrayHelper::get($address->getAdministrativeDistricts(), '0'));
    }

    /**
     * Gets the address line fields for an address in the request.
     *
     * @param Address $address
     * @return array<string, string>
     */
    protected function getAddressLinesForRequest(Address $address) : array
    {
        $lines = [];

        foreach (array_slice($address->getLines(), 0, 3) as $line) {
            $lines['address_line'.(count($lines) + 1)] = $line;
        }

        return $lines;
    }

    /**
     * Gets data from the given Address to be used as the ship_from address for the request.
     *
     * The origin address has address_residential_indicator is set to “no”.
     *
     * @param Address|null $address
     * @return array<string, string|null>|null
     */
    protected function getShipFromAddressForRequest(?Address $address) : ?array
    {
        return $this->getAddressForRequest($address, static::ADDRESS_TYPE_ORIGIN);
    }

    /**
     * Gets package data for the request.
     *
     * @return array<array{weight: array{value: float, unit: ?string}}>
     */
    protected function getPackagesForRequest() : array
    {
        $packages = [];

        foreach ($this->operation->getShipment()->getPackages() as $package) {
            if (! $weight = $package->getWeight()) {
                continue;
            }

            $packages[] = [
                'weight' => $this->getWeightForRequest($weight),
            ];
        }

        return $packages;
    }

    /**
     * Gets the weight data for the request.
     *
     * @param Weight $weight
     * @return array{value: float, unit: ?string}
     */
    protected function getWeightForRequest(Weight $weight) : array
    {
        return [
            'value' => $weight->getValue(),
            'unit'  => $this->getWeightUnitForRequest($weight),
        ];
    }

    /**
     * Converts the unit of the given weight object into one of the units accepted by ShipEngine.
     *
     * @param Weight $weight
     * @return ?string
     */
    protected function getWeightUnitForRequest(Weight $weight) : ?string
    {
        switch ($weight->getUnitOfMeasurement()) {
            case 'oz':
                return 'ounce';
            case 'g':
                return 'gram';
            case 'kg':
                return 'kilogram';
            case 'lbs':
                return 'pound';
            default:
                return null;
        }
    }

    /**
     * Gets the IDs of the carriers for this request.
     *
     * @return string[]
     */
    protected function getCarrierIdsForRequest() : array
    {
        return array_map(static function (CarrierContract $carrier) {
            return $carrier->getId();
        }, $this->operation->getCarriers());
    }

    /** {@inheritdoc} */
    protected function convertResponse(ResponseContract $response)
    {
        $this->operation->getShipment()->setRemoteId($this->getShipmentRemoteId($response));
        $this->operation->setShippingRates(...$this->getShippingRates($response));

        foreach ($this->operation->getShipment()->getPackages() as $package) {
            $package->setStatus(new CreatedPackageStatus());
        }

        return $this->operation;
    }

    /**
     * Gets the remote ID for the new shipment.
     *
     * @param ResponseContract $response
     * @return string
     * @throws ShippingException
     */
    protected function getShipmentRemoteId(ResponseContract $response) : string
    {
        if (! $remoteId = ArrayHelper::getStringValueForKey(ArrayHelper::wrap($response->getBody()), 'shipment_id')) {
            throw new ShippingException('The response does not include a shipment ID.');
        }

        return $remoteId;
    }

    /**
     * Gets a list of {@see ShippingRateContract} objects from the response.
     *
     * @param ResponseContract $response
     * @return ShippingRateContract[]
     * @throws ShippingException
     */
    protected function getShippingRates(ResponseContract $response) : array
    {
        $data = ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'rate_response.rates');

        if (is_null($data)) {
            throw new ShippingException('The response does not include shipping rates data.');
        }

        $shippingRates = [];

        foreach (ArrayHelper::wrap($data) as $shippingRateData) {
            if (! ArrayHelper::accessible($shippingRateData)) {
                throw new ShippingException('The response includes invalid shipping rates data.');
            }

            $shippingRates[] = ShippingRateAdapter::getNewInstance($shippingRateData)
                ->setCarriers($this->operation->getCarriers())
                ->convertFromSource();
        }

        return $shippingRates;
    }
}
