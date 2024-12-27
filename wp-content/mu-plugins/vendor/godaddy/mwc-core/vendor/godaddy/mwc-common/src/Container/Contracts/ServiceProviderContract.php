<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Contracts;

interface ServiceProviderContract extends ContainerAwareContract, HasDefinitionIdentifiersContract
{
    /**
     * Returns true if this class provides a container definition with the given id, false otherwise.
     *
     * @param string $id
     * @return bool
     */
    public function provides(string $id) : bool;

    /**
     * Uses container to register bindings.
     *
     * @return void
     */
    public function register() : void;
}
