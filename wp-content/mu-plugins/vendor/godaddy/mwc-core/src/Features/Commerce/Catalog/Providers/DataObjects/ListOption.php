<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Value;

/**
 * A list of option values not backed by variants.
 */
class ListOption extends AbstractOption
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

        $this->type = static::TYPE_LIST;
    }
}
