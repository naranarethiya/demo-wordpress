<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class ProductInventoryUpdateFailedNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_ERROR;

    /** {@inheritdoc} */
    protected $id = 'mwc-commerce-product-inventory-update-failed';

    /**
     * ProductInventoryUpdateFailedNotice constructor.
     */
    public function __construct(string $failReason)
    {
        $this->setContent(__('An error occurred trying to update the stock level. '.$failReason));
    }
}
