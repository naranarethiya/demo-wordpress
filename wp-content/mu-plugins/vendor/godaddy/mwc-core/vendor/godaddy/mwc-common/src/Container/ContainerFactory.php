<?php

namespace GoDaddy\WordPress\MWC\Common\Container;

use GoDaddy\WordPress\MWC\Common\Container\Contracts\ContainerContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

/**
 * Container factory singleton.
 *
 * Generally this is used only to set up the container and instantiate components at the app's main entrypoint.
 */
class ContainerFactory
{
    use IsSingletonTrait;

    /** @var ContainerContract|null */
    protected ?ContainerContract $container = null;

    /**
     * Gets the shared container.
     *
     * @return ContainerContract
     */
    public function getSharedContainer() : ContainerContract
    {
        return $this->container ??= new Container();
    }
}
