<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class AppleGooglePayLocalPickupNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $type = self::TYPE_WARNING;

    /** {@inheritdoc} */
    protected $id = 'mwc-commerce-apple-google-pay-local-pickup';

    /**
     * AppleGooglePayLocalPickupNotice constructor.
     */
    public function __construct()
    {
        $this->setContent(__("Apple Pay and Google Pay don't support local pickup location selection. If you need pickup locations selected by your customers to fulfill pickup orders, disable these payment methods."));
    }
}
