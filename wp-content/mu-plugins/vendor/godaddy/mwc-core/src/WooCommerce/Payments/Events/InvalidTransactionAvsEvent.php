<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events;

use GoDaddy\WordPress\MWC\Payments\Events\AbstractTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;

/**
 * This event is broadcast when transactions fail due to AVS flags that can indicate fraudulent payments.
 */
class InvalidTransactionAvsEvent extends AbstractTransactionEvent
{
    public function __construct(AbstractTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
