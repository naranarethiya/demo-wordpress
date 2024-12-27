<?php

namespace GoDaddy\WordPress\MWC\Common\Components\Traits;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\DelayedInstantiationComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\DelayedLoadingComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Features\EnabledFeaturesCache;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * Common functionality for classes that need to load one or more components.
 */
trait HasComponentsTrait
{
    /**
     * Gets an array of fully qualified names of components classes to instantiate.
     *
     * @return class-string<ComponentContract>[]
     * @throws ComponentClassesNotDefinedException
     */
    protected function getComponentClasses() : array
    {
        if (! property_exists($this, 'componentClasses')) {
            throw new ComponentClassesNotDefinedException(get_class($this).' must define a componentClasses property with a list of component classes to instantiate.');
        }

        return ArrayHelper::wrap($this->componentClasses);
    }

    /**
     * Instantiates all registered components.
     *
     * @return ComponentContract[]
     * @throws ComponentClassesNotDefinedException|ComponentLoadFailedException
     */
    protected function loadComponents() : array
    {
        /** @throws ComponentClassesNotDefinedException|ComponentLoadFailedException */
        return ArrayHelper::whereNotNull(array_map([static::class, 'maybeLoadComponent'], $this->getComponentClasses()), false);
    }

    /**
     * Attempts to create an instance of the given class name and load it.
     *
     * Throws an exception if the given class name is not a component.
     *
     * If the component is a delayed instantiation component, schedules the instantiation as defined by the component.
     *
     * @param string $className the name of the class to instantiate
     * @return ComponentContract|null
     * @throws ComponentLoadFailedException
     */
    public static function maybeLoadComponent(string $className)
    {
        if (! is_a($className, ComponentContract::class, true)) {
            throw new ComponentLoadFailedException("{$className} does not implement the ComponentContract interface.");
        }

        if (is_a($className, DelayedInstantiationComponentContract::class, true)) {
            $className::scheduleInstantiation(function () use ($className) {
                static::maybeInstantiateComponent($className);
            });

            return null;
        }

        return static::maybeInstantiateComponent($className);
    }

    /**
     * Creates an instance of the given class name and loads it.
     *
     * If the component is a delayed loading component, schedules the loading as defined by the component.
     *
     * @param class-string<ComponentContract> $className the name of the class to instantiate
     * @return ComponentContract|null
     */
    public static function maybeInstantiateComponent(string $className)
    {
        if (is_a($className, ConditionalComponentContract::class, true) && ! $className::shouldLoad()) {
            return null;
        }

        $component = static::instantiateComponent($className);

        if (is_a($component, DelayedLoadingComponentContract::class, true)) {
            $component::scheduleLoading(function () use ($component) {
                static::loadComponent($component);
            });

            return $component;
        }

        return static::loadComponent($component);
    }

    /**
     * Creates an instance of the given component class name.
     *
     * @param class-string<ComponentContract> $className the name of the class to instantiate
     * @return ComponentContract
     */
    protected static function instantiateComponent(string $className) : ComponentContract
    {
        return new $className();
    }

    /**
     * Loads a component.
     *
     * @param ComponentContract $component
     * @return ComponentContract
     */
    public static function loadComponent(ComponentContract $component) : ComponentContract
    {
        $component->load();
        static::maybeCacheEnabledFeature($component);

        return $component;
    }

    /**
     * Adds a feature to the Enabled Features Cache if it isn't already in there.
     *
     * @param ComponentContract $component
     * @return void
     */
    public static function maybeCacheEnabledFeature(ComponentContract $component)
    {
        if (is_a($component, AbstractFeature::class)) {
            $enabledFeaturesCache = EnabledFeaturesCache::getNewInstance();
            $cache = (array) $enabledFeaturesCache->get();
            if (! ArrayHelper::contains($cache, $component::getName())) {
                $cache[] = $component::getName();
                $enabledFeaturesCache->set($cache);
            }
        }
    }
}
