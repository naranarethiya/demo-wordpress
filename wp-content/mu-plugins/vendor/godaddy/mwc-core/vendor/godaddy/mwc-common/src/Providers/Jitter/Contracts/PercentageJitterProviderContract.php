<?php

namespace GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts;

interface PercentageJitterProviderContract extends CanGetJitterContract
{
    /**
     * @param float $value Upper/lower bound as percentage of base value that can be jitter. Negative value will be lower bound.
     *
     * @return $this
     */
    public function setRate(float $value) : CanGetJitterContract;
}
