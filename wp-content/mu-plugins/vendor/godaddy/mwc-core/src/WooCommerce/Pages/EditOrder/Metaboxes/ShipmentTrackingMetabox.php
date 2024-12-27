<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Pages\EditOrder\Metaboxes;

use Exception;
use GoDaddy\WordPress\MWC\Common\Content\AbstractPostMetabox;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WP_Post;

/**
 * Adds a shipment tracking metabox to the edit order page.
 */
class ShipmentTrackingMetabox extends AbstractPostMetabox
{
    /** @var string The post type associated with this metabox */
    protected $postType = 'shop_order';

    /** @var string The ID for the metabox */
    protected $id = 'mwc-order-shipment';

    /** @var string The priority for the metabox */
    protected $priority = self::PRIORITY_HIGH;

    /**
     * Metabox constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setTitle(__('Shipment Tracking', 'mwc-core'));
    }

    /**
     * Registers the metabox hooks.
     *
     * @throws Exception
     */
    protected function addHooks() : void
    {
        parent::addHooks();

        if (! $this->getPostType()) {
            return;
        }

        Register::action()
            ->setGroup("add_meta_boxes_{$this->getPostType()}")
            ->setHandler([$this, 'maybeUpdateMetaboxOrder'])
            ->execute();
    }

    /**
     * Gets the post type for the metabox.
     *
     * @return string
     */
    public function getPostType() : string
    {
        return WooCommerceRepository::getEditOrderPageScreenId() ?: parent::getPostType();
    }

    /**
     * Possibly updates metabox order.
     *
     * Will not update the order if this metabox was moved to another position or context.
     *
     * @internal
     *
     * @param WP_Post|null $post
     * @return void
     */
    public function maybeUpdateMetaboxOrder($post = null)
    {
        $screenMetaboxOrder = get_user_option("meta-box-order_{$this->getPostType()}");

        if (empty($screenMetaboxOrder) || $this->isMetaboxOrderConfigured($screenMetaboxOrder)) {
            return;
        }

        $contextOrder = TypeHelper::string(ArrayHelper::get(TypeHelper::array($screenMetaboxOrder, []), $this->getContext()), '');

        if (! empty($contextOrder)) {
            // get metabox ids, remove empty values
            $ids = array_filter(explode(',', $contextOrder));

            // TODO: use ArrayHelper::insert() method
            array_splice(
                $ids,
                array_search('woocommerce-order-items', $ids, false) + 1,
                0,
                $this->getId()
            );

            $screenMetaboxOrder[$this->getContext()] = implode(',', $ids);
        } else {
            // if the metabox context is missing from the order array, we'll create the context order manually
            // and set our metabox as the only metabox there
            $screenMetaboxOrder[$this->getContext()] = $this->getId();
        }

        update_user_option(get_current_user_id(), "meta-box-order_{$this->getPostType()}", $screenMetaboxOrder, true);
    }

    /**
     * Determines if the metabox order for the shipment tracking metabox is configured.
     *
     * Will return true if the position of the metabox is set, regardless of the content
     * (even if the merchant moved it to another context).
     *
     * @param array $screenMetaboxOrder
     * @return bool
     */
    private function isMetaboxOrderConfigured(array $screenMetaboxOrder) : bool
    {
        foreach ($screenMetaboxOrder as $contextOrder) {
            if ($contextOrder && StringHelper::contains($contextOrder, $this->getId())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Renders metabox markup.
     *
     * @param WP_Post|null $post
     * @param array $args
     */
    public function render($post = null, $args = [])
    {
        echo '<div id="mwc-order-shipment-content"></div>';
    }
}
