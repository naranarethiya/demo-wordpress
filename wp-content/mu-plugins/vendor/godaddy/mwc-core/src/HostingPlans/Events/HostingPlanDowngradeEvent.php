<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Events;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;

class HostingPlanDowngradeEvent extends AbstractHostingPlanEvent
{
    /**
     * {@inheritDoc}
     */
    public static function from(HostingPlanContract $hostingPlan) : HostingPlanDowngradeEvent
    {
        return new static($hostingPlan, 'hosting_plan', 'downgrade');
    }
}
