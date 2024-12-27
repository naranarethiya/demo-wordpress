<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\ExtensionsController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\MessagesController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Orders\ItemsController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Orders\OrdersController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\PluginsController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Shipping\ProvidersController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\ShopController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\SupportController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\UserController;

class API
{
    use IsConditionalFeatureTrait;

    /**
     * All available API controllers.
     *
     * @var array<string, AbstractController>
     */
    protected array $controllers;

    /**
     * Class constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setControllers();

        Register::action()
            ->setGroup('rest_api_init')
            ->setHandler([$this, 'registerRoutes'])
            ->execute();
    }

    /**
     * Registers all available API controllers.
     *
     * @return void
     */
    protected function setControllers() : void
    {
        $this->controllers = [
            MessagesController::class => new MessagesController(),
            PluginsController::class  => new PluginsController(),
            ShopController::class     => new ShopController(),
            SupportController::class  => new SupportController(),
            UserController::class     => new UserController(),
        ];

        if (WooCommerceRepository::isWooCommerceActive()) {
            $this->controllers = array_merge($this->controllers, [
                ExtensionsController::class => new ExtensionsController(),
                ItemsController::class      => new ItemsController(),
                OrdersController::class     => new OrdersController(),
                ProvidersController::class  => new ProvidersController(),
            ]);
        }
    }

    /**
     * Registers the routes for all available API controllers.
     */
    public function registerRoutes() : void
    {
        foreach ($this->controllers as $controller) {
            $controller->registerRoutes();
        }
    }

    /**
     * Determines whether the feature can be loaded.
     *
     * @return bool
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        /* @TODO we always load the API because we need some controllers also when WooCommerce isn't available, in the future we may refactor this {unfulvio 2021-08-10} */
        return true;
    }
}
