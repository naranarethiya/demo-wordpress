<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\AssetUserHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\RemoteImageResizeServiceContract;
use WP_Post;
use WP_Query;

/**
 * Handles the reading of catalog assets.
 */
class AssetReadInterceptor extends AbstractInterceptor
{
    protected RemoteImageResizeServiceContract $imageResizeService;

    public function __construct(RemoteImageResizeServiceContract $imageResizeService)
    {
        $this->imageResizeService = $imageResizeService;
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        /* @link https://developer.wordpress.org/reference/hooks/wp_get_attachment_image_src/ */
        Register::filter()
            ->setGroup('wp_get_attachment_image_src')
            ->setHandler([$this, 'maybeFilterAttachmentSrc'])
            ->setArgumentsCount(4)
            ->execute();

        /* @see WP_Query::get_posts() */
        Register::filter()
            ->setGroup('posts_where')
            ->setHandler([$this, 'maybeExcludeCommerceAssets'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Filters the `src` attribute for Commerce attachments.
     *
     * @internal
     *
     * @param array<int, string|int|bool>|mixed $image
     * @param int|mixed $attachmentId
     * @param string|int[] $size
     * @param bool|mixed $icon
     * @return array<int, string|int|bool>|mixed
     */
    public function maybeFilterAttachmentSrc($image, $attachmentId, $size, $icon)
    {
        if (! is_array($image)) {
            return $image;
        }

        $attachmentId = TypeHelper::int($attachmentId, 0);
        if (! $attachmentId) {
            return $image;
        }

        $attachmentPostObject = CatalogIntegration::withoutReads(fn () => get_post($attachmentId));
        if (! $attachmentPostObject instanceof WP_Post) {
            return $image;
        }

        if ($this->shouldFilterAttachment($attachmentPostObject)) {
            /*
             * Set the URL to be the GUID. This is where we've stored the remote URL. In the future we can change this
             * to look up the URL via the API once individual asset endpoints are available. For now our thinking is
             * that the URL for an asset is unlikely to change once set.
             */
            return $this->imageResizeService->generateImageData($attachmentPostObject->guid, $size, $image);
        }

        return $image;
    }

    /**
     * Determines whether we should filter the supplied attachment.
     *
     * @param WP_Post $attachment
     * @return bool
     */
    protected function shouldFilterAttachment(WP_Post $attachment) : bool
    {
        return ! empty($attachment->guid) // GUID is required, as that's where we store the remote URL for now
            && AssetUserHelper::isCommerceAssetUser(TypeHelper::int($attachment->post_author, 0));
    }

    /**
     * Exclude Commerce-originating assets from the media library for relevant queries.
     *
     * We exclude them because we're unable to filter the `src` attribute in the admin media library, which results
     * in broken attachments being rendered. Even if we could fix that, merchants are unable to use the image editor
     * properties for remote assets anyway (e.g. crop, resize, etc.). It's a better UX to just exclude them.
     *
     * @internal
     *
     * @param mixed|string $whereClause database query WHERE clause
     * @param WP_Query|mixed $wpQuery
     * @return string|mixed
     */
    public function maybeExcludeCommerceAssets($whereClause, $wpQuery)
    {
        if (! $this->shouldFilterWhereClause($whereClause, $wpQuery) || ! $commerceAssetUserId = AssetUserHelper::getAssetUserId()) {
            return $whereClause;
        }

        $wpdb = DatabaseRepository::instance();

        $whereClause .= " AND {$wpdb->posts}.post_author <> {$commerceAssetUserId} ";

        return $whereClause;
    }

    /**
     * Determines if the supplied WHERE clause should be filtered.
     *
     * @param mixed|string $whereClause
     * @param WP_Query|mixed $wpQuery
     * @return bool
     *
     * @phpstan-assert-if-true string $whereClause
     * @phpstan-assert-if-true WP_Query $wpQuery
     */
    protected function shouldFilterWhereClause($whereClause, $wpQuery) : bool
    {
        return is_string($whereClause) &&
            $wpQuery instanceof WP_Query &&
            (
                // is a main admin query for attachments (WHERE post_type = 'attachment')
                is_admin() &&
                $wpQuery->is_main_query() &&
                StringHelper::contains($whereClause, 'attachment')
            ) ||
            (
                // or, we're doing an ajax call for attachments
                ArrayHelper::get($_POST, 'action') === 'query-attachments' ||
                ArrayHelper::get($_POST, 'action') === 'get-attachment'
            );
    }
}
