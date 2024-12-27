<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Events;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;

class HostingPlanChangeEvent extends AbstractHostingPlanEvent
{
    /**
     * {@inheritDoc}
     */
    public static function from(HostingPlanContract $hostingPlan) : HostingPlanChangeEvent
    {
        return new static($hostingPlan, 'hosting_plan', 'change');
    }
}
