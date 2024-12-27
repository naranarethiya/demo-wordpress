<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class OrdersListOutput extends AbstractDataObject
{
    /**
     * @var Order[]
     */
    public array $orders = [];

    /**
     * Constructor.
     *
     * @param array{
     *     orders?: Order[]
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
