<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Traits\HasUnitOfMeasurementTrait;

/**
 * An object representation of dimensions.
 */
class Dimensions extends AbstractModel
{
    use HasUnitOfMeasurementTrait;

    /** @var float the height */
    protected $height;

    /** @var float the width */
    protected $width;

    /** @var float the length */
    protected $length;

    /**
     * Gets the height value.
     *
     * @return float
     */
    public function getHeight() : float
    {
        return is_float($this->height) ? $this->height : 0;
    }

    /**
     * Sets the height value.
     *
     * @param float $value
     * @return $this
     */
    public function setHeight(float $value) : Dimensions
    {
        $this->height = $value;

        return $this;
    }

    /**
     * Gets the width value.
     *
     * @return float
     */
    public function getWidth() : float
    {
        return is_float($this->width) ? $this->width : 0;
    }

    /**
     * Sets the width value.
     *
     * @param float $value
     * @return $this
     */
    public function setWidth(float $value) : Dimensions
    {
        $this->width = $value;

        return $this;
    }

    /**
     * Gets the length value.
     *
     * @return float
     */
    public function getLength() : float
    {
        return is_float($this->length) ? $this->length : 0;
    }

    /**
     * Sets the length value.
     *
     * @param float $value
     * @return $this
     */
    public function setLength(float $value) : Dimensions
    {
        $this->length = $value;

        return $this;
    }
}
