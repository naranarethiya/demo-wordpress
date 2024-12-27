<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs\AbstractBackfillResourceJob;

/**
 * An event that is fired when a {@see AbstractBackfillResourceJob} is skipped because writes are not enabled for the resource.
 *
 * @method static static getNewInstance(string $jobKey)
 */
class BackfillJobSkippedEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;
    use CanGetNewInstanceTrait;

    /** @var string unique identifier for the backfill job that was skipped */
    protected string $jobKey;

    public function __construct(string $jobKey)
    {
        $this->jobKey = $jobKey;
        $this->resource = 'commerce_backfill_job';
        $this->action = 'skipped';
    }

    /**
     * Returns an array with initial data for this event.
     *
     * @return array<string, string>
     */
    protected function buildInitialData() : array
    {
        return [
            'backfillJob' => $this->jobKey,
        ];
    }
}
