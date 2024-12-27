<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Builders;

use GoDaddy\WordPress\MWC\Common\Builders\Contracts\BuilderContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Base class for builders that can build one or more instances of the same data object type from data.
 *
 * @template TDataObject
 */
abstract class AbstractDataObjectBuilder implements BuilderContract
{
    use CanGetNewInstanceTrait;

    /** @var array<int|string, mixed> */
    protected array $data = [];

    /**
     * Sets the order data.
     *
     * @param array<int|string, mixed> $value
     * @return $this
     */
    public function setData(array $value)
    {
        $this->data = $value;

        return $this;
    }

    /**
     * Creates a new data object using the current data as source.
     *
     * @return TDataObject
     */
    abstract public function build();

    /**
     * Creates one or more data objects using the values in the given array of data.
     *
     * @param array<int|string, mixed> $data
     * @return TDataObject[]
     */
    public function buildMany(array $data) : array
    {
        return array_map(function ($itemData) {
            return $this->setData(TypeHelper::array($itemData, []))->build();
        }, $data);
    }

    /**
     * Returns the given value if it is a string or null otherwise.
     *
     * @param mixed $value
     * @return string|null
     */
    protected function stringOrNull($value) : ?string
    {
        return is_string($value) ? $value : null;
    }

    /**
     * Returns the given value if it is a non-empty string or null otherwise.
     *
     * @param mixed $value
     * @return non-empty-string|null
     */
    protected function nonEmptyStringOrNull($value) : ?string
    {
        return TypeHelper::string($value, '') ?: null;
    }
}
