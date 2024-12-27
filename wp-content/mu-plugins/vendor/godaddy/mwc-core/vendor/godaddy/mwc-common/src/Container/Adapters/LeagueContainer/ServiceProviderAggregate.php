<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Adapters\LeagueContainer;

use GoDaddy\WordPress\MWC\Common\Container\Contracts\HasDefinitionIdentifiersContract;
use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\Exception\ContainerException;
use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\ServiceProvider\ServiceProviderAggregate as LeagueServiceProviderAggregate;
use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\ServiceProvider\ServiceProviderAggregateInterface;
use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\ServiceProvider\ServiceProviderInterface;
use function sprintf;

/**
 * A custom implementation of {@see ServiceProviderAggregateInterface} that indexes providers by the ID of the definitions that they provide.
 *
 * The {@see LeagueServiceProviderAggregate} class loops through all available providers everytime it
 * needs to find a provider for a given ID, calling the {@see ServiceProviderInterface::provides()} method
 * on each instance. That approach results in a large number of loops and unnecessary checks whenever the
 * container is used to instantiate a class that doesn't have a definition already.
 *
 * This class, on the other hand, builds an index of definition IDs and providers that allows it to quickly
 * retrieve the provider instance that provides a given definition ID, if any.
 */
class ServiceProviderAggregate extends LeagueServiceProviderAggregate
{
    /** @var array<string, ServiceProviderInterface&HasDefinitionIdentifiersContract> */
    protected $provides = [];

    /**
     * @var array<string, true>
     */
    protected $registered = [];

    /**
     * @param ServiceProviderInterface&HasDefinitionIdentifiersContract $provider
     */
    public function add(ServiceProviderInterface $provider) : ServiceProviderAggregateInterface
    {
        parent::add($provider);

        $this->provides += array_fill_keys($provider->getDefinitionIdentifiers(), $provider);

        return $this;
    }

    public function provides(string $service) : bool
    {
        return isset($this->provides[$service]);
    }

    /**
     * @throws ContainerException
     */
    public function register(string $service) : void
    {
        $provider = $this->provides[$service] ?? null;

        if (! $provider) {
            throw new ContainerException(sprintf('(%s) is not provided by a service provider', $service));
        }

        if (isset($this->registered[$provider->getIdentifier()])) {
            return;
        }

        $provider->register();

        $this->registered[$provider->getIdentifier()] = true;
    }
}
