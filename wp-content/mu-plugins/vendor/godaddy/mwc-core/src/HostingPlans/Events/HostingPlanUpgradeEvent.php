<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Events;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;

class HostingPlanUpgradeEvent extends AbstractHostingPlanEvent
{
    /**
     * {@inheritDoc}
     */
    public static function from(HostingPlanContract $hostingPlan) : HostingPlanUpgradeEvent
    {
        return new static($hostingPlan, 'hosting_plan', 'upgrade');
    }
}
