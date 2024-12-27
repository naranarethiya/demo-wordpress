<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

/**
 * The Customer adapter.
 *
 * Converts between a native core Customer model and a Stripe customer object.
 */
class CustomerAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var Customer source Customer object */
    protected Customer $source;

    /**
     * Customer adapter constructor.
     *
     * @param Customer|null $customer source Customer object.
     */
    public function __construct(?Customer $customer = null)
    {
        $this->source = $customer ?? Customer::getNewInstance();
    }

    /**
     * Converts a core Customer model to a Stripe customer data array.
     *
     * @return array
     */
    public function convertFromSource() : array
    {
        $billingAddress = $this->source->getBillingAddress();
        $shippingAddress = $this->source->getShippingAddress();

        return [
            'address'  => $this->getSourceAddress($billingAddress),
            'email'    => (string) $this->source->getEmail(),
            'phone'    => $billingAddress->getPhone(),
            'name'     => $this->getSourceName($billingAddress),
            'shipping' => [
                'name'    => $this->getSourceName($shippingAddress),
                'phone'   => $shippingAddress->getPhone(),
                'address' => $this->getSourceAddress($shippingAddress),
            ],
        ];
    }

    /**
     * Accepts an Address and returns a populated array.
     *
     * @param Address|null $address
     *
     * @return array
     */
    protected function getSourceAddress(?Address $address) : array
    {
        return [
            'city'        => $address ? $address->getLocality() : '',
            'country'     => $address ? $address->getCountryCode() : '',
            'line1'       => $address ? ArrayHelper::get($addressLines = $address->getLines(), 0) : '',
            'line2'       => $address ? ArrayHelper::get($addressLines, 1) : '',
            'postal_code' => $address ? $address->getPostalCode() : '',
            'state'       => $address ? ArrayHelper::get($address->getAdministrativeDistricts(), 0) : '',
        ];
    }

    /**
     * Accepts an Address and returns either a full name or a business name.
     *
     * @param Address|null $address
     *
     * @return string
     */
    protected function getSourceName(?Address $address) : string
    {
        return $address ? (($addressFirstName = $address->getFirstName()) && ($addressLastName = $address->getLastName()))
            ? "{$addressFirstName} {$addressLastName}"
            : $address->getBusinessName() : '';
    }

    /**
     * Accepts a Stripe customer data array and updates the source Customer object.
     *
     * @param array|null $data
     *
     * @return Customer
     */
    public function convertToSource(array $data = null) : Customer
    {
        if ($id = ArrayHelper::get($data, 'id', null)) {
            $this->source->setRemoteId($id);
        }

        if ($address = ArrayHelper::get($data, 'address')) {
            $billingAddress = $this->getConvertedAddress($address);
            $billingAddress->setPhone(TypeHelper::string(ArrayHelper::get($data, 'phone', ''), ''));
            $this->source->setBillingAddress($billingAddress);
        }

        if (ArrayHelper::get($data, 'shipping')) {
            $shippingAddress = $this->getConvertedAddress(ArrayHelper::get($data, 'shipping.address'));
            $shippingAddress->setPhone(ArrayHelper::get($data, 'shipping.phone', ''));
            $this->source->setShippingAddress($shippingAddress);
        }

        $this->source->setEmail(TypeHelper::string(ArrayHelper::get($data, 'email'), '') ?: null);

        return $this->source;
    }

    /**
     * Accepts an address array and returns an Address object.
     *
     * @param array $address
     *
     * @return Address
     */
    protected function getConvertedAddress(array $address) : Address
    {
        return (new Address())
            ->setFirstname((string) $this->source->getFirstName())
            ->setLastName((string) $this->source->getLastName())
            ->setCountryCode(ArrayHelper::get($address, 'country', ''))
            ->setPostalCode(ArrayHelper::get($address, 'postal_code', ''))
            ->setAdministrativeDistricts([ArrayHelper::get($address, 'state', '')])
            ->setLocality(ArrayHelper::get($address, 'city', ''))
            ->setLines([
                ArrayHelper::get($address, 'line1', ''),
                ArrayHelper::get($address, 'line2', ''),
            ]);
    }
}
