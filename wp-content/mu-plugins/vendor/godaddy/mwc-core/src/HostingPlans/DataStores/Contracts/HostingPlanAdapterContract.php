<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\DataStores\Contracts;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;

interface HostingPlanAdapterContract extends DataSourceAdapterContract
{
    /**
     * {@inheritDoc}
     * @return HostingPlanContract|null
     */
    public function convertFromSource() : ?HostingPlanContract;

    /**
     * {@inheritDoc}
     * @param HostingPlanContract|null $hostingPlan
     * @return array<string, mixed>|null
     */
    public function convertToSource(HostingPlanContract $hostingPlan = null) : ?array;
}
