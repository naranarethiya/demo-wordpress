<?php

namespace GoDaddy\WordPress\MWC\Core\API;

use GoDaddy\WordPress\MWC\Common\API\API as CommonAPI;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Core\API\Controllers\AccountController;
use GoDaddy\WordPress\MWC\Core\API\Controllers\FeaturesController;
use GoDaddy\WordPress\MWC\Core\API\Controllers\Orders\ShipmentsController;
use GoDaddy\WordPress\MWC\Core\API\Controllers\Platform\StoresController;

/**
 * Orders REST API handler.
 */
class API extends CommonAPI
{
    use HasComponentsFromContainerTrait;

    /** @var class-string<ComponentContract>[] */
    protected $componentClasses = [
        ShipmentsController::class,
        StoresController::class,
        AccountController::class,
        FeaturesController::class,
    ];
}
