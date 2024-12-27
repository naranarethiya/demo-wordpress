<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

/**
 * Image asset data object.
 *
 * Describes an image asset.
 */
class ImageAsset extends AbstractAsset
{
    /**
     * Constructor.
     *
     * @param array{
     *     contentType: ?string,
     *     name: string,
     *     thumbnail: string,
     *     url: string,
     * } $data
     */
    public function __construct(array $data)
    {
        $data['type'] = AbstractAsset::TYPE_IMAGE;

        parent::__construct($data);
    }
}
