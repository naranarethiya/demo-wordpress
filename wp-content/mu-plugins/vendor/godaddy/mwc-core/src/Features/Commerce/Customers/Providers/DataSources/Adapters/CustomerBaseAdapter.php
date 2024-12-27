<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataSources\Adapters;

use DateTime;
use DateTimeInterface;
use GoDaddy\WordPress\MWC\Common\Contracts\HasStringRemoteIdentifierContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringRemoteIdentifierTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\CustomerBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\Email;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address as CommerceCustomerAddress;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Phone;

class CustomerBaseAdapter implements DataSourceAdapterContract, HasStringRemoteIdentifierContract
{
    use HasStringRemoteIdentifierTrait;
    use CanGetNewInstanceTrait;

    /**
     * No-op.
     */
    public function convertFromSource()
    {
        // No-op
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource(?CustomerContract $customer = null) : ?CustomerBase
    {
        if (! $customer) {
            return null;
        }

        return new CustomerBase([
            'customerId' => $this->getRemoteId(),
            'firstName'  => $customer->getFirstName() ?? '',
            'lastName'   => $customer->getLastName() ?? '',
            'updatedAt'  => $this->getCurrentTimestampFormatted(),
            'emails'     => $this->convertToEmails($customer),
            'phones'     => $this->convertToPhones($customer),
            'addresses'  => $this->convertToAddresses($customer),
        ]);
    }

    /**
     * Gets current time using ATOM format.
     *
     * @return string
     */
    protected function getCurrentTimestampFormatted() : string
    {
        return (new DateTime())->format(DateTimeInterface::ATOM);
    }

    /**
     * Convert customer email to array of commerce customer Email objects.
     *
     * @param CustomerContract $customer
     *
     * @return Email[]
     */
    protected function convertToEmails(CustomerContract $customer) : array
    {
        if (! $email = $customer->getEmail()) {
            return [];
        }

        return [
            new Email(['email' => $email]),
        ];
    }

    /**
     * Convert customer billing phone to array of commerce customer Phone objects.
     *
     * Phones will only be set when updating an existing customer (having a remote ID), as otherwise we may get
     * unintended merging of different customers.
     *
     * @param CustomerContract $customer
     *
     * @return Phone[]
     */
    protected function convertToPhones(CustomerContract $customer) : array
    {
        if (! $phone = $customer->getBillingAddress()->getPhone()) {
            return [];
        }

        return [
            new Phone([
                'phone' => $phone,
                'label' => 'Billing',
            ]),
        ];
    }

    /**
     * Convert customer addresses to array of commerce customer Address objects.
     *
     * @param CustomerContract $customer
     *
     * @return CommerceCustomerAddress[]
     */
    protected function convertToAddresses(CustomerContract $customer) : array
    {
        $addressAdapter = AddressAdapter::getNewInstance();

        return array_filter([
            $addressAdapter->convertToSource($customer->getBillingAddress()),
        ]);
    }
}
