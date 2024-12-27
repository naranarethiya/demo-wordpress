<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Adapters\LeagueContainer;

use Closure;
use GoDaddy\WordPress\MWC\Common\Container\Contracts\ContainerContract;
use GoDaddy\WordPress\MWC\Common\Container\Contracts\ServiceProviderContract;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\Container as LeagueContainer;
use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\ReflectionContainer;
use GoDaddy\WordPress\MWC\Common\Vendor\Psr\Container\ContainerExceptionInterface;
use GoDaddy\WordPress\MWC\Common\Vendor\Psr\Container\NotFoundExceptionInterface;
use Throwable;

/**
 * Adapts {@see LeagueContainer} to interface with {@see ContainerContract}.
 */
class ContainerAdapter implements ContainerContract
{
    /** @var LeagueContainer */
    protected LeagueContainer $container;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->container = new LeagueContainer(
            new DefinitionAggregate(),
            new ServiceProviderAggregate(),
        );
    }

    /**
     * {@inheritDoc}
     *
     * @note Due to how league/container works, binding a class-string $concrete only works when auto-wiring is enabled
     *    {@see enableAutoWiring}. The alternative is to always pass a closure that instantiates the concrete class.
     */
    public function bind(string $abstract, $concrete) : void
    {
        if (! $concrete instanceof Closure) {
            $concrete = fn () => $this->get($concrete);
        }

        $this->container->add($abstract, $concrete);
    }

    /**
     * {@inheritDoc}
     */
    public function singleton(string $abstract, $concrete) : void
    {
        if (! $concrete instanceof Closure) {
            $concrete = fn () => $this->get($concrete);
        }

        $this->container->addShared($abstract, $concrete);
    }

    /**
     * Add a provider.
     *
     * @NOTE We might want to move the inner parts of this method to its own class so it can do more stuff, like register bootable providers.
     *
     * @param ServiceProviderContract $provider
     * @return void
     */
    public function addProvider(ServiceProviderContract $provider) : void
    {
        $provider->setContainer($this);

        $this->container->addServiceProvider(ServiceProviderAdapter::getNewInstance($provider));
    }

    /**
     * {@inheritDoc}
     *
     * @NOTE Once enabled, it cannot be disabled on the same container instance.
     */
    public function enableAutoWiring() : void
    {
        $this->container->delegate(new ReflectionContainer());
    }

    /**
     * {@inheritDoc}
     */
    public function get($id)
    {
        try {
            $instance = $this->container->get($id);
        } catch (NotFoundExceptionInterface $exception) {
            throw new EntryNotFoundException($exception->getMessage(), $exception);
        } catch (ContainerExceptionInterface|Throwable $throwable) {
            throw new ContainerException($throwable->getMessage(), $throwable);
        }

        $this->validateInstanceType($instance, $id);

        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function has($id) : bool
    {
        return $this->container->has($id);
    }

    /**
     * Validates that instance is an object of given type, if type is a class or interface.
     *
     * @param string $id
     * @param mixed $instance
     *
     * @throws ContainerException
     */
    protected function validateInstanceType($instance, string $id) : void
    {
        if ((class_exists($id) || interface_exists($id)) &&
            (! is_object($instance) || ! is_a($instance, $id))) {
            throw new ContainerException("Error while retrieving an instance of {$id} from the container.");
        }
    }
}
