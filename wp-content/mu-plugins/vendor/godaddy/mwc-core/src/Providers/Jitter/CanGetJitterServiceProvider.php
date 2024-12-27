<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Jitter;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\Contracts\CanGetJitterContract;
use GoDaddy\WordPress\MWC\Common\Providers\Jitter\FullJitterProvider;

class CanGetJitterServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CanGetJitterContract::class];

    /**
     * {@inheritdoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(CanGetJitterContract::class, function () {
            return new FullJitterProvider();
        });
    }
}
