<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\RemoteAssetDownloadFailedException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\RemoteAssetDownloadInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Assets\RemoteAssetProcessingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\AttachmentsService;

/**
 * Handler for {@see RemoteAssetDownloadInterceptor}.
 */
class RemoteAssetDownloadHandler extends AbstractInterceptorHandler
{
    protected RemoteAssetProcessingService $remoteAssetProcessingService;

    public function __construct(RemoteAssetProcessingService $remoteAssetProcessingService)
    {
        $this->remoteAssetProcessingService = $remoteAssetProcessingService;
    }

    /**
     * Executes the job callback.
     *
     * This is responsible for generating local metadata for a remote asset.
     *
     * @param ...$args
     * @return void
     */
    public function run(...$args)
    {
        $localAttachmentId = TypeHelper::int(ArrayHelper::get($args, 0), 0);
        if (empty($localAttachmentId)) {
            return;
        }

        $assetUrl = TypeHelper::string(wp_get_attachment_image_url($localAttachmentId, 'full'), '');

        if ($assetUrl && $this->shouldDownloadAsset($assetUrl)) {
            try {
                $this->processAndSaveAttachmentMetadata($localAttachmentId, $assetUrl);
            } catch(Exception $e) {
                SentryException::getNewInstance('Failed to parse data from remote asset: '.$e->getMessage(), $e);
            }
        }
    }

    /**
     * Determines whether we should download the asset.
     *
     * @param string $remoteUrl
     * @return bool
     */
    protected function shouldDownloadAsset(string $remoteUrl) : bool
    {
        // below method will return false for local URLs; we shouldn't need to download those!
        return false !== wp_http_validate_url($remoteUrl);
    }

    /**
     * Generates metadata for the provided remote asset, and saves it to the local database.
     *
     * @param int $attachmentId
     * @param string $remoteAssetUrl
     * @return void
     * @throws RemoteAssetDownloadFailedException|BaseException
     */
    protected function processAndSaveAttachmentMetadata(int $attachmentId, string $remoteAssetUrl) : void
    {
        $attachmentMetadata = $this->remoteAssetProcessingService->processAttachment($remoteAssetUrl);

        /*
         * We did get the mime type when we first inserted the attachment {@see AttachmentsService::insertAttachment()}
         * but the value provided by the API is not validated as being a real mime type, so we prefer to check it ourselves.
         * If we don't have an accurate mime type saved, certain WordPress functions like {@see wp_attachment_is_image()}
         * will not work correctly.
         */
        wp_update_post([
            'ID'             => $attachmentId,
            'post_mime_type' => $attachmentMetadata->mimeType,
        ]);

        // combine the existing metadata with the new generated data
        // we don't actually expect existing metadata to exist, but better to double check!
        $existingMetadata = TypeHelper::array(
            get_post_meta($attachmentId, '_wp_attachment_metadata', true),
            []
        );

        $combinedMetadata = ArrayHelper::combineRecursive($existingMetadata, $attachmentMetadata->toArray());

        update_post_meta($attachmentId, '_wp_attachment_metadata', $combinedMetadata);
    }
}
