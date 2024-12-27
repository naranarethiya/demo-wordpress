<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CommerceProductUuidRequestInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;

/**
 * Handles incoming requests containing a Commerce UUID {@see CommerceProductUuidRequestInterceptor}.
 */
class CommerceProductUuidRequestHandler extends AbstractInterceptorHandler
{
    protected ProductMapRepository $productMapRepository;

    public function __construct(ProductMapRepository $productMapRepository)
    {
        $this->productMapRepository = $productMapRepository;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function run(...$args)
    {
        $remoteProductId = SanitizationHelper::input(TypeHelper::string(ArrayHelper::get($_GET, 'gd-product-id'), ''));

        if (empty($remoteProductId)) {
            return;
        }

        $localId = $this->productMapRepository->getLocalId($remoteProductId);

        if ($localId && $permalink = get_permalink($localId)) {
            Redirect::to($permalink)->setStatusCode(301)->execute();

            return;
        }

        // Return a 404 when the product is not found.
        wp_die(__('Product not found.', 'mwc-core'), '', ['response' => 404]);
    }
}
