<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Repositories;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Builders\ManagedWordPress\HostingPlanBuilder;

class HostingPlanRepository extends AbstractHostingPlanRepository
{
    /** {@inheritdoc} */
    public function getCurrent() : HostingPlanContract
    {
        return HostingPlanBuilder::getNewInstance()->build();
    }
}
