<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class UpsertSummaryInput extends AbstractDataObject
{
    public string $storeId;
    public Summary $summary;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     storeId: string,
     *     summary: Summary,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
