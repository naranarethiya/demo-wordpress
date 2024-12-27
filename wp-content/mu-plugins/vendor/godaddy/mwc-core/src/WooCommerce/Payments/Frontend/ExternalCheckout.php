<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\ExternalCheckout\AbstractExternalCheckoutIntegration;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\ExternalCheckout\WalletCheckoutIntegration;
use WC_Product;

/**
 * External checkout handler.
 *
 * Handles the views for external checkout buttons, such as Apple Pay.
 */
class ExternalCheckout implements ConditionalComponentContract
{
    /** @var string flag to display button on cart page */
    public const BUTTON_PAGE_CART = 'CART';

    /** @var string flag to display button on checkout page */
    public const BUTTON_PAGE_CHECKOUT = 'CHECKOUT';

    /** @var string flag to display button on single product pages */
    public const BUTTON_PAGE_SINGLE_PRODUCT = 'SINGLE_PRODUCT';

    /** @var AbstractExternalCheckoutIntegration[] */
    protected $integrations = [];

    /** @var array<string, AbstractExternalCheckoutIntegration[]> memoized available integrations (for caching results during the request lifecycle) */
    protected $availableIntegrations = [];

    /**
     * Loads the external checkout components.
     *
     * @throws Exception
     */
    public function load() : void
    {
        $this->integrations = [
            new WalletCheckoutIntegration(),
        ];

        $this->addHooks();
    }

    /**
     * Adds hooks to output external checkout components in WooCommerce pages.
     *
     * @throws Exception
     */
    protected function addHooks() : void
    {
        // grouped products
        Register::action()
            ->setGroup('woocommerce_before_add_to_cart_button')
            ->setHandler([$this, 'renderSingleProductButtons'])
            ->execute();

        // other products
        Register::action()
            ->setGroup('woocommerce_after_add_to_cart_quantity')
            ->setHandler([$this, 'renderSingleProductButtons'])
            ->execute();

        // cart page
        Register::action()
            ->setGroup('woocommerce_proceed_to_checkout')
            ->setHandler([$this, 'renderCartButtons'])
            ->execute();

        // checkout page
        Register::action()
            ->setGroup('woocommerce_before_checkout_form')
            ->setHandler([$this, 'renderCheckoutButtons'])
            ->execute();

        Register::action()
            ->setGroup('wp_enqueue_scripts')
            ->setHandler([$this, 'enqueueIntegrationFrontendScriptsAndStyles'])
            ->execute();
    }

    /**
     * Enqueues frontend scripts and styles for each integration that has them.
     */
    public function enqueueIntegrationFrontendScriptsAndStyles() : void
    {
        if (empty($context = $this->getContext())) {
            return;
        }

        foreach ($this->getAvailableIntegrations($context) as $integration) {
            if (is_callable([$integration, 'enqueueFrontendScriptsAndStyles'])) {
                $integration->enqueueFrontendScriptsAndStyles($context);
            }
        }
    }

    /**
     * Renders the external checkout buttons according to context.
     *
     * @param string $context
     */
    protected function renderButtons(string $context) : void
    {
        if (! $integrations = $this->getAvailableIntegrations($context)) {
            return;
        }

        echo '<div class="mwc-external-checkout-buttons">';

        foreach ($integrations as $integration) {
            $integration->render();
        }

        echo '</div>';
        /* translators: Divider between the Wallet buttons and "Add to Cart" */
        echo '<span class="mwc-external-checkout-buttons-divider">&mdash; '.esc_html__('or', 'mwc-core').' &mdash;</span>';
    }

    /**
     * Renders the external checkout buttons on the cart page.
     *
     * @internal callback
     */
    public function renderCartButtons() : void
    {
        $wc = WooCommerceRepository::getInstance();

        // do not display buttons on empty cart
        if (! $wc || empty($wc->cart) || $wc->cart->is_empty()) {
            return;
        }

        $this->renderButtons(self::BUTTON_PAGE_CART);
    }

    /**
     * Renders the external checkout buttons on the checkout page.
     *
     * @internal callback
     */
    public function renderCheckoutButtons() : void
    {
        $this->renderButtons(self::BUTTON_PAGE_CHECKOUT);
    }

    /**
     * Renders the external checkout buttons on single product pages.
     *
     * @internal callback
     */
    public function renderSingleProductButtons() : void
    {
        $id = get_queried_object_id();

        if (! $id) {
            return;
        }

        $product = ProductsRepository::get($id);

        if (! $product || ! $this->shouldRenderSingleProductButtons($product)) {
            return;
        }

        $this->renderButtons(self::BUTTON_PAGE_SINGLE_PRODUCT);
    }

    /**
     * Determines whether buttons should display for a product.
     *
     * @param WC_Product $product
     * @return bool
     */
    protected function shouldRenderSingleProductButtons(WC_Product $product) : bool
    {
        $productType = $product->get_type();

        if (('grouped' !== $productType && ! $product->is_purchasable()) || ! $product->is_in_stock()) {
            return false;
        }

        $actionHook = current_action();

        // grouped products use a different action hook, so we make sure we only output buttons once per product type
        return ! (('grouped' !== $productType && 'woocommerce_before_add_to_cart_button' === $actionHook) || ('grouped' === $productType && 'woocommerce_after_add_to_cart_quantity' === $actionHook));
    }

    /**
     * Gets the current context for rendering external checkout integrations.
     *
     * @return string|null
     */
    protected function getContext() : ?string
    {
        if (WooCommerceRepository::isCartPage()) {
            return self::BUTTON_PAGE_CART;
        }

        if (WooCommerceRepository::isCheckoutPage()) {
            return self::BUTTON_PAGE_CHECKOUT;
        }

        if (WooCommerceRepository::isProductPage()) {
            return self::BUTTON_PAGE_SINGLE_PRODUCT;
        }

        return null;
    }

    /**
     * Gets all available integrations.
     *
     * @param string $context
     *
     * @return AbstractExternalCheckoutIntegration[]
     */
    protected function getAvailableIntegrations(string $context) : array
    {
        if ($availableIntegrations = ArrayHelper::get($this->availableIntegrations, $context)) {
            return $availableIntegrations;
        }

        $this->availableIntegrations[$context] = array_filter($this->integrations, static function (AbstractExternalCheckoutIntegration $integration) use ($context) {
            return $integration->isAvailable($context);
        });

        return $this->availableIntegrations[$context];
    }

    /**
     * Determines whether there's at least one available integration for the given context.
     *
     * @param string $context
     * @return bool
     */
    protected function hasAvailableIntegrations(string $context) : bool
    {
        return ! empty($this->getAvailableIntegrations($context));
    }

    /**
     * Determines whether the component should load.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return true;
    }
}
