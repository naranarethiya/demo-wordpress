<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\API as CommonAPI;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Email\Repositories\EmailServiceRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Auth\Providers\JwtAuthProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\EmailNotificationsController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\EmailTemplatesController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\EmailTemplatesSettingsController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\SendersController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\SettingsController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailsPage;
use GoDaddy\WordPress\MWC\Core\Vendor\Firebase\JWT\ExpiredException;
use WP_Error;
use WP_REST_Request;

/**
 * Email notifications API handler.
 */
class API extends CommonAPI
{
    use HasComponentsTrait;

    /** @var array */
    protected $componentClasses = [
        EmailNotificationsController::class,
        EmailTemplatesController::class,
        SendersController::class,
        SettingsController::class,
        EmailTemplatesSettingsController::class,
    ];

    /**
     * Determines if the current logged-in WP user has access to the module's REST endpoints.
     *
     * @return bool
     */
    public static function hasAPIAccess() : bool
    {
        return current_user_can(EmailsPage::CAPABILITY);
    }

    /**
     * Determines if an external service (e.g. the MWC Emails Service) has access to the module's REST endpoints.
     *
     * Validates a provided JWT token against a known JWK (retrieved from the MWC API).
     *
     * @param WP_REST_Request|null $request
     * @return bool|WP_Error
     * @throws Exception
     */
    public static function serviceHasAPIAccess(WP_REST_Request $request = null)
    {
        if (empty($request) || empty($token = $request->get_param('token'))) {
            return false;
        }

        try {
            $decoded = JwtAuthProvider::getNewInstance()->decodeToken($token);
        } catch (ExpiredException $exception) {
            return static::getRestResponseError('expired_token', __('This token is expired', 'mwc-core'), 401);
        } catch (Exception $exception) {
            return false;
        }

        // validate the site ID
        $siteId = EmailServiceRepository::getSiteId();

        foreach (array_chunk(explode('#', TypeHelper::string(ArrayHelper::get($decoded, 'sub'), '')), 2) as $pair) {
            if ('SITEID' === $pair[0] && ArrayHelper::get($pair, 1) === $siteId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a WordPress error object to be served as a REST response error.
     *
     * @TODO: extract to the AbstractController {dmagalhaes 2022-03-22}
     *
     * @param string $errorCode
     * @param string $errorMessage
     * @param int $statusCode
     * @return WP_Error
     */
    public static function getRestResponseError(string $errorCode, string $errorMessage, int $statusCode) : WP_Error
    {
        return new WP_Error($errorCode, $errorMessage, [
            'status' => $statusCode,
        ]);
    }
}
