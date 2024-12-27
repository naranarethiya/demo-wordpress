<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractOption;

/**
 * Object representing a value for an {@see AbstractOption}.
 */
class Value extends AbstractDataObject
{
    /** @var string internal option name (e.g. "red-color") */
    public string $name;

    /** @var string the displayed name (e.g. "Red") */
    public string $presentation;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     name: string,
     *     presentation: string
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
