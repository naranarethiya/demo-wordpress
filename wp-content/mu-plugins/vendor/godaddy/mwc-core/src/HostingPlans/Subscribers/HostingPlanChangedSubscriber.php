<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\ClassNotFoundException;
use GoDaddy\WordPress\MWC\Common\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Common\HostingPlans\HostingPlanRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\HostingPlans\Repositories\Contracts\HostingPlanRepositoryContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Events\HostingPlanChangeEvent;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Events\HostingPlanDowngradeEvent;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Events\HostingPlanUpgradeEvent;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Services\HostingPlanComparatorService;

class HostingPlanChangedSubscriber implements SubscriberContract
{
    /** @var HostingPlanRepositoryContract|null */
    protected ?HostingPlanRepositoryContract $hostingPlanRepository = null;

    /** @var HostingPlanComparatorService|null */
    protected ?HostingPlanComparatorService $hostingPlanComparatorService = null;

    /**
     * {@inheritDoc}
     * @throws ClassNotFoundException|InvalidClassNameException
     */
    public function handle(EventContract $event) : void
    {
        if ($event instanceof HostingPlanChangeEvent) {
            $this->onHostingPlanChanged($event);
        }
    }

    /**
     * Handles the hosting plan changed event.
     *
     * @param HostingPlanChangeEvent $event
     * @return void
     * @throws ClassNotFoundException|InvalidClassNameException
     */
    protected function onHostingPlanChanged(HostingPlanChangeEvent $event) : void
    {
        $repository = $this->repository();

        /** @var HostingPlanContract $updatedPlan */
        $updatedPlan = $event->getModel();
        $storedPlan = $repository->getStored();

        // store plan if nothing stored
        if (! $storedPlan) {
            $repository->add($updatedPlan);

            return;
        }

        $comparator = $this->comparator();

        // plan upgraded
        if ($comparator->greaterThan($updatedPlan, $storedPlan)) {
            Events::broadcast(HostingPlanUpgradeEvent::from($repository->add($updatedPlan)));

            return;
        }

        // plan downgraded
        if ($comparator->greaterThan($storedPlan, $updatedPlan)) {
            Events::broadcast(HostingPlanDowngradeEvent::from($repository->add($updatedPlan)));

            return;
        }

        // plan not changed, maybe a renewal
        $repository->save($updatedPlan);
    }

    /**
     * Gets hosting plan repository instance.
     *
     * @return HostingPlanRepositoryContract
     * @throws ClassNotFoundException|InvalidClassNameException
     */
    protected function repository() : HostingPlanRepositoryContract
    {
        if (null === $this->hostingPlanRepository) {
            $this->hostingPlanRepository = HostingPlanRepositoryFactory::getNewInstance()->getHostingPlanRepository();
        }

        return $this->hostingPlanRepository;
    }

    /**
     * Gets hosting plan comparator service instance.
     *
     * @return HostingPlanComparatorService
     */
    protected function comparator() : HostingPlanComparatorService
    {
        if (null === $this->hostingPlanComparatorService) {
            $this->hostingPlanComparatorService = HostingPlanComparatorService::getNewInstance();
        }

        return $this->hostingPlanComparatorService;
    }
}
