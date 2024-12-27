<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Assets;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataObjects\AttachmentMetadata;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\RemoteAssetDownloadFailedException;

/**
 * Service class for generating WordPress-style attachment metadata for a provided file.
 */
class AttachmentMetadataGenerationService
{
    /**
     * Generates metadata for the provided image file.
     *
     * @param string $filePath
     * @return AttachmentMetadata
     * @throws RemoteAssetDownloadFailedException
     */
    public function generateImageMetadata(string $filePath) : AttachmentMetadata
    {
        if (! $imageSize = $this->getImageSize($filePath)) {
            throw new RemoteAssetDownloadFailedException("Failed to read image size on file path {$filePath}");
        }

        return new AttachmentMetadata([
            'width'    => TypeHelper::int(ArrayHelper::get($imageSize, 0), 0),
            'height'   => TypeHelper::int(ArrayHelper::get($imageSize, 1), 0),
            'mimeType' => TypeHelper::string(ArrayHelper::get($imageSize, 'mime'), ''),
            'fileSize' => TypeHelper::int(wp_filesize($filePath), 0),
        ]);
    }

    /**
     * Gets the size data for the provided image file.
     *
     * @codeCoverageIgnore
     *
     * @param string $filePath
     * @return array{0: int, 1: int, 2: int, 3: string, bits: int, channels: int, mime: string}|false
     */
    protected function getImageSize(string $filePath)
    {
        $imageSize = getimagesize($filePath);

        if ($imageSize === false) {
            return false;
        }

        return [
            0          => $imageSize[0] ?? 0, // width
            1          => $imageSize[1] ?? 0, // height
            2          => $imageSize[2] ?? 0, // @link https://www.php.net/manual/en/image.constants.php
            3          => $imageSize[3] ?? '', //  text string with the correct height="yyy" width="xxx" string that can be used directly in an IMG tag
            'bits'     => $imageSize['bits'] ?? 0,
            'channels' => $imageSize['channels'] ?? 0,
            'mime'     => $imageSize['mime'] ?? '',
        ];
    }
}
