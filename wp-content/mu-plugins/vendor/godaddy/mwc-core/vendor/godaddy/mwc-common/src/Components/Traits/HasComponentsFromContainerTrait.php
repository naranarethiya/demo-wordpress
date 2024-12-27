<?php

namespace GoDaddy\WordPress\MWC\Common\Components\Traits;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use Throwable;

/**
 * Trait for classes that have components from a container.
 */
trait HasComponentsFromContainerTrait
{
    use HasComponentsTrait;

    /**
     * Creates an instance of the given component class name.
     *
     * @param class-string<ComponentContract> $className the name of the class to instantiate
     * @return ComponentContract
     */
    protected static function instantiateComponent(string $className) : ComponentContract
    {
        $container = ContainerFactory::getInstance()->getSharedContainer();

        try {
            return $container->get($className);
        } catch (Throwable $exception) {
            return new $className();
        }
    }
}
