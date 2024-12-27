<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects;

/**
 * The context of a given resource.
 */
class Context extends AbstractDataObject
{
    public string $storeId;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     storeId: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
