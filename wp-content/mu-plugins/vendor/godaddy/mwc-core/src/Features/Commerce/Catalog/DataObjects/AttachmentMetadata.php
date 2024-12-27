<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * Represents metadata required for local attachment objects.
 */
class AttachmentMetadata extends AbstractDataObject
{
    public int $width;
    public int $height;
    public string $mimeType;
    public int $fileSize; // in bytes

    /**
     * Creates a new data object.
     *
     * @param array{
     *     width: int,
     *     height: int,
     *     mimeType: string,
     *     fileSize: int,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    /**
     * Converts the DTO into an array. The array format should match what WordPress core expects in {@see wp_generate_attachment_metadata()}.
     * This function doesn't have to return the full array with all keys, but any keys that are included need to match
     * the names used in {@see wp_generate_attachment_metadata()} in order for WordPress core to recognize them.
     *
     * @return array<string, string|int> width, height, file size
     */
    public function toArray() : array
    {
        return [
            'width'    => $this->width,
            'height'   => $this->height,
            'filesize' => $this->fileSize,
        ];
    }
}
