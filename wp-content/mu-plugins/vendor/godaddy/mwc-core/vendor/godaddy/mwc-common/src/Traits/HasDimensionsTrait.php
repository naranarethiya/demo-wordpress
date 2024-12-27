<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Models\Dimensions;

/**
 * A trait to assign dimension properties to an object.
 */
trait HasDimensionsTrait
{
    /** @var Dimensions */
    protected $dimensions;

    /**
     * Gets the dimensions.
     *
     * @return Dimensions
     */
    public function getDimensions() : Dimensions
    {
        return $this->dimensions;
    }

    /**
     * Sets the dimensions.
     *
     * @param Dimensions $dimensions
     * @return $this
     */
    public function setDimensions(Dimensions $dimensions)
    {
        $this->dimensions = $dimensions;

        return $this;
    }
}
