<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Interceptors;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\ClassNotFoundException;
use GoDaddy\WordPress\MWC\Common\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Common\HostingPlans\HostingPlanRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Common\Schedule\Types\SingleAction;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Services\HostingPlanComparatorService;

class RegisterHostingPlanChangeActionInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('shutdown')
            ->setHandler([$this, 'maybeRegisterSingleAction'])
            ->setCondition(function () {
                // try to limit processing to document requests
                return ! WordPressRepository::isAjax() && ! WordPressRepository::isApiRequest();
            })
            ->execute();
    }

    /**
     * Registers a recurring action to run every 15 minutes.
     *
     * @internal
     *
     * @return void
     */
    public function maybeRegisterSingleAction() : void
    {
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return;
        }

        try {
            $repository = HostingPlanRepositoryFactory::getNewInstance()->getHostingPlanRepository();
        } catch (ClassNotFoundException|InvalidClassNameException $exception) {
            return;
        }

        $current = $repository->getCurrent();
        $stored = $repository->getStored();

        if ($stored && HostingPlanComparatorService::getNewInstance()->equalsTo($current, $stored)) {
            return;
        }

        $job = $this->getDetectHostingPlanChangeAction($this->getHostingPlansHash($current, $stored));

        if ($job->isScheduled()) {
            return;
        }

        try {
            $job->schedule();
        } catch (InvalidScheduleException $exception) {
            return;
        }
    }

    /**
     * Gets a hash of the combined information from the given hosting plan objects.
     *
     * @param HostingPlanContract $current
     * @param HostingPlanContract|null $stored
     * @return string
     */
    protected function getHostingPlansHash(HostingPlanContract $current, ?HostingPlanContract $stored) : string
    {
        return md5(serialize([$current->toArray(), $stored ? $stored->toArray() : []]));
    }

    /**
     * Prepares a new single action object to check for hosting plan changes.
     *
     * @param string $hash
     * @return SingleAction
     */
    protected function getDetectHostingPlanChangeAction(string $hash) : SingleAction
    {
        return Schedule::singleAction()
            ->setName(DetectHostingPlanChangeActionInterceptor::ACTION_NAME)
            ->setArguments($hash)
            ->setScheduleAt(new DateTime());
    }
}
