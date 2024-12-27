<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Enums\XCropPosition;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Enums\YCropPosition;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Data object for image size crop parameters.
 */
class ImageCropAttributes extends AbstractDataObject
{
    public bool $shouldCrop = false;

    /** @var string the x crop position, accepts: left, center, right {@see XCropPosition} */
    public string  $xPosition = '';

    /** @var string the y crop position, accepts: top, center, bottom {@see YCropPosition} */
    public string  $yPosition = '';

    /**
     * @param array{
     *     shouldCrop?: bool,
     *     xPosition?: string,
     *     yPosition?: string,
     * } $data
     */
    public function __construct(array $data)
    {
        $data['xPosition'] = XCropPosition::tryFrom($data['xPosition'] ?? '') ?: '';
        $data['yPosition'] = YCropPosition::tryFrom($data['yPosition'] ?? '') ?: '';

        // Due to the way WordPress formats this data, when we have an x or y position, we will never have a `shouldCrop` value.
        // Therefore, we need to explicitly set `shouldCrop = true`.
        if (! empty($data['xPosition']) || ! empty($data['yPosition'])) {
            $data['shouldCrop'] = true;
        }

        parent::__construct($data);
    }
}
