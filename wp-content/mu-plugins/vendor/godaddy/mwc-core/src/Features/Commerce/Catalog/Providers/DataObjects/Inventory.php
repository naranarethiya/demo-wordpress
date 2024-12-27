<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Provides basic inventory tracking.
 */
class Inventory extends AbstractDataObject
{
    /** @var bool if the product's inventory is tracked by an external service */
    public bool $externalService = true;

    /** @var bool if the product is tracking inventory */
    public bool $tracking = true;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     externalService?: bool,
     *     tracking?: bool,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
