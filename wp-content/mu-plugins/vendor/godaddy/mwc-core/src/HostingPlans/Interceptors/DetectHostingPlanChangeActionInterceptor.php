<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\ClassNotFoundException;
use GoDaddy\WordPress\MWC\Common\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\HostingPlans\HostingPlanRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Events\HostingPlanChangeEvent;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Services\HostingPlanComparatorService;

class DetectHostingPlanChangeActionInterceptor extends AbstractInterceptor
{
    public const ACTION_NAME = 'mwc_detect_hosting_plan_change';

    protected const LOCK_PREFIX = 'mwc_detect_hosting_plan_change_lock_';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup(static::ACTION_NAME)
            ->setHandler([$this, 'detectHostingPlanChange'])
            ->execute();
    }

    /**
     * Compares the current plan with the stored plan and broadcasts an event if they are different.
     *
     * @internal
     *
     * @param mixed $hash
     * @return void
     */
    public function detectHostingPlanChange($hash = null) : void
    {
        if (! $hash = TypeHelper::string($hash, '')) {
            return;
        }

        if (! set_transient(static::LOCK_PREFIX.$hash, 1, 15 * MINUTE_IN_SECONDS)) {
            return;
        }

        try {
            $this->maybeBroadcastHostingPlanChangeEvent();
        } catch (ClassNotFoundException|InvalidClassNameException $exception) {
            return;
        }
    }

    /**
     * Broadcasts a {@see HostingPlanChangeEvent} if the current plan information doesn't match the stored information.
     *
     * @return void
     * @throws ClassNotFoundException
     * @throws InvalidClassNameException
     */
    protected function maybeBroadcastHostingPlanChangeEvent() : void
    {
        $hostingPlanRepository = HostingPlanRepositoryFactory::getNewInstance()->getHostingPlanRepository();

        $currentPlan = $hostingPlanRepository->getCurrent();
        $storedPlan = $hostingPlanRepository->getStored();

        if ($storedPlan && HostingPlanComparatorService::getNewInstance()->equalsTo($currentPlan, $storedPlan)) {
            return;
        }

        Events::broadcast(HostingPlanChangeEvent::from($currentPlan));
    }
}
