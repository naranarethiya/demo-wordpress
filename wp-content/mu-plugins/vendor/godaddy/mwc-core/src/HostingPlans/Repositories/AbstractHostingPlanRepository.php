<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Repositories;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\HostingPlans\Repositories\Contracts\HostingPlanRepositoryContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\HostingPlans\DataStores\Contracts\HostingPlanDataStoreContract;
use GoDaddy\WordPress\MWC\Core\HostingPlans\DataStores\HostingPlanDataStore;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Services\HostingPlanComparatorService;

abstract class AbstractHostingPlanRepository implements HostingPlanRepositoryContract
{
    use CanGetNewInstanceTrait;

    /** @var HostingPlanDataStoreContract|null */
    protected $hostingPlanDataStore = null;

    /** {@inheritdoc} */
    public function getStored() : ?HostingPlanContract
    {
        return $this->getHostingPlanDataStore()->latest();
    }

    /**
     * Gets an instance of the data store used to store hosting plan data.
     *
     * @return HostingPlanDataStoreContract
     */
    protected function getHostingPlanDataStore() : HostingPlanDataStoreContract
    {
        if (is_null($this->hostingPlanDataStore)) {
            $this->hostingPlanDataStore = HostingPlanDataStore::getNewInstance();
        }

        return $this->hostingPlanDataStore;
    }

    /** {@inheritdoc} */
    public function save(HostingPlanContract $hostingPlan) : HostingPlanContract
    {
        return $this->getHostingPlanDataStore()->save($hostingPlan);
    }

    /** {@inheritdoc} */
    public function add(HostingPlanContract $hostingPlan) : HostingPlanContract
    {
        return $this->getHostingPlanDataStore()->add($hostingPlan);
    }

    /**
     * Gets the comparator object.
     *
     * @return HostingPlanComparatorService
     */
    protected function getComparator()
    {
        return new HostingPlanComparatorService;
    }

    /** {@inheritdoc} */
    public function getUpgradeDateTime() : ?DateTime
    {
        $history = $this->getHostingPlanDataStore()->all();
        $dates = array_keys($history);

        $latest = array_shift($history);
        $previous = array_shift($history);

        // If there hasn't been enough history to determine this, there's no upgrade time to share.
        if (! $latest || ! $previous) {
            return null;
        }

        // If this was not an upgrade, return null.
        if (! $this->getComparator()->greaterThan($latest, $previous)) {
            return null;
        }

        // Try to make the DateTime object from the dates.
        try {
            return new DateTime($dates[0]);
        } catch (Exception $date) {
            return null;
        }
    }
}
