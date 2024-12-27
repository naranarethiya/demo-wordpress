<?php

namespace GoDaddy\WordPress\MWC\Core\Extensions\Configuration;

use GoDaddy\WordPress\MWC\Common\Extensions\Configuration\ManagedExtensionsRuntimeConfiguration as CommonManagedExtensionsRuntimeConfiguration;
use GoDaddy\WordPress\MWC\Common\Extensions\Enums\BrandsEnum;
use GoDaddy\WordPress\MWC\Common\HostingPlans\Enums\HostingPlanNamesEnum;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;

class ManagedExtensionsRuntimeConfiguration extends CommonManagedExtensionsRuntimeConfiguration
{
    /** @var HostingPlanContract */
    protected HostingPlanContract $hostingPlan;

    /**
     * class constructor.
     * @param HostingPlanContract $hostingPlan
     */
    public function __construct(
        HostingPlanContract $hostingPlan
    ) {
        $this->hostingPlan = $hostingPlan;
    }

    /**
     * {@inheritDoc}
     */
    public function getExcludedBrands() : array
    {
        $brands = parent::getExcludedBrands();

        if (HostingPlanNamesEnum::isEssentialsPlan($this->hostingPlan->getName())) {
            $brands[] = BrandsEnum::Woo;
        }

        return array_unique($brands);
    }
}
