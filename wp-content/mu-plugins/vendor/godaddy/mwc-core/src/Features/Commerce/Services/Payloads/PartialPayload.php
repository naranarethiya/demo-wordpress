<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Payloads;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\PayloadContract;

/**
 * @phpstan-import-type TPayloadValue from PayloadContract
 */
class PartialPayload implements PayloadContract
{
    /** @var TPayloadValue */
    protected $value;

    /**
     * Constructor.
     *
     * @param TPayloadValue $value
     */
    final public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function hasValue() : bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Generates new instance from the given value.
     *
     * @param TPayloadValue $value
     * @return PartialPayload
     */
    public static function fromValue($value) : PartialPayload
    {
        return new static(is_scalar($value) || ArrayHelper::accessible($value) ? $value : null);
    }
}
