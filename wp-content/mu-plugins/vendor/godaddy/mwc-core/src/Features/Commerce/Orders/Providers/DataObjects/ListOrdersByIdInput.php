<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

class ListOrdersByIdInput extends AbstractOrdersRequestInput
{
    /** @var non-empty-string[] */
    public array $ids = [];

    /**
     * Constructor.
     *
     * @param array{
     *     ids?: non-empty-string[],
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
