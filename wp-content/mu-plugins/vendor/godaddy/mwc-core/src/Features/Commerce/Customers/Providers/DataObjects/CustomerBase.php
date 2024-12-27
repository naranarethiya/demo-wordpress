<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Phone;

class CustomerBase extends AbstractDataObject
{
    public ?string $customerId;
    public string $firstName;
    public string $lastName;
    public string $updatedAt;

    /** @var Email[] */
    public array $emails;

    /** @var Phone[] */
    public array $phones = [];

    /** @var Address[] */
    public array $addresses = [];

    /**
     * Creates a new data object.
     *
     * @param array{
     *     customerId: ?string,
     *     firstName: string,
     *     lastName: string,
     *     updatedAt: string,
     *     emails: Email[],
     *     phones?: Phone[],
     *     addresses?: Address[]
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
