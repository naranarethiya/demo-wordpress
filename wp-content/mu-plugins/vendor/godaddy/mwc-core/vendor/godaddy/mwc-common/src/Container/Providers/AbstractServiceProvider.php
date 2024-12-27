<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Providers;

use GoDaddy\WordPress\MWC\Common\Container\Contracts\ContainerAwareContract;
use GoDaddy\WordPress\MWC\Common\Container\Contracts\ContainerContract;
use GoDaddy\WordPress\MWC\Common\Container\Contracts\ServiceProviderContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

abstract class AbstractServiceProvider implements ServiceProviderContract
{
    /** @var ContainerContract */
    protected ContainerContract $container;

    /** @var string[] list of abstracts for which this provider provides concrete implementations */
    protected array $provides = [];

    /**
     * Sets the container instance.
     *
     * @return $this
     */
    public function setContainer(ContainerContract $container) : ContainerAwareContract
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Gets the container instance.
     *
     * @return ContainerContract
     */
    public function getContainer() : ContainerContract
    {
        return $this->container;
    }

    /**
     * {@inheritDoc}
     */
    public function provides(string $id) : bool
    {
        $classNames = $this->provides;

        if (! $classNames) {
            return false;
        }

        if (count($classNames) === 1) {
            return $classNames === ArrayHelper::wrap($id);
        }

        return ArrayHelper::contains($classNames, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinitionIdentifiers() : array
    {
        return $this->provides;
    }
}
