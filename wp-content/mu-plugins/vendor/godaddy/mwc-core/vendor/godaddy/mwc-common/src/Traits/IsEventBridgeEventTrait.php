<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * Trait for event bridges.
 *
 * @see EventBridgeEventContract interface - this trait implements some of its methods
 */
trait IsEventBridgeEventTrait
{
    /** @var string the name of the event resource */
    protected $resource;

    /** @var string the name of the event action */
    protected $action;

    /** @var array<mixed> the data for this event */
    protected $data;

    /** @var string the context of the event */
    protected $context;

    /**
     * Gets the name of the resource for the event.
     *
     * @return string
     */
    public function getResource() : string
    {
        return $this->resource ?: '';
    }

    /**
     * Gets the name of the action for the event.
     *
     * @return string
     */
    public function getAction() : string
    {
        return $this->action ?: '';
    }

    /**
     * Sets the data for the event.
     *
     * @param array<mixed> $data
     * @return $this|EventBridgeEventContract
     */
    public function setData(array $data) : EventBridgeEventContract
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Gets the data for the event, initializing it if needed.
     *
     * @return array<mixed>
     */
    public function getData() : array
    {
        if (null === $this->data) {
            $this->data = $this->buildInitialData();
        }

        return ArrayHelper::wrap($this->data);
    }

    /**
     * Sets the event context.
     *
     * @param string $value
     * @return $this|EventBridgeEventContract
     */
    public function setContext(string $value) : EventBridgeEventContract
    {
        $this->context = $value;

        return $this;
    }

    /**
     * Gets the event context.
     *
     * @return string
     */
    public function getContext() : string
    {
        return $this->context ?: '';
    }

    /**
     * Returns an array with initial data for this event.
     *
     * Subclasses can override this method to initialize the data based on the objects associated with the particular event.
     *
     * @return array<mixed>
     */
    protected function buildInitialData() : array
    {
        return [];
    }
}
