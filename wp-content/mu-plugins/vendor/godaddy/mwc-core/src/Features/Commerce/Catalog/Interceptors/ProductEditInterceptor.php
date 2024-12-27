<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\ProductEditHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanLoadWhenReadsEnabledTrait;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

/**
 * Interceptor for manipulating the product-editing experience.
 */
class ProductEditInterceptor extends AbstractInterceptor
{
    use CanLoadWhenReadsEnabledTrait;

    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('current_screen')
            ->setHandler([ProductEditHandler::class, 'handle'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::action()
            ->setGroup('admin_notices')
            ->setHandler([$this, 'maybeDisplayNoticeForVariationsUsingAnyAttributeValues'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueAssets'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        // WooCommerce performs a direct DB check for variations having no prices, which would always apply for products read from Commerce
        Register::filter()
            ->setGroup('woocommerce_show_invalid_variations_notice')
            ->setHandler('__return_false')
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Maybe displays a notice on a parent variable product edit screen if one of its variations uses an "Any..." attribute value.
     *
     * @internal
     *
     * @return void
     */
    public function maybeDisplayNoticeForVariationsUsingAnyAttributeValues() : void
    {
        /** @var WC_Product_Variable|null $product */
        $product = $this->getEditScreenProduct('variable');

        if (! $product || ! $this->variableProductHasAnyAttributeVariation($product)) {
            return;
        }

        echo Notice::getNewInstance()
            ->setId('mwc-commerce-catalog-product-variation-any-attribute-value-detected')
            ->setType(Notice::TYPE_ERROR)
            ->setDismissible(false)
            ->setContent(__('The "Any" attribute cannot be used for variable products synced with your GoDaddy Commerce product catalog. Please specify the attribute value for each variation, then save your changes.', 'mwc-core'))
            ->getHtml();
    }

    /**
     * Determines whether a variable product has at least 1 variation with an "Any..." attribute value.
     *
     * @param WC_Product_Variable $variableProduct
     * @return bool
     */
    protected function variableProductHasAnyAttributeVariation(WC_Product_Variable $variableProduct) : bool
    {
        $hasAnyAttributeValue = false;

        foreach ($variableProduct->get_children() as $variationId) {
            /** @var WC_Product_Variation|null $variation */
            $variation = ProductsRepository::get($variationId);

            if (! $variation) {
                continue;
            }

            foreach ($variation->get_attributes() as $attribute) {
                if ($attribute === '') {
                    $hasAnyAttributeValue = true;
                    break 2;
                }
            }
        }

        return $hasAnyAttributeValue;
    }

    /**
     * Enqueues static assets for the product edit screen page.
     *
     * @internal
     *
     * @return void
     * @throws Exception
     */
    public function enqueueAssets() : void
    {
        Enqueue::script()
            ->setHandle('mwc-commerce-catalog-product-edit')
            ->setSource(WordPressRepository::getAssetsUrl('js/features/commerce/admin/product-edit.js'))
            ->setVersion(TypeHelper::string(Configuration::get('mwc.version'), ''))
            ->setCondition([$this, 'shouldEnqueueAssets'])
            ->setDependencies(['jquery'])
            ->setDeferred(true)
            ->attachInlineScriptObject('mwcCommerceCatalogProductEdit')
            ->attachInlineScriptVariables([
                'isNewProduct' => ! $this->getEditScreenProduct(),
            ])
            ->execute();
    }

    /**
     * Determines if the product edit screen page should enqueue static assets.
     *
     * @internal
     *
     * @return bool
     * @throws Exception
     */
    public function shouldEnqueueAssets() : bool
    {
        return WordPressRepository::isCurrentScreen(CatalogIntegration::PRODUCT_POST_TYPE)
            || $this->getEditScreenProduct();
    }

    /**
     * Gets the product object for the current edit screen.
     *
     * @param string|null $productType e.g. 'simple', 'variable', or null for any product type
     * @return WC_Product|WC_Product_Variable|null
     */
    protected function getEditScreenProduct(?string $productType = null)
    {
        $currentScreen = WordPressRepository::getCurrentScreen();

        if (! $currentScreen || 'edit_product' !== $currentScreen->getPageId()) {
            return null;
        }

        $product = ProductsRepository::get(TypeHelper::int($currentScreen->getObjectId(), 0));

        if (! $product || ($productType && ! $product->is_type($productType))) {
            return null;
        }

        return $product;
    }
}
