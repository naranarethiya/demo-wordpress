<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;

interface HostingPlanContract extends ModelContract, HasLabelContract
{
    /**
     * Returns true if this hosting plan is a trial.
     *
     * @return bool
     */
    public function isTrial() : bool;

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setIsTrial(bool $value);
}
