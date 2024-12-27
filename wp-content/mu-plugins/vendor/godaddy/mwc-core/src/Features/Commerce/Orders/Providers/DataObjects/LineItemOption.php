<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class LineItemOption extends AbstractDataObject
{
    /** @var string The presentation value for the option attribute, eg "Size" */
    public string $attribute;

    /** @var string[] The presentation value for the selected options for the attribute, eg "Large". */
    public array $values;

    /**
     * Constructor.
     *
     * @param array{
     *     attribute: string,
     *     values: string[]
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
