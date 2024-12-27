<?php

namespace GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts;

interface CanGetJitterContract
{
    /**
     * @param int $value Base value from which to calculate jitter.
     *
     * @return int
     */
    public function getJitter(int $value) : int;
}
