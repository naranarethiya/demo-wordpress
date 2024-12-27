<?php

namespace GoDaddy\WordPress\MWC\Common\Providers\Jitter;

use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\CanGetJitterContract;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\PercentageJitterProviderContract;

/**
 * The main percentage Jitter implementation.
 */
class PercentageJitterProvider implements PercentageJitterProviderContract
{
    /** @var float the base percentage rate to vary the results around it */
    protected float $rate = 0.2;

    /**
     * {@inheritDoc}
     */
    public function getJitter(int $value) : int
    {
        if (0.0 === $this->rate) {
            return 0;
        }

        $jitterBound = (int) ($this->rate * $value);

        return mt_rand(min($jitterBound, 0), max($jitterBound, 0));
    }

    /**
     * {@inheritDoc}
     */
    public function setRate(float $value) : CanGetJitterContract
    {
        $this->rate = $value;

        return $this;
    }
}
