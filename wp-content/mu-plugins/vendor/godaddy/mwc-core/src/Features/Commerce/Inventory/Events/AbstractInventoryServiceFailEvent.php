<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;

abstract class AbstractInventoryServiceFailEvent implements EventContract
{
    /** @var string */
    public string $failReason;

    /** @var string */
    public const FAIL_REASON_SERVICE_DOWN = 'service down';

    /**
     * Event constructor.
     *
     * @param string $failReason
     */
    public function __construct(string $failReason)
    {
        $this->failReason = $failReason;
    }
}
