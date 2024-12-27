<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Value;

/**
 * A list of option values that are backed by variants.
 */
class VariantListOption extends AbstractOption
{
    /** @var Value[] */
    public array $values;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     cardinality?: string,
     *     name: string,
     *     presentation: string,
     *     type?: string,
     *     values: Value[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->type = static::TYPE_VARIANT_LIST;
    }
}
