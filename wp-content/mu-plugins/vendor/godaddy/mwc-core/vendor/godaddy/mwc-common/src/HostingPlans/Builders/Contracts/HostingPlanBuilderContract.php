<?php

namespace GoDaddy\WordPress\MWC\Common\HostingPlans\Builders\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;

interface HostingPlanBuilderContract
{
    /**
     * Assembles an instance of a hosting plan object from platform data.
     *
     * @return HostingPlanContract
     */
    public function build() : HostingPlanContract;
}
