<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Account\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountStatusContract;

class ConnectedStatus implements AccountStatusContract
{
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('connected');
        $this->setLabel(__('Connected', 'mwc-shipping'));
    }
}
