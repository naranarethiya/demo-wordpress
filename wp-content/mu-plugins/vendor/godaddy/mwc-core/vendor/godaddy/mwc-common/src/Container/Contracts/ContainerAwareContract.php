<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Contracts;

interface ContainerAwareContract
{
    /**
     * Sets the container instance.
     *
     * @param ContainerContract $container
     * @return $this
     */
    public function setContainer(ContainerContract $container) : ContainerAwareContract;

    /**
     * Gets the container instance.
     *
     * @return ContainerContract
     */
    public function getContainer() : ContainerContract;
}
