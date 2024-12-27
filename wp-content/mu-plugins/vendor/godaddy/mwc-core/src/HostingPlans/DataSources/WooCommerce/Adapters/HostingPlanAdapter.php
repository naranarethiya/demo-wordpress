<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\DataSources\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Models\HostingPlan;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\HostingPlans\DataStores\Contracts\HostingPlanAdapterContract;

class HostingPlanAdapter implements HostingPlanAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> */
    protected $source;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $source
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : ?HostingPlanContract
    {
        if (! ArrayHelper::get($this->source, 'name')) {
            return null;
        }

        return HostingPlan::seed($this->source);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource(HostingPlanContract $hostingPlan = null) : ?array
    {
        return $hostingPlan ? $this->convertHostingPlanToSource($hostingPlan) : null;
    }

    /**
     * Converts given HostingPlanContract instance into an array.
     *
     * @param HostingPlanContract $hostingPlan
     * @return array<string, mixed>
     */
    protected function convertHostingPlanToSource(HostingPlanContract $hostingPlan) : array
    {
        return $hostingPlan->toArray();
    }
}
