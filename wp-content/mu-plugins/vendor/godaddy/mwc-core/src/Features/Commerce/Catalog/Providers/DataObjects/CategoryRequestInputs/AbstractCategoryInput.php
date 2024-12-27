<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Abstract category input data object.
 *
 * @method static static getNewInstance(array $data)
 */
abstract class AbstractCategoryInput extends AbstractDataObject
{
    /** @var string the store ID */
    public string $storeId;

    /**
     * Base Category Input Constructor.
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
