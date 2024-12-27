<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataSources\WordPress\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\ImageCropAttributes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\ImageDimensions;

/**
 * Adapts WordPress image data.
 */
class ImageDimensionsAdapter
{
    protected ImageCropAdapter $imageCropAdapter;

    public function __construct(ImageCropAdapter $imageCropAdapter)
    {
        $this->imageCropAdapter = $imageCropAdapter;
    }

    /**
     * Converts a WordPress size array/name into an {@see ImageDimensions} DTO.
     *
     * @param string|array<mixed> $size
     * @return ImageDimensions
     */
    public function convertFromSource($size) : ImageDimensions
    {
        if (is_array($size)) {
            return ImageDimensions::getNewInstance([
                'width'  => TypeHelper::int(ArrayHelper::get($size, 0), 0),
                'height' => TypeHelper::int(ArrayHelper::get($size, 1), 0),
                'crop'   => $this->convertCropFromSource(ArrayHelper::get($size, 2, false)),
            ]);
        } else {
            return $this->convertSizeNameFromSource($size);
        }
    }

    /**
     * Converts an image size name (e.g. `thumbnail`) to an {@see ImageDimensions} DTO.
     *
     * @param string $sizeName
     * @return ImageDimensions
     */
    protected function convertSizeNameFromSource(string $sizeName) : ImageDimensions
    {
        $imageSizes = wp_get_registered_image_subsizes();

        $width = TypeHelper::int(
            ArrayHelper::get($imageSizes, "{$sizeName}.width"),
            0
        );

        $height = TypeHelper::int(
            ArrayHelper::get($imageSizes, "{$sizeName}.height"),
            0
        );

        return ImageDimensions::getNewInstance([
            'width'  => $width,
            'height' => $height,
            'crop'   => $this->convertCropFromSource(
                ArrayHelper::get($imageSizes, "{$sizeName}.crop", false)
            ),
        ]);
    }

    /**
     * @param mixed $crop
     * @return ImageCropAttributes
     */
    protected function convertCropFromSource($crop) : ImageCropAttributes
    {
        return $this->imageCropAdapter->convertFromSource($crop);
    }
}
