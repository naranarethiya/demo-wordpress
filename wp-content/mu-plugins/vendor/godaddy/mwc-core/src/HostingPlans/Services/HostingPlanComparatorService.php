<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Services;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class HostingPlanComparatorService
{
    use CanGetNewInstanceTrait;

    /**
     * Returns true if the two provided plans have the same name and trial status.
     *
     * @param HostingPlanContract $a
     * @param HostingPlanContract $b
     *
     * @return bool
     */
    public function equalsTo(HostingPlanContract $a, HostingPlanContract $b) : bool
    {
        return $a->getName() === $b->getName() && $a->isTrial() === $b->isTrial();
    }

    /**
     * Returns true if item A has a higher grade value than item B.
     *
     * @param HostingPlanContract $a
     * @param HostingPlanContract $b
     *
     * @return bool
     */
    public function greaterThan(HostingPlanContract $a, HostingPlanContract $b) : bool
    {
        return $this->getPlanGrade($a) > $this->getPlanGrade($b);
    }

    /**
     * Attempts to retrieve a plan grade from the given hosting plan.
     *
     * @param HostingPlanContract $hostingPlan
     *
     * @return int The plan's grade value
     */
    protected function getPlanGrade(HostingPlanContract $hostingPlan) : int
    {
        $plans = ArrayHelper::wrap(Configuration::get('hosting_plans.mappings', []));
        foreach ($plans as $plan) {
            if (ArrayHelper::get($plan, 'name') === $hostingPlan->getName()) {
                return TypeHelper::int(ArrayHelper::get($plan, 'grade'), 0);
            }
        }

        return 0;
    }
}
