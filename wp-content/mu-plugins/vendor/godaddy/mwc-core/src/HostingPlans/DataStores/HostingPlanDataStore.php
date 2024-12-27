<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\DataStores;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\HostingPlans\DataSources\WooCommerce\Adapters\HostingPlanAdapter;
use GoDaddy\WordPress\MWC\Core\HostingPlans\DataStores\Contracts\HostingPlanDataStoreContract;

class HostingPlanDataStore implements HostingPlanDataStoreContract
{
    use CanGetNewInstanceTrait;

    /**
     * Gets raw stored data from options table.
     *
     * @return array<string, mixed>
     */
    protected function getStoredData() : array
    {
        return ArrayHelper::wrap(get_option('mwc_hosting_plans', []));
    }

    /**
     * Stores data into options table.
     *
     * @param array<string, mixed> $data
     * @return void
     */
    protected function storeData(array $data) : void
    {
        update_option('mwc_hosting_plans', $data);
    }

    /**
     * Stores list of adapted hosting plan objects.
     *
     * @param array<string, HostingPlanContract> $hostingPlans
     * @return void
     */
    protected function storeHostingPlans(array $hostingPlans) : void
    {
        $data = [];
        $adapter = HostingPlanAdapter::getNewInstance([]);

        foreach ($hostingPlans as $timestamp => $hostingPlan) {
            $data[$timestamp] = ['plan' => $adapter->convertToSource($hostingPlan)];
        }

        $this->storeData($data);
    }

    /**
     * Gets list of adapted stored hosting plan objects.
     *
     * @return HostingPlanContract[]
     */
    protected function getStoredHostingPlans() : array
    {
        $hostingPlans = [];

        foreach ($this->getStoredData() as $timestamp => $data) {
            if (! $planData = ArrayHelper::get($data, 'plan')) {
                continue;
            }

            if (! $hostingPlan = HostingPlanAdapter::getNewInstance($planData)->convertFromSource()) {
                continue;
            }

            $hostingPlans[$timestamp] = $hostingPlan;
        }

        uksort($hostingPlans, static function (string $timestampA, string $timestampB) {
            return strtotime($timestampB) - strtotime($timestampA);
        });

        return $hostingPlans;
    }

    /**
     * {@inheritDoc}
     */
    public function latest() : ?HostingPlanContract
    {
        $hostingPlans = $this->getStoredHostingPlans();

        return array_shift($hostingPlans);
    }

    /**
     * {@inheritDoc}
     */
    public function all() : array
    {
        return $this->getStoredHostingPlans();
    }

    /**
     * {@inheritDoc}
     */
    public function save(HostingPlanContract $hostingPlan) : HostingPlanContract
    {
        $this->storeHostingPlans($this->replaceLatest($hostingPlan));

        return $hostingPlan;
    }

    /**
     * Replaces the latest.
     *
     * @param HostingPlanContract $hostingPlan
     * @return HostingPlanContract[]
     */
    protected function replaceLatest(HostingPlanContract $hostingPlan) : array
    {
        $listWithoutFirst = array_slice($this->getStoredHostingPlans(), 1);

        $newLatest = [date(DATE_ATOM) => $hostingPlan];

        return array_merge($newLatest, $listWithoutFirst);
    }

    /**
     * {@inheritDoc}
     */
    public function add(HostingPlanContract $hostingPlan) : HostingPlanContract
    {
        $this->storeHostingPlans($this->prependPlan($hostingPlan));

        return $hostingPlan;
    }

    /**
     * Prepend a new plan entry to list.
     *
     * @param HostingPlanContract $hostingPlan
     * @return HostingPlanContract[]
     */
    protected function prependPlan(HostingPlanContract $hostingPlan) : array
    {
        $listWithoutLast = array_slice($this->getStoredHostingPlans(), 0, $this->maximumPlansToKeep() - 1);

        $newPlanEntry = [date(DATE_ATOM) => $hostingPlan];

        return array_merge($newPlanEntry, $listWithoutLast);
    }

    /**
     * Retrieves maximum number of hosting plan records to keep.
     *
     * @return int
     */
    protected function maximumPlansToKeep() : int
    {
        return TypeHelper::int(Configuration::get('hosting_plans.max_plans_to_keep'), 3);
    }
}
