<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Account\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountStatusContract;

class NotConfiguredStatus implements AccountStatusContract
{
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('not-configured');
        $this->setLabel(__('Not Configured', 'mwc-shipping'));
    }
}
