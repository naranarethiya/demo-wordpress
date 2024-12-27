<?php

namespace GoDaddy\WordPress\MWC\Common\Pipeline;

use Closure;
use Exception;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Contracts\ContainerContract;
use GoDaddy\WordPress\MWC\Common\Pipeline\Contracts\PipelineContract;
use GoDaddy\WordPress\MWC\Common\Pipeline\Exceptions\InvalidPipeException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use Throwable;

/**
 * Pipelines provide a convenient way to "pipe" a given output through a series of invokable classes, closures, or
 * callables, giving each class the opportunity to inspect or modify the output and invoke the next callable in
 * the pipeline.
 */
class Pipeline implements PipelineContract
{
    use CanGetNewInstanceTrait;

    /** @var ContainerContract|null the container implementation */
    protected ?ContainerContract $container = null;

    /** @var mixed the object being passed through the pipeline */
    protected $passable;

    /** @var array<object|class-string|Closure|callable|string> the array of class pipes */
    protected array $pipes = [];

    /** @var string the method to call on each pipe */
    protected string $method = 'handle';

    /** {@inheritDoc} */
    public function send($passable) : PipelineContract
    {
        $this->passable = $passable;

        return $this;
    }

    /** {@inheritDoc} */
    public function through(array $pipes) : PipelineContract
    {
        $this->pipes = $pipes;

        return $this;
    }

    /** {@inheritDoc} */
    public function via(string $method) : PipelineContract
    {
        $this->method = $method;

        return $this;
    }

    /** {@inheritDoc} */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes()),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Runs the pipeline and returns the result.
     *
     * @return mixed
     * @throws Throwable
     */
    public function thenReturn()
    {
        return $this->then(fn ($passable) => $passable);
    }

    /**
     * Get the final piece of the Closure onion.
     *
     * @param Closure $destination
     * @return Closure
     */
    protected function prepareDestination(Closure $destination) : Closure
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch(Throwable $e) {
                return $this->handleException($passable, $e);
            }
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return Closure
     * @phpstan-ignore-next-line the pipe's method may throw an exception, which phpstan doesn't understand
     * @throws Throwable
     */
    protected function carry() : Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    if (is_callable($pipe)) {
                        // If the pipe is a callable, then we will call it directly, but otherwise we
                        // will resolve the pipes out of the dependency container and call it with
                        // the appropriate method and arguments, returning the results back out.
                        return $pipe($passable, $stack);
                    } elseif (! is_object($pipe)) {
                        [$name, $parameters] = $this->parsePipeString($pipe);

                        // If the pipe is a string we will parse the string and resolve the class out
                        // of the dependency injection container. We can then build a callable and
                        // execute the pipe function giving in the parameters that are required.
                        /** @var object $pipe */
                        $pipe = $this->getContainer()->get($name);

                        $parameters = array_merge([$passable, $stack], $parameters);
                    } else {
                        // If the pipe is already an object we'll just make a callable and pass it to
                        // the pipe as-is. There is no need to do any extra parsing and formatting
                        // since the object we're given was already a fully instantiated object.
                        $parameters = [$passable, $stack];
                    }

                    if (method_exists($pipe, $this->method)) {
                        $carry = $pipe->{$this->method}(...$parameters);
                    } elseif (is_callable($pipe)) {
                        $carry = $pipe(...$parameters);
                    } else {
                        throw new InvalidPipeException();
                    }

                    return $this->handleCarry($carry);
                } catch (Throwable $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string  $pipe
     * @return array{0: string, 1: array<mixed>}
     */
    protected function parsePipeString(string $pipe) : array
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * Get the array of configured pipes.
     *
     * @return array<object|class-string|Closure|callable|string>
     */
    protected function pipes() : array
    {
        return $this->pipes;
    }

    /**
     * Gets the container instance.
     *
     * @return ContainerContract
     */
    protected function getContainer() : ContainerContract
    {
        return $this->container ??= ContainerFactory::getInstance()->getSharedContainer();
    }

    /**
     * Handle the value returned from each pipe before passing it to the next.
     *
     * @param  mixed  $carry
     * @return mixed
     */
    protected function handleCarry($carry)
    {
        return $carry;
    }

    /**
     * Handle the given exception.
     *
     * @param  mixed  $passable
     * @param Throwable $e
     * @return mixed
     *
     * @throws Throwable
     */
    protected function handleException($passable, Throwable $e)
    {
        throw $e;
    }
}
