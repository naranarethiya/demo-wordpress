<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\ImageSizeContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringIdentifierTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasUrlTrait;

/**
 * Object representation of an image size.
 *
 * @method static static getNewInstance(array $properties = [])
 */
class ImageSize implements ImageSizeContract
{
    use CanBulkAssignPropertiesTrait;
    use CanConvertToArrayTrait;
    use CanGetNewInstanceTrait;
    use HasStringIdentifierTrait;
    use HasUrlTrait;

    /** @var int pixels */
    protected $height = 0;

    /** @var int pixels */
    protected $width = 0;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $properties
     */
    public function __construct(array $properties = [])
    {
        $this->id = ''; // ensures the identifier default is not null
        $this->setProperties($properties);
    }

    /**
     * Gets the image size height.
     *
     * @return int pixels
     */
    public function getHeight() : int
    {
        return $this->height;
    }

    /**
     * Sets the image size height.
     *
     * @param int $value height in pixels
     * @return $this
     */
    public function setHeight(int $value) : ImageSize
    {
        $this->height = $value;

        return $this;
    }

    /**
     * Gets the image size width.
     *
     * @return int pixels
     */
    public function getWidth() : int
    {
        return $this->width;
    }

    /**
     * Sets the image size width.
     *
     * @param int $value width in pixels
     * @return $this
     */
    public function setWidth(int $value) : ImageSize
    {
        $this->width = $value;

        return $this;
    }
}
