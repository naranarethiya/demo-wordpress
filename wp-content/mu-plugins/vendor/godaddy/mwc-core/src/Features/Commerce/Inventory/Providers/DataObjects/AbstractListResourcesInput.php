<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Provides common functionalities to classes used as input resources.
 */
abstract class AbstractListResourcesInput extends AbstractDataObject
{
    public string $storeId;

    /** @var string[] commerce productIds to filter by */
    public array $productIds = [];

    /**
     * Creates a new data object.
     *
     * @param array{
     *     storeId: string,
     *     productIds?: string[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
