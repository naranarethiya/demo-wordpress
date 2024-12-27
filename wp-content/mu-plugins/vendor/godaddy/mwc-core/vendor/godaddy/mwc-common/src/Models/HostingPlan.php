<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * An object representation of a GoDaddy hosting plan.
 */
class HostingPlan extends AbstractModel implements HostingPlanContract
{
    use HasLabelTrait;

    /** @var bool */
    protected $isTrial;

    /**
     * {@inheritDoc}
     */
    public function isTrial() : bool
    {
        return $this->isTrial;
    }

    /**
     * Sets is trail property value.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setIsTrial(bool $value) : HostingPlan
    {
        $this->isTrial = $value;

        return $this;
    }
}
