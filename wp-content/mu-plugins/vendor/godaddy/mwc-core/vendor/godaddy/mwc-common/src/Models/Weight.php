<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Traits\HasUnitOfMeasurementTrait;

/**
 * An object representation of a weight amount.
 */
class Weight extends AbstractModel
{
    use HasUnitOfMeasurementTrait;

    /** @var float the weight amount */
    protected $value;

    /**
     * Gets the weight amount.
     *
     * @return float
     */
    public function getValue() : float
    {
        return is_float($this->value) ? $this->value : 0;
    }

    /**
     * Sets the weight amount.
     *
     * @param float $value
     * @return $this
     */
    public function setValue(float $value) : Weight
    {
        $this->value = $value;

        return $this;
    }
}
