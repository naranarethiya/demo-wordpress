<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

/**
 * Video asset data object.
 *
 * Describes a video asset.
 */
class VideoAsset extends AbstractAsset
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
        $data['type'] = AbstractAsset::TYPE_VIDEO;

        parent::__construct($data);
    }
}
