<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataSources\WordPress\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Enums\XCropPosition;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Enums\YCropPosition;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\ImageCropAttributes;

class ImageCropAdapter
{
    /**
     * Adapts WordPress formatted crop data to a ImageCrop object.
     *
     * @param mixed $crop
     *
     * @return ImageCropAttributes
     */
    public function convertFromSource($crop) : ImageCropAttributes
    {
        if (is_array($crop)) {
            /** @var array{string,string} $crop */
            $crop = array_slice(TypeHelper::arrayOfStrings($crop), 0, 2);

            return ImageCropAttributes::getNewInstance([
                'shouldCrop' => true,
                'xPosition'  => XCropPosition::tryFromArray($crop, 0, XCropPosition::Center),
                'yPosition'  => YCropPosition::tryFromArray($crop, 1, YCropPosition::Center),
            ]);
        }

        return ImageCropAttributes::getNewInstance([
            'shouldCrop' => TypeHelper::bool($crop, false),
        ]);
    }

    /**
     * Returns WordPress formatted crop data.
     *
     * @param ImageCropAttributes $imageCrop
     *
     * @return mixed
     */
    public function convertToSource(ImageCropAttributes $imageCrop)
    {
        if (! empty($imageCrop->xPosition) || ! empty($imageCrop->yPosition)) {
            return [$imageCrop->xPosition ?: XCropPosition::Center, $imageCrop->yPosition ?: YCropPosition::Center];
        }

        return $imageCrop->shouldCrop;
    }
}
