<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses;

use GoDaddy\WordPress\MWC\Common\Contracts\OrderStatusContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

final class CheckoutDraftOrderStatus implements OrderStatusContract
{
    use CanConvertToArrayTrait;
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('checkout-draft')
            ->setLabel(__('Checkout Draft', 'mwc-common'));
    }
}
