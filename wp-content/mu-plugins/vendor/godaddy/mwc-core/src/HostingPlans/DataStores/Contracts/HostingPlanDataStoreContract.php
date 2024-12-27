<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\DataStores\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;

interface HostingPlanDataStoreContract
{
    /**
     * Gets the newest hosting plan record.
     *
     * @return HostingPlanContract|null
     */
    public function latest() : ?HostingPlanContract;

    /**
     * Gets all available history of stored hosting plan data.
     *
     * @return HostingPlanContract[]
     */
    public function all() : array;

    /**
     * Replaces the most recent entry in the list of hosting plans with the information from the given plan.
     *
     * @param HostingPlanContract $hostingPlan
     *
     * @return HostingPlanContract The saved instance.
     */
    public function save(HostingPlanContract $hostingPlan) : HostingPlanContract;

    /**
     * Adds the given hosting plan instance to the list of hosting plans stored in the database.
     *
     * @param HostingPlanContract $hostingPlan
     *
     * @return HostingPlanContract
     */
    public function add(HostingPlanContract $hostingPlan) : HostingPlanContract;
}
