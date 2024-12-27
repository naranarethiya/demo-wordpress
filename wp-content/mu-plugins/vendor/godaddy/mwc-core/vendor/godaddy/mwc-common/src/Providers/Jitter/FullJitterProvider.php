<?php

namespace GoDaddy\WordPress\MWC\Common\Providers\Jitter;

use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\CanGetJitterContract;

class FullJitterProvider implements CanGetJitterContract
{
    /**
     * {@inheritDoc}
     */
    public function getJitter(int $value) : int
    {
        return mt_rand(0, $value);
    }
}
