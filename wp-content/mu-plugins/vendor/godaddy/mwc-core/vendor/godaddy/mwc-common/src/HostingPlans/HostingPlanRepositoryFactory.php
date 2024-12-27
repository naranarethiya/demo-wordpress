<?php

namespace GoDaddy\WordPress\MWC\Common\HostingPlans;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\ClassNotFoundException;
use GoDaddy\WordPress\MWC\Common\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Common\HostingPlans\Repositories\Contracts\HostingPlanRepositoryContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class HostingPlanRepositoryFactory
{
    use CanGetNewInstanceTrait;

    /**
     * Gets an instance of hosting plan repository using the class name defined in the configuration.
     *
     * @return HostingPlanRepositoryContract
     * @throws ClassNotFoundException|InvalidClassNameException
     */
    public function getHostingPlanRepository() : HostingPlanRepositoryContract
    {
        $instance = $this->getInstanceFromConfiguration(HostingPlanRepositoryContract::class, 'hosting_plans.repository');

        if (! $instance instanceof HostingPlanRepositoryContract) {
            throw new InvalidClassNameException(sprintf(
                "The instance found is not a '%s', does not implement that interface or has that class as one of its parents.",
                HostingPlanRepositoryContract::class
            ));
        }

        return $instance;
    }

    /**
     * Creates an instance of the class from the class name from configuration.
     *
     * @param class-string $contractOrClassName
     * @param string $key
     * @return object
     * @throws ClassNotFoundException|InvalidClassNameException
     */
    protected function getInstanceFromConfiguration(string $contractOrClassName, string $key) : object
    {
        $className = $this->getClassNameFromConfiguration($contractOrClassName, $key);

        return new $className();
    }

    /**
     * Gets the class name specified in the given configuration key and validates it before returning that class name.
     *
     * @param class-string $contractOrClassName
     * @param string $key
     * @return string
     * @throws ClassNotFoundException|InvalidClassNameException
     */
    protected function getClassNameFromConfiguration(string $contractOrClassName, string $key) : string
    {
        return $this->validateClassName(Configuration::get($key, ''), $contractOrClassName);
    }

    /**
     * Checks whether the given class name exists and implements/extends the given contract or class.
     *
     * @param class-string $className
     * @param class-string $contractOrClassName
     * @return string
     * @throws ClassNotFoundException|InvalidClassNameException
     */
    protected function validateClassName(string $className, string $contractOrClassName) : string
    {
        if (! class_exists($className)) {
            throw new ClassNotFoundException("The class '{$className}' does not exist.");
        }

        if (! is_a($className, $contractOrClassName, true)) {
            throw new InvalidClassNameException("The class '{$className}' is not a '{$contractOrClassName}', does not implement that interface or has that class as one of its parents.");
        }

        return $className;
    }
}
