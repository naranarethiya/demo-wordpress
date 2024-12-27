<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Account\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountStatusContract;

class DisconnectedStatus implements AccountStatusContract
{
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('disconnected');
        $this->setLabel(__('Disconnected', 'mwc-shipping'));
    }
}
