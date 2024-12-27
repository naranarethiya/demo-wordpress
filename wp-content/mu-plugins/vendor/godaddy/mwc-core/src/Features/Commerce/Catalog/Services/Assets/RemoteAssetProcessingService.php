<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Assets;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataObjects\AttachmentMetadata;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\RemoteAssetDownloadFailedException;

/**
 * Remote Asset Processing Service.
 *
 * WordPress requires some extra information about assets that the Commerce API doesn't provide. Including:
 * - Image dimensions
 * - MIME type (the API actually does provide this, but it isn't validated, so we'll double check it ourselves!)
 *
 * This class handles the processing of a remote asset so we can obtain and save that information. This is done by
 * downloading the asset locally, fetching the data we require, then deleting the local copy.
 */
class RemoteAssetProcessingService
{
    protected AttachmentMetadataGenerationService $attachmentMetadataGenerationService;

    public function __construct(AttachmentMetadataGenerationService $attachmentMetadataGenerationService)
    {
        $this->attachmentMetadataGenerationService = $attachmentMetadataGenerationService;
    }

    /**
     * Temporarily downloads a remote file in order to generate the metadata for local storage.
     *
     * @param string $url
     * @return AttachmentMetadata
     * @throws RemoteAssetDownloadFailedException
     */
    public function processAttachment(string $url) : AttachmentMetadata
    {
        $this->maybeIncludeRequiredFiles();

        // Download the file to a temporary directory
        $tempFile = download_url($url);

        if (WordPressRepository::isError($tempFile)) {
            throw new RemoteAssetDownloadFailedException("Failed to download {$url}: ".$tempFile->get_error_message());
        }

        $metadata = $this->attachmentMetadataGenerationService->generateImageMetadata($tempFile);

        if (! $this->removeFile($tempFile)) {
            // don't want to throw here, as it shouldn't halt operation
            // just instantiating will report to sentry
            SentryException::getNewInstance("Failed to remove temporary asset file: {$tempFile}");
        }

        return $metadata;
    }

    /**
     * Includes required WordPress core files for image downloading.
     *
     * @codeCoverageIgnore
     *
     * @TODO consider moving to {@see WordPressRepository} in mwc-common.
     *
     * @return void
     */
    protected function maybeIncludeRequiredFiles() : void
    {
        if (! function_exists('download_url')) {
            $base = TypeHelper::string(Configuration::get('wordpress.absolute_path'), '');
            require_once "{$base}wp-admin/includes/image.php";
        }
    }

    /**
     * Deletes the supplied file from the filesystem.
     *
     * @codeCoverageIgnore
     *
     * @param string $filePath
     * @return bool
     */
    protected function removeFile(string $filePath) : bool
    {
        // nosemgrep: php.lang.security.unlink-use.unlink-use
        return unlink($filePath);
    }
}
