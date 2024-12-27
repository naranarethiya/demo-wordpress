<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait to handle units of measurement.
 */
trait HasUnitOfMeasurementTrait
{
    /** @var string|null */
    protected $unit;

    /**
     * Gets the unit of measurement.
     *
     * @return string
     */
    public function getUnitOfMeasurement() : string
    {
        return is_string($this->unit) ? $this->unit : '';
    }

    /**
     * Sets the unit of measurement.
     *
     * @param string $unit
     * @return $this
     */
    public function setUnitOfMeasurement(string $unit)
    {
        $this->unit = $unit;

        return $this;
    }
}
