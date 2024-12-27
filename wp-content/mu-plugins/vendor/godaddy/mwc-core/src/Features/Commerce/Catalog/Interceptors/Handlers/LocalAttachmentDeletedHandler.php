<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Factories\CatalogAssetMapRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\LocalAttachmentDeletedInterceptor;
use WP_Post;

/**
 * Handler for {@see LocalAttachmentDeletedInterceptor}.
 *
 * Deletes the corresponding mapping record when a Commerce-owned attachment is deleted.
 */
class LocalAttachmentDeletedHandler extends AbstractInterceptorHandler
{
    protected CatalogAssetMapRepositoryFactory $catalogAssetMapRepositoryFactory;

    public function __construct(CatalogAssetMapRepositoryFactory $catalogAssetMapRepositoryFactory)
    {
        $this->catalogAssetMapRepositoryFactory = $catalogAssetMapRepositoryFactory;
    }

    /**
     * Executes the callback for `delete_post` actions.
     *
     * @param ...$args
     * @return void
     */
    public function run(...$args)
    {
        /** @var WP_Post|null $attachment */
        $attachment = $args[1] ?? null;

        if ($attachment instanceof WP_Post && $this->shouldHandle($attachment)) {
            try {
                $this->handleDeletedAttachment($attachment);
            } catch(Exception $e) {
            }
        }
    }

    /**
     * Determines whether we should handle deletion for the supplied object.
     *
     * @param WP_Post $attachment
     * @return bool
     */
    protected function shouldHandle(WP_Post $attachment) : bool
    {
        return 'attachment' === $attachment->post_type;
    }

    /**
     * Handles instances where an attachment has been deleted.
     *
     * @param WP_Post $attachment
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function handleDeletedAttachment(WP_Post $attachment) : void
    {
        $this->catalogAssetMapRepositoryFactory->getRepository()->deleteByLocalId(TypeHelper::int($attachment->ID, 0));
    }
}
