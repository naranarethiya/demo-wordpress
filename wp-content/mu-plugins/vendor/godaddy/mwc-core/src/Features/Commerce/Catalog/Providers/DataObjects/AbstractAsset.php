<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Abstract assets data object.
 *
 * Concrete assets are expected to extend this base class.
 *
 * @see ImageAsset
 * @see VideoAsset
 * @see UnsupportedAsset
 *
 * @method static static getNewInstance(array $data)
 */
abstract class AbstractAsset extends AbstractDataObject
{
    /** @var string */
    const TYPE_IMAGE = 'IMAGE';

    /** @var string */
    const TYPE_VIDEO = 'VIDEO';

    /** @var string|null MIME type (e.g. `image/jpeg`) */
    public ?string $contentType;

    /** @var string the asset's name */
    public string $name;

    /** @var string thumbnail URL */
    public string $thumbnail;

    /** @var string supported asset type (e.g. `IMAGE` or `VIDEO`) */
    public string $type;

    /** @var string the asset's URL */
    public string $url;

    /**
     * Constructor.
     *
     * @param array{
     *     contentType: ?string,
     *     name: string,
     *     thumbnail: string,
     *     type: string,
     *     url: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
