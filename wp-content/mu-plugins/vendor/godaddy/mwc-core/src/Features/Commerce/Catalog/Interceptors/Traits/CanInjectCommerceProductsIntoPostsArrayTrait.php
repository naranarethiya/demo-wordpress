<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductPost;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListProductsByLocalIdService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use stdClass;
use WP_Post;
use WP_Query;

/**
 * A trait for injecting Commerce data into WordPress product posts.
 */
trait CanInjectCommerceProductsIntoPostsArrayTrait
{
    /** @var ProductPostAdapter */
    protected ProductPostAdapter $postAdapter;

    /** @var BatchListProductsByLocalIdService */
    protected BatchListProductsByLocalIdService $batchListProductsByLocalIdService;

    /**
     * Injects Commerce data in WordPress product posts.
     *
     * @param WP_Post[]|stdClass[] $posts an array of post objects or post rows from wpdb
     * @param int[]|null $localIds array of local product IDs
     * @return WP_Post[]|stdClass[] array of corresponding objects with layered Commerce data
     */
    protected function injectCommerceData(array $posts, ?array $localIds = null) : array
    {
        if (! $this->shouldQueryPlatformForProducts()) {
            return $posts;
        }

        // try to get the local IDs from the posts data rows if missing
        $localIds = $this->parseLocalProductPostIds($localIds ?: [], $posts);

        if (empty($localIds)) {
            return $posts;
        }

        $remoteProducts = $this->batchListProductsByLocalIdService
            ->setWithVariants($this->shouldQueryVariants())
            ->batchListByLocalIds($localIds);

        /** @var stdClass|WP_Post $localProductPost */
        foreach ($posts as $postIndex => $localProductPost) {
            $commerceProductPost = $this->getRemoteProductForPost($localProductPost, $remoteProducts);

            if (! $commerceProductPost) {
                continue;
            }

            // maintains the original index and type of the posts as received by this function
            $posts[$postIndex] = $this->overlayCommerceDataToWordPressPost($commerceProductPost, $localProductPost);
        }

        return $posts;
    }

    /**
     * Determines whether we should query the platform for product data.
     *
     * @return bool
     */
    protected function shouldQueryPlatformForProducts() : bool
    {
        return CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Parses local product post IDs from an array of posts data rows.
     *
     * @param int[] $localIds
     * @param stdClass[]|WP_Post[] $posts
     * @return int[]
     */
    protected function parseLocalProductPostIds(array $localIds, array $posts) : array
    {
        if (empty($localIds)) {
            $localIds = array_column((array) $posts, 'ID');
        }

        return $localIds;
    }

    /**
     * Determines whether we should pre-emptively query variants as well. This can improve performance when we know
     * variants will be queried immediately after the parents.
     *
     * @return bool
     */
    protected function shouldQueryVariants() : bool
    {
        global $wp_query;

        try {
            // we only want to do this if we're on the admin "All Products" page
            return isset($wp_query) && $wp_query instanceof WP_Query && $wp_query->is_main_query() && WordPressRepository::isCurrentScreen('edit-product');
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Gets a remote Commerce product post object for a local post object.
     *
     * @param WP_Post|stdClass $post
     * @param ProductAssociation[] $remoteProducts
     * @return ProductPost|null
     */
    protected function getRemoteProductForPost($post, array $remoteProducts) : ?ProductPost
    {
        if (empty($post->ID)) {
            return null;
        }

        /** @var ProductAssociation[] $remoteProduct */
        $remoteProduct = ArrayHelper::where($remoteProducts, function (ProductAssociation $productAssociation) use ($post) {
            return $productAssociation->localId === (int) $post->ID;
        }, false);

        if (! isset($remoteProduct[0])) {
            return null;
        }

        return $this->postAdapter->setLocalPost((array) $post)->convertToSource($remoteProduct[0]->remoteResource);
    }

    /**
     * Overlays Commerce data as a {@see ProductPost} to a WordPress {@see WP_Post} or database object as {@see stdClass}.
     *
     * Concrete classes may override this trait method to provide their own implementation.
     *
     * @param ProductPost $commercePost
     * @param object $wordPressPost
     * @return WP_Post|stdClass
     */
    protected function overlayCommerceDataToWordPressPost(ProductPost $commercePost, object $wordPressPost) : object
    {
        return $wordPressPost instanceof WP_Post
            ? $commercePost->toWordPressPost($wordPressPost)
            : $commercePost->toDatabaseObject($wordPressPost instanceof stdClass ? $wordPressPost : null);
    }
}
