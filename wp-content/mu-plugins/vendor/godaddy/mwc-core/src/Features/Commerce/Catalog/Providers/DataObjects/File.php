<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * File asset DTO.
 *
 * Used in digital {@see ProductBase} types, identifies files that would be purchased.
 *
 * @method static static getNewInstance(array $data)
 */
class File extends AbstractDataObject
{
    /** @var string|null file asset description */
    public ?string $description = null;

    /** @var string file name - e.g. file1.jpg */
    public string $name;

    /** @var string unique identifier for this file in whatever backing store contains it */
    public string $objectKey;

    /** @var int|null file size in bytes */
    public ?int $size = null;

    /** @var string|null MIME type - e.g. image/jpeg */
    public ?string $type = null;

    /** @var string file URL or path */
    public string $url;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     description?: ?string,
     *     name: string,
     *     objectKey: string,
     *     size?: ?int,
     *     type?: ?string,
     *     url: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
