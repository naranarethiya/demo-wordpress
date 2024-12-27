<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects;

class Phone extends AbstractDataObject
{
    public string $phone;
    public string $label = '';
    public bool $default = true;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     phone: string,
     *     label?: string,
     *     default?: bool
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
