<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class CustomerOutput extends AbstractDataObject
{
    /** @var non-empty-string */
    public string $customerId;

    /**
     * {@inheritDoc}
     * @param array{customerId: non-empty-string} $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
