<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ImageContract;
use GoDaddy\WordPress\MWC\Common\Models\Exceptions\ImageSizeNotFound;

/**
 * A model for handling image files.
 */
class Image extends AbstractAttachment implements ImageContract
{
    /** @var ImageSize[] */
    protected array $sizes = [];

    /**
     * Gets the image sizes.
     *
     * @return ImageSize[]
     */
    public function getSizes() : array
    {
        return $this->sizes;
    }

    /**
     * Determines if the image has a given size.
     *
     * @param string $sizeIdentifier
     * @return bool
     */
    public function hasSize(string $sizeIdentifier) : bool
    {
        return ArrayHelper::has($this->sizes, $sizeIdentifier);
    }

    /**
     * Gets an image size of a given type.
     *
     * @param string $sizeIdentifier
     * @return ImageSize
     * @throws ImageSizeNotFound
     */
    public function getSize(string $sizeIdentifier) : ImageSize
    {
        $size = ArrayHelper::get($this->sizes, $sizeIdentifier);

        if (! $size) {
            throw new ImageSizeNotFound(sprintf('%s size not found for image #%d.', $sizeIdentifier, $this->id));
        }

        return $size;
    }

    /**
     * Sets the image sizes.
     *
     * @param ImageSize[] $value
     * @return $this
     */
    public function setSizes(array $value) : Image
    {
        $this->sizes = $value;

        return $this;
    }
}
