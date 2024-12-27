<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\API\Response;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Common\Traits\CanFormatRequestSettingValuesTrait;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailSenderTakenException;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailsServiceException;
use GoDaddy\WordPress\MWC\Core\Email\Http\EmailsServiceRequest;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations\SendEmailSenderMailboxVerificationMutation;
use GoDaddy\WordPress\MWC\Core\Email\Models\EmailSender;
use GoDaddy\WordPress\MWC\Core\Email\Repositories\EmailSenderRepository;
use GoDaddy\WordPress\MWC\Core\Email\Repositories\EmailServiceRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\API;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\RequestException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetEmailNotificationDataStoreTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetWooCommerceSettingsDataStoreTrait;
use GoDaddy\WordPress\MWC\Dashboard\Menu\GetHelpMenu;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API controller for email notifications.
 */
class SendersController extends AbstractController implements ComponentContract
{
    use CanGetEmailNotificationDataStoreTrait;
    use CanGetWooCommerceSettingsDataStoreTrait;
    use CanFormatRequestSettingValuesTrait;
    /** @var string */
    protected $route = 'email-notifications/senders';

    /**
     * Initializes the controller.
     */
    public function load()
    {
        $this->registerRoutes();
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     */
    public function registerRoutes()
    {
        $emailPattern = '(?P<email>[.\@\%a-zA-Z0-9_-]+)';

        register_rest_route($this->namespace, "/{$this->route}/{$emailPattern}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItem'],
                'permission_callback' => [$this, 'getItemPermissionsCheck'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'createItem'],
                'permission_callback' => [$this, 'createItemPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/{$emailPattern}/send-verification", [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'resendVerification'],
                'permission_callback' => [$this, 'resendVerificationPermissionsCheck'],
            ],
        ]);
    }

    /**
     * Sends the given request.
     *
     * @param AbstractGraphQLOperation $query
     * @return array|null|WP_Error
     * @throws Exception
     */
    protected function sendRequest(AbstractGraphQLOperation $query)
    {
        try {
            $response = EmailsServiceRequest::getNewInstance()
                ->setOperation($query)
                ->send();

            if ($response->isError()) {
                $errorStatus = max((int) $response->getStatus(), 400);

                throw new RequestException((string) $response->getErrorMessage(), $errorStatus);
            }

            return $response->getBody();
        } catch (Exception $exception) {
            $status = $exception->getCode() ?: 500;

            return $this->getWordPressError($status, $exception->getMessage(), [
                'status' => $status,
            ]);
        }
    }

    /**
     * Determines if the current user has permissions to issue requests to create items.
     *
     * @return bool
     */
    public function createItemPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
    }

    /**
     * Determines if the current user has permissions to issue requests to get items.
     *
     * @return bool
     */
    public function getItemPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
    }

    /**
     * Determines if the current user has permissions to issue requests to resend verifications.
     *
     * @return bool
     */
    public function resendVerificationPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
    }

    /**
     * Handle create new email sender request.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function createItem(WP_REST_Request $request)
    {
        try {
            $emailSender = EmailSender::create($request->get_param('email'));
        } catch (EmailSenderTakenException $exception) {
            return $this->getEmailSenderTakenErrorResponse($exception);
        } catch (EmailsServiceException $exception) {
            return $this->getWordPressError($exception->getCode(), $exception->getMessage());
        }

        return rest_ensure_response(['data' => ['emailSender' => $emailSender->toArray()]]);
    }

    /**
     * Gets an email notification.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function getItem(WP_REST_Request $request)
    {
        try {
            $emailSender = EmailSender::getOrFail($request->get_param('email'));
        } catch (EmailSenderTakenException $exception) {
            return $this->getEmailSenderTakenErrorResponse($exception);
        } catch (EmailsServiceException $exception) {
            return $this->getWordPressError($exception->getCode(), $exception->getMessage());
        }

        return rest_ensure_response(['data' => ['emailSender' => $emailSender->toArray()]]);
    }

    /**
     * Gets a {@see WP_REST_Response} object that represents the error described by the given exception.
     *
     * @param EmailSenderTakenException $exception
     * @return WP_REST_Response
     */
    protected function getEmailSenderTakenErrorResponse(EmailSenderTakenException $exception) : WP_REST_Response
    {
        return $this->getWordPressResponse($this->getErrorResponse(
            $exception->getCode(),
            sprintf(
                __('This email address belongs to another store. Please enter a different email address. If you already own this address, please reach out via %1$sGet Help%2$s.', 'mwc-core'),
                '<a href="'.admin_url('admin.php?page='.GetHelpMenu::MENU_SLUG).'">',
                '</a>'
            ),
            $exception->getErrorCode()
        ));
    }

    /**
     * Gets an Response object that represents the error described by the given exception.
     *
     * TODO: move this method into an AbstractController class in mwc-core {wvega 2022-02-17}
     *       https://jira.godaddy.com/browse/MWC-4487
     *
     * @param int $status
     * @param string $message
     * @param string $code
     * @return Response
     */
    protected function getErrorResponse(int $status, string $message, string $code) : Response
    {
        return (new Response())
            ->setStatus($status)
            ->setBody([
                'errors' => [
                    [
                        'message'    => $message,
                        'extensions' => [
                            'code' => $code,
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Converts the given {@see Response} into an instance of {@see WP_REST_Response}.
     *
     * TODO: move this method into an AbstractController class in mwc-core {wvega 2022-02-17}
     *       https://jira.godaddy.com/browse/MWC-4487
     *
     * @param Response $response
     * @return WP_REST_Response
     */
    protected function getWordPressResponse(Response $response) : WP_REST_Response
    {
        return new WP_REST_Response($response->getBody(), $response->getStatus());
    }

    /**
     * Gets WP Error instance with the given data.
     *
     * @param int|mixed $code
     * @param string $message
     * @param mixed $data
     * @return WP_Error
     */
    protected function getWordPressError($code, string $message, $data = '') : WP_Error
    {
        return new WP_Error($code, $message, $data);
    }

    /**
     * Sends request to resend email verification.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function resendVerification(WP_REST_Request $request)
    {
        $query = SendEmailSenderMailboxVerificationMutation::getNewInstance()->setVariables([
            'emailAddress'                   => urldecode(TypeHelper::string($request->get_param('email'), '')),
            'siteId'                         => EmailServiceRepository::getSiteId(),
            'mailboxVerificationRedirectUrl' => EmailSenderRepository::getMailboxVerificationRedirectUrl(),
        ]);

        return rest_ensure_response($this->sendRequest($query));
    }

    /**
     * Gets the schema for REST email notification sender items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'emailSender',
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'description' => __('Sender unique ID.', 'mwc-core'),
                    'type'        => 'integer',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'emailAddress' => [
                    'description' => __('Sender email address.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'verifiedAt' => [
                    'description' => __('The date and time when the sender was verified in our system.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'verifiedBy' => [
                    'description' => __('How the sender was verified.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'status' => [
                    'description' => __('Sender status.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
