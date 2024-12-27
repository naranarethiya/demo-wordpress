<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Contracts;

use Closure;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Vendor\Psr\Container\ContainerInterface;

interface ContainerContract extends ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @template T of object
     * @param class-string<T>|string $id
     * @return ($id is class-string<T> ? T : mixed)
     * @throws EntryNotFoundException No entry was found for **this** identifier.
     * @throws ContainerException
     */
    public function get(string $id);

    /**
     * Register a binding in the container.
     *
     * @param string $abstract
     * @param class-string|Closure $concrete
     * @return void
     */
    public function bind(string $abstract, $concrete) : void;

    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract
     * @param class-string|Closure $concrete
     */
    public function singleton(string $abstract, $concrete) : void;

    /**
     * Adds a container.
     *
     * @param ServiceProviderContract $provider
     * @return void
     */
    public function addProvider(ServiceProviderContract $provider) : void;

    /**
     * Enables auto-wiring of class constructor arguments.
     *
     * @NOTE this has a cost due to use of reflection.
     *
     * @return void
     */
    public function enableAutoWiring() : void;
}
