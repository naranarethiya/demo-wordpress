<?php

namespace GoDaddy\WordPress\MWC\Common\Register;

use Closure;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterAction;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterFilter;
use GoDaddy\WordPress\MWC\Common\Traits\HasConditionCheckTrait;

/**
 * Registers an item.
 */
class Register
{
    use HasConditionCheckTrait;

    /** @var string registration type */
    protected $registrableType;

    /** @var string group name containing other items the item should be registered with */
    protected $groupName;

    /** @var callable|Closure|array<object, string>|string|mixed function, method or closure to be attached to the registered item's execution */
    protected $handler;

    /** @var int number of arguments to pass the handler */
    protected $numberOfArguments;

    /** @var int|null priority of the item being registered */
    protected $processPriority;

    /**
     * Registers an action.
     *
     * @return RegisterAction
     */
    public static function action() : RegisterAction
    {
        return new RegisterAction();
    }

    /**
     * Registers a filter.
     *
     * @return RegisterFilter
     */
    public static function filter() : RegisterFilter
    {
        return new RegisterFilter();
    }

    /**
     * Sets the registrable type for the current object.
     *
     * @param string $type a registrable type
     * @return $this
     */
    protected function setType(string $type) : Register
    {
        $this->registrableType = $type;

        return $this;
    }

    /**
     * Gets the registrable type.
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->registrableType ?: '';
    }

    /**
     * Sets the group name to register the handler to.
     *
     * @param string $name name of the group to register the handler to
     * @return $this
     */
    public function setGroup(string $name) : Register
    {
        $this->groupName = $name;

        return $this;
    }

    /**
     * Sets a handler for the item to register.
     *
     * @param callable|Closure|array<object, string>|string|mixed $handler function name (string), static method name (string) or array (object name, method name)
     * @return $this
     */
    public function setHandler($handler) : Register
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Determines if the item to register has a handler attached.
     *
     * @return bool
     */
    protected function hasHandler() : bool
    {
        return null !== $this->handler && ($this->handler instanceof Closure || is_callable($this->handler));
    }

    /**
     * Sets the priority for where in the overall order the registration should be processed.
     *
     * @param int|null $priority
     * @return $this
     */
    public function setPriority(?int $priority = null) : Register
    {
        $this->processPriority = $priority;

        return $this;
    }

    /**
     * Sets if the arguments to pass to the handler upon registration.
     *
     * @param int $arguments
     * @return $this
     */
    public function setArgumentsCount(int $arguments) : Register
    {
        $this->numberOfArguments = $arguments;

        return $this;
    }

    /**
     * Determines whether the registration should apply based on the defined condition, if present.
     *
     * @return bool
     */
    protected function shouldRegister() : bool
    {
        return $this->conditionPasses();
    }
}
