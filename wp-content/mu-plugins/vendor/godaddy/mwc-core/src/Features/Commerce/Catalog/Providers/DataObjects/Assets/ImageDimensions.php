<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Data object for image dimensions.
 *
 * The properties follow the possible values of the `size` parameter in the WordPress function `add_image_size()`.
 * {@link https://developer.wordpress.org/reference/functions/add_image_size/}
 */
class ImageDimensions extends AbstractDataObject
{
    public int $width = 0;
    public int $height = 0;

    public ImageCropAttributes $crop;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     width?: int,
     *     height?: int,
     *     crop?: ImageCropAttributes
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    /**
     * Does the image dimensions have x or y crop positions set.
     *
     * @return bool
     */
    public function hasCropPosition() : bool
    {
        return ! empty($this->crop->xPosition) || ! empty($this->crop->yPosition);
    }
}
