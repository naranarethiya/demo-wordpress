<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages\EditProduct\Metaboxes;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Content\AbstractPostMetabox;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Marketplaces;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ListingRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components\GoDaddyBranding;
use WP_Post;

/**
 * Class responsible to output a metabox in the edit product page to handle Marketplaces data.
 */
class MarketplacesMetabox extends AbstractPostMetabox implements ComponentContract
{
    use CanGetNewInstanceTrait;

    /** @var string The post type associated with this metabox */
    protected $postType = 'product';

    /** @var string The ID for the metabox */
    protected $id = 'gd-marketplaces';

    /** @var string The priority for the metabox (will be displayed after the Product data metabox) */
    protected $priority = self::PRIORITY_CORE;

    /**
     * Marketplaces & Social metabox constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setTitle(__('Marketplaces & Social', 'mwc-core'));
    }

    /**
     * Loads the metabox.
     *
     * @return void
     * @throws Exception
     */
    public function load() : void
    {
        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueAssets'])
            ->execute();
    }

    /**
     * Determines if the assets should be enqueued.
     *
     * @return bool
     */
    public function shouldEnqueueAssets() : bool
    {
        return ! empty($screen = WordPressRepository::getCurrentScreen())
            && in_array($screen->getPageId(), ['add_product', 'edit_product'], true);
    }

    /**
     * Enqueues the assets.
     *
     * @internal
     *
     * @return void
     * @throws Exception
     */
    public function enqueueAssets() : void
    {
        if (! $this->shouldEnqueueAssets()) {
            return;
        }

        Enqueue::style()
            ->setHandle('gd-marketplaces')
            ->setSource(WordPressRepository::getAssetsUrl('css/features/marketplaces/admin/product-edit-metabox.css'))
            ->execute();
    }

    /**
     * Renders metabox markup.
     *
     * @param WP_Post|null $post
     * @param array<mixed> $args
     * @return void
     */
    public function render($post = null, $args = []) : void
    {
        $channelTypes = ChannelRepository::getTypes();

        try {
            $product = (new ProductAdapter(ProductsRepository::get($post->ID ?? 0)))->convertFromSource();
        } catch (Exception $exception) {
            return;
        } ?>
        <div class="panel-wrap woocommerce <?php echo $this->getId(); ?>">

            <ul class="<?php echo $this->getId(); ?>-tabs wc-tabs">
                <?php

                $firstChannelTypeSlug = current(array_keys($channelTypes));

        foreach ($channelTypes as $channelTypeSlug => $channelTypeName) {
            $classes = [
                SanitizationHelper::htmlClass($channelTypeSlug).'-tab',
            ];
            if ($firstChannelTypeSlug === $channelTypeSlug) {
                $classes[] = 'active';
            }
            $hasPublishedListing = ! empty(ListingRepository::getProductListingsByChannelType($product, $channelTypeSlug, true)); ?>
                    <li class="<?php echo implode(' ', array_map([SanitizationHelper::class, 'htmlClass'], $classes)); ?>">
                        <a href="#<?php echo esc_attr("gd-marketplaces-{$channelTypeSlug}"); ?>">
                            <span><?php echo esc_html($channelTypeName); ?></span>
                            <?php if ($hasPublishedListing) : ?>
                                <span class="alignright">
                                    <?php echo $this->getCheckBadgeIcon(); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php
        } ?>
            </ul>
            <?php

            if (! empty($channelTypes)) {
                foreach ($channelTypes as $channelTypeSlug => $channelTypeName) {
                    $firstChannelListing = null;
                    if (! empty($channelListings = ListingRepository::getProductListingsByChannelType($product, $channelTypeSlug))) {
                        $firstChannelListing = current($channelListings);
                    }
                    $this->renderChannelPanel($product, $post, $channelTypeName, $firstChannelListing);
                }
            } ?>
            <div class="clear"></div>
        </div><!-- //.panel-wrap -->
        <?php
        try {
            GoDaddyBranding::getInstance()->render();
        } catch (Exception $exception) {
            // catch the exception while in a hook callback context
        } ?>
        <?php
    }

    /**
     * Renders the markup for a channel panel.
     *
     * @param Product|null $product
     * @param WP_Post|null $post
     * @param string $channelTypeName
     * @param Listing|null $listing
     * @return void
     */
    protected function renderChannelPanel(?Product $product, ?WP_Post $post, string $channelTypeName, ?Listing $listing)
    {
        $channelTypeSlug = strtolower($channelTypeName);
        $channel = ChannelRepository::getByType($channelTypeSlug); ?>
        <div id="<?php echo esc_attr("gd-marketplaces-{$channelTypeSlug}"); ?>" class="panel woocommerce_options_panel">
            <?php
                if (empty($channel)) {
                    $this->renderChannelNotConnected($product, $post, $channelTypeSlug);
                } elseif (empty($listing)) {
                    $this->renderProductNotListed($product, $post, $channelTypeName, $channel);
                } elseif (! $listing->isPublished()) {
                    $this->renderDraftListingCreated($product, $post, $listing);
                } else {
                    $this->renderProductListed($product, $post, $listing);
                } ?>
        </div>
        <?php
    }

    /**
     * Renders the markup for a channel panel when the channel is not connected.
     *
     * @param Product|null $product
     * @param WP_Post|null $post
     * @param string $channelTypeSlug
     *
     * @return void
     */
    protected function renderChannelNotConnected(?Product $product, ?WP_Post $post, string $channelTypeSlug) : void
    {
        $channelTypeSlug = StringHelper::lowerCase($channelTypeSlug);
        $storeId = Marketplaces::getStoreId();

        $ventureId = '';
        try {
            $ventureId = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getVentureId();
        } catch (PlatformRepositoryException $exception) {
            // since we are in a hook callback context we catch the exception and forward to Sentry instead of throwing
            new SentryException($exception->getMessage(), $exception);
        } ?>
        <p>
            <?php esc_html_e('You haven\'t connected this sales channel yet.', 'mwc-core'); ?>
        </p>
        <?php try { ?>
            <p>
                <a href="<?php echo esc_url(Marketplaces::getSalesChannelsUrl("/marketplaces/about/{$channelTypeSlug}?storeId={$storeId}&ventureId={$ventureId}")); ?>" target="_blank" class="button button-primary"><?php esc_html_e('Connect Sales Channel', 'mwc-core'); ?></a>
            </p>
        <?php } catch (Exception $exception) {
            // since we are in a hook callback context we catch the exception and forward to Sentry instead of throwing
            new SentryException($exception->getMessage(), $exception);
        }
    }

    /**
     * Renders the markup for a channel panel when the channel is connected and the product is not listed on it.
     *
     * @param Product|null $product
     * @param WP_Post|null $post
     * @param string $channelTypeName
     * @param Channel $channel
     * @return void
     */
    protected function renderProductNotListed(?Product $product, ?WP_Post $post, string $channelTypeName, Channel $channel)
    {
        ?>
        <div id="<?php printf(esc_attr('gd-marketplaces-product-not-listed-%s'), SanitizationHelper::slug($channelTypeName)); ?>" class="gd-marketplaces-product-not-listed">
            <p>
                <?php printf(
                    /* translators: Placeholders: %s: Marketplaces channel name (e.g. Amazon, eBay, Facebook, etc.) */
                    esc_html__('This product hasn\'t been listed on %s yet.', 'mwc-core'), esc_html($channelTypeName)
                ); ?>
            </p>
            <?php if (ChanneL::TYPE_GOOGLE !== $channel->getType()) : ?>
                <p>
                    <button id="<?php printf(esc_attr('mwc-marketplaces-create-draft-listing-for-%s'), SanitizationHelper::slug($channelTypeName)); ?>" class="button button-primary mwc-marketplaces-create-draft-listing" data-channel-type="<?php echo SanitizationHelper::slug($channelTypeName); ?>" data-channel-uuid="<?php echo esc_attr($channel->getUuid()); ?>"><?php esc_html_e('Create Draft Listing', 'mwc-core'); ?></button>
                </p>
            <?php endif; ?>
            <div class="gd-marketplaces-create-draft-error" style="display: none"></div>
            <div class="gd-marketplaces-product-requirements">
                <p>
                    <?php if (ChanneL::TYPE_GOOGLE === $channel->getType()) : ?>
                    <?php printf(
                        /* translators: Placeholders: %1$s - Opening <a> HTML link tag, %2$s - Closing </a> HTML link tag */
                        esc_html__('A draft listing will be automatically created once the product meets the requirements below. %1$sLearn more about product sync%2$s', 'mwc-core'),
                        '<a href="https://godaddy.com/help/a-41223" target="_blank">',
                        '</a>'
                    ); ?>
                    <?php else : ?>
                        <?php printf(
                            /* translators: Placeholders: %1%s - Opening HTML <a> link tag, %2$s - Closing HTML </a> link tag */
                            esc_html__('The requirements below must be met in order to list this product. %1$sLearn more about product sync%2$s', 'mwc-core'),
                            '<a href="https://godaddy.com/help/a-41223" target="_blank">',
                            '</a>'
                        ); ?>
                    <?php endif; ?>
                </p>
                <ul class="ul-list ul-disc">
                    <li><?php echo esc_html_x('Simple or variable product (not virtual or downloadable)', 'Product property requirement', 'mwc-core'); ?></li>
                    <li><?php echo esc_html_x('Published product', 'Product property requirement', 'mwc-core'); ?></li>
                    <li><?php echo esc_html_x('Product SKU must be set', 'Product property requirement', 'mwc-core'); ?></li>
                    <li><?php echo esc_html_x('Manage stock enabled and stock quantity > 0', 'Product property requirement', 'mwc-core'); ?></li>
                    <li><?php echo esc_html_x('No backorders allowed', 'Product property requirement', 'mwc-core'); ?></li>
                    <li><?php echo esc_html_x('Brand and Condition fields filled', 'Product property requirement', 'mwc-core'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Retrieves the URL to manage a listing, catching all exceptions because we're in a hook callback context.
     *
     * @param string $path path to append to the Marketplaces domain
     * @return string
     */
    protected function getManageListingUrl(string $path) : string
    {
        try {
            return Marketplaces::getMarketplacesUrl($path);
        } catch (Exception $exception) {
            // since we are in a hook callback context, we catch exceptions
            new SentryException('Failed to build URL to manage listing.', $exception);

            return TypeHelper::string(Configuration::get('marketplaces.website.url'), '');
        }
    }

    /**
     * Renders the markup for a channel panel when the channel is connected and the product has a draft listing on it.
     *
     * @internal
     *
     * @param Product|null $product
     * @param WP_Post|null $post
     * @param Listing $listing
     * @return void
     */
    public function renderDraftListingCreated(?Product $product, ?WP_Post $post, Listing $listing) : void
    {
        $listingLink = $listing->getLink(); ?>
        <p>
            <?php
                /* translators: Placeholders: %s Marketplaces channel name (e.g. Amazon, eBay, Facebook, etc.) */
                echo esc_html(sprintf(__('A draft listing has been created for this product on %s.', 'mwc-core'), ChannelRepository::getLabel($listing->getChannelType()))); ?>
        </p>
        <?php if ('' != $listingLink): ?>
        <p>
            <a href="<?php echo esc_url($this->getManageListingUrl($listingLink)); ?>" target="_blank" class="button"><?php esc_html_e('View Draft Listing', 'mwc-core'); ?></a>
        </p>
        <?php
        endif;
    }

    /**
     * Renders the markup for a channel panel when the channel is connected and the product has a published listing on it.
     *
     * @param Product|null $product
     * @param WP_Post|null $post
     * @param Listing $listing
     * @return void
     */
    protected function renderProductListed(?Product $product, ?WP_Post $post, Listing $listing) : void
    {
        $channel = ChannelRepository::getByType($listing->getChannelType());
        $channelId = $channel ? $channel->getId() : '';
        $listingUrl = $this->getManageListingUrl("/channels/{$channelId}/{$listing->getListingId()}"); ?>
        <p class="gd-marketplaces-product-listed">
            <?php
            echo $this->getCheckBadgeIcon();

        /* translators: Placeholders: %s Marketplaces channel name (e.g. Amazon, eBay, Facebook, etc.) */
        echo esc_html(sprintf(__('This product has been listed on %s.', 'mwc-core'), ChannelRepository::getLabel($listing->getChannelType()))); ?>
        </p>
        <p>
            <a href="<?php echo esc_url($listingUrl); ?>" target="_blank" class="button"><?php esc_html_e('Edit Listing', 'mwc-core'); ?></a>
        </p>
        <?php
    }

    /**
     * Returns the `<img>` tag for the check badge icon.
     *
     * @return string `<img>` tag on success, empty string if the icon cannot be found.
     */
    protected function getCheckBadgeIcon() : string
    {
        try {
            $imageUrl = WordPressRepository::getAssetsUrl('images/check-badge-icon.svg');
        } catch (Exception $exception) {
            new SentryException('Check badge icon not loaded', $exception);

            return '';
        }

        if (empty($imageUrl)) {
            return '';
        }

        return '<img src="'.esc_url($imageUrl).'" width="16" height="16"/>';
    }
}
