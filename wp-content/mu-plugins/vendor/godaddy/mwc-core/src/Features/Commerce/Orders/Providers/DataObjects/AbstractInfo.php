<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * A base object for data objects that hold customer information (e.g., billing or shipping) associated with an order.
 */
abstract class AbstractInfo extends AbstractDataObject
{
    public string $firstName = '';
    public string $lastName = '';

    /**
     * Constructor.
     *
     * @param array{
     *     firstName: string,
     *     lastName?: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
