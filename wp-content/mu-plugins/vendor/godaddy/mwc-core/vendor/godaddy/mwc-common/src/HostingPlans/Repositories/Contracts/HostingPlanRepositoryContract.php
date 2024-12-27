<?php

namespace GoDaddy\WordPress\MWC\Common\HostingPlans\Repositories\Contracts;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;

interface HostingPlanRepositoryContract
{
    /**
     * Gets a hosting plan instance with the latest plan data stored in the database.
     *
     * @return HostingPlanContract|null
     */
    public function getStored() : ?HostingPlanContract;

    /**
     * Gets current hosting plan object the store.
     *
     * @return HostingPlanContract
     */
    public function getCurrent() : HostingPlanContract;

    /**
     * Updates the latest hosting plan record in the data store.
     *
     * @param HostingPlanContract $hostingPlan
     * @return HostingPlanContract
     */
    public function save(HostingPlanContract $hostingPlan) : HostingPlanContract;

    /**
     * Adds a hosting plan to the data store.
     *
     * @param HostingPlanContract $hostingPlan
     * @return HostingPlanContract
     */
    public function add(HostingPlanContract $hostingPlan) : HostingPlanContract;

    /**
     * Gets the datetime associated with the latest upgrade event, if the last change event was an upgrade.
     *
     * @return DateTime|null
     */
    public function getUpgradeDateTime() : ?DateTime;
}
