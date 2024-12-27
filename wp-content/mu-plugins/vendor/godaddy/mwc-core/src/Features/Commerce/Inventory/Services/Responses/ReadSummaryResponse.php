<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadSummaryResponseContract;

/**
 * @method static static getNewInstance(Summary $summary)
 */
class ReadSummaryResponse implements ReadSummaryResponseContract
{
    use CanGetNewInstanceTrait;

    protected Summary $summary;

    /**
     * @param Summary $summary
     */
    public function __construct(Summary $summary)
    {
        $this->summary = $summary;
    }

    /**
     * Gets the summary.
     *
     * @return Summary
     */
    public function getSummary() : Summary
    {
        return $this->summary;
    }
}
