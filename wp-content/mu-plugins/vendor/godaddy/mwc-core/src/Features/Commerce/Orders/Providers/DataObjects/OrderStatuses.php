<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\FulfillmentStatus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\PaymentStatus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\Status;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class OrderStatuses extends AbstractDataObject
{
    /**
     * @var FulfillmentStatus::*
     */
    public string $fulfillmentStatus;

    /**
     * @var PaymentStatus::*
     */
    public string $paymentStatus;

    /**
     * @var Status::*
     */
    public string $status;
}
