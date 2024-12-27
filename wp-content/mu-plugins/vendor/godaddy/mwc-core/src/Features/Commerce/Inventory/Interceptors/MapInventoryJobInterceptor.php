<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterAction;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Interceptors\Handlers\MapInventoryJobHandler;

/**
 * Interceptor used to register the {@see MapInventoryJobHandler} job.
 */
class MapInventoryJobInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        RegisterAction::action()
            ->setGroup(MapInventoryJobHandler::JOB_NAME)
            ->setArgumentsCount(2)
            ->setHandler([MapInventoryJobHandler::class, 'handle'])
            ->execute();
    }
}
