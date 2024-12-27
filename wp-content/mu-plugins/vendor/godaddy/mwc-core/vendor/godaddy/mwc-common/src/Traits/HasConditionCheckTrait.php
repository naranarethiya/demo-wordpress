<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use Closure;

/**
 * A trait for objects that need to check a condition before performing an action.
 */
trait HasConditionCheckTrait
{
    /** @var callable|Closure|array<object, string>|string|mixed|null closure, function or method callback */
    protected $condition;

    /**
     * Sets the condition.
     *
     * @param callable|Closure|array<object, string>|string|mixed $condition
     * @return $this
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Removes the condition.
     *
     * @return $this
     */
    public function removeCondition()
    {
        $this->condition = null;

        return $this;
    }

    /**
     * Determines if a condition is present.
     *
     * @return bool
     */
    protected function hasCondition() : bool
    {
        return null !== $this->condition;
    }

    /**
     * Determines if the condition passes.
     *
     * @return bool
     */
    protected function conditionPasses() : bool
    {
        return ! $this->hasCondition() || (($this->condition instanceof Closure || is_callable($this->condition)) && call_user_func($this->condition));
    }
}
