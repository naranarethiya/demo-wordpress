<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListSummariesResponseContract;

class ListSummariesResponse implements ListSummariesResponseContract
{
    use CanGetNewInstanceTrait;

    /** @var Summary[] */
    protected array $summaries;

    /**
     * @param Summary[] $summaries
     */
    public function __construct(array $summaries)
    {
        $this->summaries = $summaries;
    }

    /**
     * Gets the summaries.
     *
     * @return Summary[]
     */
    public function getSummaries() : array
    {
        return $this->summaries;
    }
}
