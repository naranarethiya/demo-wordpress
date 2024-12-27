<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Traits\CanInjectCommerceProductsIntoPostsArrayTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListProductsByLocalIdService;
use stdClass;
use WP_Post;

/**
 * Handler for priming the posts caches.
 */
class PrimePostCachesHandler extends AbstractInterceptorHandler
{
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
     * Injects Commerce data into the given posts.
     *
     * @param array<mixed> $args
     * @return stdClass[]|WP_Post[]
     */
    public function run(...$args) : array
    {
        /** @var WP_Post[]|stdClass[] $posts */
        $posts = TypeHelper::array($args[0] ?? [], []);
        /** @var int[] $localIds */
        $localIds = ArrayHelper::wrap($args[1] ?? []);

        return ! empty($posts)
            ? $this->injectCommerceData($posts, $localIds)
            : $posts;
    }
}
