<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class CreateOrderInput extends AbstractDataObject
{
    public Order $order;

    public string $storeId;

    /**
     * Constructor.
     *
     * @param array{
     *     order: Order,
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
