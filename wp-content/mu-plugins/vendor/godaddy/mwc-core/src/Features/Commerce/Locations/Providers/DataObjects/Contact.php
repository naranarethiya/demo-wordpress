<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Phone;

class Contact extends AbstractDataObject
{
    public string $type;
    public Phone $phone;

    /** @var string represents the WORK phone type */
    const TYPE_WORK = 'WORK';

    /**
     * Creates a new data object.
     *
     * @param array{
     *     type: string,
     *     phone: Phone,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
