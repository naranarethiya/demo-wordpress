<?php

namespace GoDaddy\WordPress\MWC\Common\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Generic event to be reused by model classes.
 */
class ModelEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var ModelContract the model with data for the current event */
    protected $model;

    /**
     * ModelEvent constructor.
     */
    public function __construct(ModelContract $model, string $resource, string $action)
    {
        $this->model = $model;
        $this->resource = $resource;
        $this->action = $action;
    }

    /**
     * Gets the event model instance.
     *
     * @return ModelContract
     */
    public function getModel() : ModelContract
    {
        return $this->model;
    }

    /**
     * Builds the initial data for the event.
     */
    protected function buildInitialData() : array
    {
        return [
            'resource' => $this->model->toArray(),
        ];
    }
}
