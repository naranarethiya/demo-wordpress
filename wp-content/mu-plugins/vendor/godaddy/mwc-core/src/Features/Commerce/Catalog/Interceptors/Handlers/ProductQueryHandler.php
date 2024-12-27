<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Traits\CanInjectCommerceProductsIntoPostsArrayTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListProductsByLocalIdService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanDetermineWpQueryProductPostTypeTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use stdClass;
use WP_Post;
use WP_Query;

/**
 * A handler for injecting Commerce data into WordPress product posts in WordPress queries.
 */
class ProductQueryHandler extends AbstractInterceptorHandler
{
    use CanDetermineWpQueryProductPostTypeTrait;
    use CanInjectCommerceProductsIntoPostsArrayTrait;

    /**
     * Constructor.
     *
     * @param ProductPostAdapter $postAdapter
     * @param BatchListProductsByLocalIdService $batchListProductsByLocalIdService
     */
    public function __construct(ProductPostAdapter $postAdapter, BatchListProductsByLocalIdService $batchListProductsByLocalIdService)
    {
        $this->postAdapter = $postAdapter;
        $this->batchListProductsByLocalIdService = $batchListProductsByLocalIdService;
    }

    /**
     * Determines if the given posts should be modified with commerce data.
     *
     * @param WP_Post[]|stdClass[] $posts
     * @param WP_Query|mixed $wpQuery
     * @return bool
     */
    protected function shouldInjectCommerceData(array $posts, $wpQuery) : bool
    {
        return CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ)
            && ! empty($posts)
            && $wpQuery instanceof WP_Query
            && $this->isProductQuery($wpQuery);
    }

    /**
     * Injects Commerce data into the given posts.
     *
     * @param array<mixed> $args
     * @return WP_Post[]|stdClass[]
     */
    public function run(...$args) : array
    {
        /** @var WP_Post[]|stdClass[] $posts */
        $posts = TypeHelper::array($args[0] ?? [], []);
        /** @var WP_Query|mixed|null $wpQuery */
        $wpQuery = $args[1] ?? null;

        if (! $this->shouldInjectCommerceData($posts, $wpQuery)) {
            return $posts;
        }

        return $this->injectCommerceData($posts);
    }
}
