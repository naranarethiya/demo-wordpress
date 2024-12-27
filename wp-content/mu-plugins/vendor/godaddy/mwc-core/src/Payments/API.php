<?php

namespace GoDaddy\WordPress\MWC\Core\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\API as CommonAPI;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\CartController;
use GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\GoDaddyPayments\Wallets\Processing\PayController;
use GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\GoDaddyPayments\Wallets\WalletRequestController;
use GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\GoDaddyPaymentsController;
use GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\ProviderProcessingController;
use GoDaddy\WordPress\MWC\Core\Payments\API\Traits\VerifiesNonceTrait;

/**
 * Payments REST API handler.
 */
class API extends CommonAPI
{
    use HasComponentsTrait;

    /** @var AbstractController[] */
    protected $components = [];

    /** @var string */
    public const NONCE_ACTION = 'mwc_payments_rest_api';

    /** @var class-string<ComponentContract>[] */
    protected $componentClasses = [
        GoDaddyPaymentsController::class,
        ProviderProcessingController::class,
        CartController::class,
        PayController::class,
        WalletRequestController::class,
    ];

    /**
     * Loads the API component.
     *
     * @throws Exception
     */
    public function load() : void
    {
        parent::load();

        Register::action()
            ->setGroup('rest_authentication_errors')
            ->setHandler([$this, 'checkAuthentication'])
            ->execute();
    }

    /**
     * Registers the API routes.
     *
     * @throws ComponentLoadFailedException|ComponentClassesNotDefinedException
     * @internal
     */
    public function registerRoutes() : void
    {
        $this->components = TypeHelper::arrayOf($this->loadComponents(), AbstractController::class);
    }

    /**
     * Checks whether the request should be authenticated or not.
     *
     * @see \WP_REST_Server::check_authentication()
     * @see \Automattic\WooCommerce\StoreApi\Authentication
     * @internal
     *
     * @param mixed $result
     * @return mixed
     */
    public function checkAuthentication($result)
    {
        if ($result !== null) {
            return $result;
        }

        if ($this->isNonceVerificationBasedRoute()) {
            return true;
        }

        return null;
    }

    /**
     * Determines whether the current route authentication is based on nonce verification.
     *
     * @return bool
     */
    protected function isNonceVerificationBasedRoute() : bool
    {
        if (empty($GLOBALS['wp']->query_vars['rest_route'])) {
            return false;
        }

        foreach ($this->components as $component) {
            if (! ArrayHelper::contains(class_uses($component), VerifiesNonceTrait::class)) {
                continue;
            }

            /*
             * This check is required because the trait is not guaranteed to be implementing the method and also phpstan
             * will not be able to detect it.
             */
            if (! method_exists($component, 'shouldAuthenticateRouteByNonceVerification')) {
                continue;
            }

            if ($component->shouldAuthenticateRouteByNonceVerification($GLOBALS['wp']->query_vars['rest_route'])) {
                return true;
            }
        }

        return false;
    }
}
