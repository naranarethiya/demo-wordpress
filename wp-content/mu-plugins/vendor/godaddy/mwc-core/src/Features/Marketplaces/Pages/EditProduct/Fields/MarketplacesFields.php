<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages\EditProduct\Fields;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use WC_Product;
use WC_Product_Variation;
use WP_Post;

/**
 * This class is responsible for outputting and handling Marketplaces fields displayed in the Edit Product page.
 */
class MarketplacesFields implements ComponentContract
{
    /**
     * Loads the component.
     *
     * @return void
     * @throws Exception
     */
    public function load() : void
    {
        Register::action()
            ->setGroup('woocommerce_product_options_general_product_data')
            ->setHandler([$this, 'renderMarketplacesGeneralFieldsSimpleProduct'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_product_options_inventory_product_data')
            ->setHandler([$this, 'renderMarketplacesInventoryFieldsSimpleProduct'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_product_after_variable_attributes')
            ->setHandler([$this, 'renderMarketplacesFieldsProductVariation'])
            ->setArgumentsCount(3)
            ->setPriority(20)
            ->execute();

        Register::action()
            ->setGroup('woocommerce_admin_process_product_object')
            ->setHandler([$this, 'saveSimpleProductFields'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_admin_process_variation_object')
            ->setHandler([$this, 'saveProductVariationFields'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Outputs the Marketplaces general fields for simple products.
     *
     * @internal
     *
     * @return void
     */
    public function renderMarketplacesGeneralFieldsSimpleProduct() : void
    {
        global $post;

        $condition = '';

        if ($post instanceof WP_Post && $post->ID) {
            // usage of `get_post_meta()` here is intentional for Commerce component compatibility
            $condition = TypeHelper::string(get_post_meta($post->ID, ProductAdapter::MARKETPLACES_CONDITION_META_KEY, true), '');
        }

        echo '<div id="gd-marketplaces-simple-product-general-fields" class="options_group show_if_simple marketplaces-product-fields">';

        woocommerce_wp_text_input([
            'id'    => ProductAdapter::MARKETPLACES_BRAND_META_KEY,
            'label' => esc_html__('Product Brand', 'mwc-core'),
        ]);

        woocommerce_wp_select([
            'id'      => ProductAdapter::MARKETPLACES_CONDITION_META_KEY,
            'label'   => esc_html__('Product Condition', 'mwc-core'),
            'class'   => 'short',
            'value'   => strtolower($condition), // fallback for legacy condition option values that were uppercase
            'options' => [
                ''            => '&nbsp;',
                'new'         => _x('New', 'Product condition', 'mwc-core'),
                'used'        => _x('Used', 'Product condition', 'mwc-core'),
                'refurbished' => _x('Refurbished', 'Product condition', 'mwc-core'),
            ],
        ]);

        echo '</div>';
    }

    /**
     * Outputs the Marketplaces inventory fields for simple products.
     *
     * @internal
     *
     * @return void
     */
    public function renderMarketplacesInventoryFieldsSimpleProduct() : void
    {
        echo '<div id="gd-marketplaces-simple-product-inventory-fields" class="options_group show_if_simple marketplaces-product-fields">';

        woocommerce_wp_text_input([
            'id'          => ProductAdapter::MARKETPLACES_GTIN_META_KEY,
            'label'       => '<abbr title="'.esc_attr__('Global Trade Item Number', 'mwc-core').'">'.esc_html_x('GTIN', 'Global Trade Item Number (GTIN)', 'mwc-core').'</abbr>',
            'description' => __('A Global Trade Item Number is used to uniquely identify your product on Marketplaces & Social product listings. It can be found next to the barcode.', 'mwc-core'),
            'desc_tip'    => true,
        ]);

        woocommerce_wp_text_input([
            'id'          => ProductAdapter::MARKETPLACES_MPN_META_KEY,
            'label'       => '<abbr title="'.esc_attr__('Manufacturer Part Number', 'mwc-core').'">'.esc_html_x('MPN', 'Manufacturer Part Number (MPN)', 'mwc-core').'</abbr>',
            'description' => __('A Manufacturer Part Number is used to uniquely identify your product on Marketplaces & Social product listings. Only use the MPN assigned by the manufacturer.', 'mwc-core'),
            'desc_tip'    => true,
        ]);

        echo '</div>';
    }

    /**
     * Outputs the Marketplaces fields for product variations.
     *
     * @param int|mixed $loop
     * @param array|mixed $variationData
     * @param WP_Post|mixed $variationPost
     * @return void
     */
    public function renderMarketplacesFieldsProductVariation($loop, $variationData, $variationPost) : void
    {
        $loop = TypeHelper::int($loop, 0);
        $brand = $condition = $gtin = $mpn = ''; // default values

        if ($variationPost instanceof WP_Post && $variationPost->ID) {
            // usage of `get_post_meta()` here is intentional for Commerce component compatibility
            $brand = TypeHelper::string(get_post_meta($variationPost->ID, ProductAdapter::MARKETPLACES_BRAND_META_KEY, true), '');
            $condition = TypeHelper::string(get_post_meta($variationPost->ID, ProductAdapter::MARKETPLACES_CONDITION_META_KEY, true), '');
            $gtin = TypeHelper::string(get_post_meta($variationPost->ID, ProductAdapter::MARKETPLACES_GTIN_META_KEY, true), '');
            $mpn = TypeHelper::string(get_post_meta($variationPost->ID, ProductAdapter::MARKETPLACES_MPN_META_KEY, true), '');
        }

        echo '<div id="gd-marketplaces-product-variation-fields-'.esc_attr((string) $loop).'" class="marketplaces-product-fields">';

        woocommerce_wp_text_input([
            'id'            => sprintf('%s_%d', ProductAdapter::MARKETPLACES_BRAND_META_KEY, $loop),
            'name'          => sprintf('%s[%d]', ProductAdapter::MARKETPLACES_BRAND_META_KEY, $loop),
            'value'         => $brand,
            'label'         => esc_html__('Product Brand', 'mwc-core'),
            'wrapper_class' => 'form-row form-row-first',
        ]);

        woocommerce_wp_select([
            'id'            => sprintf('%s_%d', ProductAdapter::MARKETPLACES_CONDITION_META_KEY, $loop),
            'name'          => sprintf('%s[%d]', ProductAdapter::MARKETPLACES_CONDITION_META_KEY, $loop),
            'label'         => esc_html__('Product Condition', 'mwc-core'),
            'wrapper_class' => 'form-row form-row-last',
            'value'         => strtolower($condition), // fallback for legacy condition option values that were uppercase
            'options'       => [
                ''            => '&nbsp;',
                'new'         => _x('New', 'Product condition', 'mwc-core'),
                'used'        => _x('Used', 'Product condition', 'mwc-core'),
                'refurbished' => _x('Refurbished', 'Product condition', 'mwc-core'),
            ],
        ]);

        woocommerce_wp_text_input([
            'id'            => sprintf('%s_%d', ProductAdapter::MARKETPLACES_GTIN_META_KEY, $loop),
            'name'          => sprintf('%s[%d]', ProductAdapter::MARKETPLACES_GTIN_META_KEY, $loop),
            'value'         => $gtin,
            'label'         => '<abbr title="'.esc_attr__('Global Trade Item Number', 'mwc-core').'">'.esc_html_x('GTIN', 'Global Trade Item Number (GTIN)', 'mwc-core').'</abbr>',
            'description'   => __('A Global Trade Item Number is used to uniquely identify your product on Marketplaces & Social product listings. It can be found next to the barcode.', 'mwc-core'),
            'desc_tip'      => true,
            'wrapper_class' => 'form-row form-row-first',
        ]);

        woocommerce_wp_text_input([
            'id'            => sprintf('%s_%d', ProductAdapter::MARKETPLACES_MPN_META_KEY, $loop),
            'name'          => sprintf('%s[%d]', ProductAdapter::MARKETPLACES_MPN_META_KEY, $loop),
            'value'         => $mpn,
            'label'         => '<abbr title="'.esc_attr__('Manufacturer Part Number', 'mwc-core').'">'.esc_html_x('MPN', 'Manufacturer Part Number (MPN)', 'mwc-core').'</abbr>',
            'description'   => __('A Manufacturer Part Number is used to uniquely identify your product on Marketplaces & Social product listings. Only use the MPN assigned by the manufacturer.', 'mwc-core'),
            'desc_tip'      => true,
            'wrapper_class' => 'form-row form-row-last',
        ]);

        echo '</div>';
    }

    /**
     * Saves the simple product fields.
     *
     * @internal
     *
     * @param WC_Product|mixed $product
     * @return void
     */
    public function saveSimpleProductFields($product) : void
    {
        if (! $product instanceof WC_Product) {
            return;
        }

        if ('simple' !== $product->get_type()) {
            return;
        }

        $this->updateProductMetadata(
            $product,
            TypeHelper::string(ArrayHelper::get($_POST, ProductAdapter::MARKETPLACES_BRAND_META_KEY), ''),
            TypeHelper::string(ArrayHelper::get($_POST, ProductAdapter::MARKETPLACES_CONDITION_META_KEY), ''),
            TypeHelper::string(ArrayHelper::get($_POST, ProductAdapter::MARKETPLACES_GTIN_META_KEY), ''),
            TypeHelper::string(ArrayHelper::get($_POST, ProductAdapter::MARKETPLACES_MPN_META_KEY), '')
        );
    }

    /**
     * Saves the variable product fields.
     *
     * @internal
     *
     * @param WC_Product_Variation|WC_Product|mixed $product
     * @param int|mixed $variationIndex
     * @return void
     */
    public function saveProductVariationFields($product, $variationIndex) : void
    {
        if (! $product instanceof WC_Product) {
            return;
        }

        if ('variation' !== $product->get_type()) {
            return;
        }

        $variationBrand = $_POST[ProductAdapter::MARKETPLACES_BRAND_META_KEY][$variationIndex] ?? null;
        $variationCondition = $_POST[ProductAdapter::MARKETPLACES_CONDITION_META_KEY][$variationIndex] ?? null;
        $variationGtin = $_POST[ProductAdapter::MARKETPLACES_GTIN_META_KEY][$variationIndex] ?? null;
        $variationMpn = $_POST[ProductAdapter::MARKETPLACES_MPN_META_KEY][$variationIndex] ?? null;

        $this->updateProductMetadata($product, $variationBrand, $variationCondition, $variationGtin, $variationMpn);
    }

    /**
     * Updates the product Marketplaces metadata.
     *
     * @param WC_Product $product
     * @param string|null $brand
     * @param string|null $condition
     * @param string|null $gtin
     * @param string|null $mpn
     * @return void
     */
    protected function updateProductMetadata(WC_Product $product, ?string $brand, ?string $condition, ?string $gtin, ?string $mpn) : void
    {
        $metaData = [
            ProductAdapter::MARKETPLACES_BRAND_META_KEY     => $brand,
            ProductAdapter::MARKETPLACES_CONDITION_META_KEY => $condition,
            ProductAdapter::MARKETPLACES_GTIN_META_KEY      => $gtin,
            ProductAdapter::MARKETPLACES_MPN_META_KEY       => $mpn,
        ];

        foreach ($metaData as $key => $value) {
            if (! empty($value)) {
                $product->update_meta_data($key, SanitizationHelper::input(StringHelper::unslash($value)));
            } else {
                $product->delete_meta_data($key);
            }
        }

        // we do not need to save as WooCommerce will be calling `save()` immediately after this hook
    }
}
