<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class UpdateCustomerInput extends AbstractDataObject
{
    public string $storeId;
    public CustomerBase $customer;
    public string $customerSource = 'COMMERCE';

    /** @var non-empty-string */
    public string $customerId;

    /**
     * @param array{
     *     storeId: string,
     *     customer: CustomerBase,
     *     customerSource?: string,
     *     customerId: non-empty-string,
     *  } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
