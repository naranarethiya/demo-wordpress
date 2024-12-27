<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class OrderOutput extends AbstractDataObject
{
    public Order $order;

    /**
     * @param array{order: Order} $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
