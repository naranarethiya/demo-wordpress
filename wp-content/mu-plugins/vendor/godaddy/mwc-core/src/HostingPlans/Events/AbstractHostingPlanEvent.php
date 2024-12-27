<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Events;

use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;

abstract class AbstractHostingPlanEvent extends ModelEvent
{
    /**
     * Constructor.
     *
     * This method is redeclared as final in this class so that we can safely use `new static()` in subclasses.
     *
     * @param ModelContract $model
     * @param string $resource
     * @param string $action
     */
    final public function __construct(ModelContract $model, string $resource, string $action)
    {
        parent::__construct($model, $resource, $action);
    }

    /**
     * Creates an instance of the called class using the given event.
     *
     * @param HostingPlanContract $hostingPlan
     * @return static
     */
    abstract public static function from(HostingPlanContract $hostingPlan);
}
