<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Pages\Orders\Columns;

use GoDaddy\WordPress\MWC\Common\Content\AbstractWooCommerceOrdersTableColumn;

class ShipmentTrackingColumn extends AbstractWooCommerceOrdersTableColumn
{
    /** @var int the value 100 makes this column render last */
    protected $registerPriority = 100;

    /** @var string the slug for the column */
    protected $slug = 'mwc_shipment_tracking';

    /**
     * ShipmentTrackingColumn constructor.
     *
     * @since 2.10.0
     */
    public function __construct()
    {
        parent::__construct();

        $this->setName(__('Shipment tracking', 'mwc-core'));
    }

    /**
     * Renders the container for the React portal.
     *
     * @since 2.10.0
     *
     * @param int|null $postId
     * @return mixed|void
     */
    public function render(int $postId = null)
    {
        echo "<div data-mwc-shipment-order-id='{$postId}'/>";
    }
}
