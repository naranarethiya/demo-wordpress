<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\RequiresAdministratorPermissionsTrait;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\SupportRequestFailedException;
use GoDaddy\WordPress\MWC\Dashboard\Menu\GetHelpMenu;
use GoDaddy\WordPress\MWC\Dashboard\Support\SupportRequest;
use GoDaddy\WordPress\MWC\Dashboard\Support\SupportUser;
use WP_REST_Request;

/**
 * SupportController controller class.
 */
class SupportController extends AbstractController
{
    use RequiresAdministratorPermissionsTrait;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->route = 'support-requests';
    }

    /**
     * Registers the API route for the support endpoint.
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'POST', // WP_REST_Server::CREATABLE
                'callback'            => [$this, 'createItem'],
                'permission_callback' => [$this, 'createItemPermissionsCheck'],
                'args'                => $this->getItemSchema(),
            ],
            'schema' => [$this, 'getItemSchema'],
        ]);
    }

    /**
     * Checks if the current user can update items through the controller.
     *
     * @return bool|\WP_Error
     * @throws Exception
     */
    public function createItemPermissionsCheck()
    {
        return current_user_can(GetHelpMenu::CAPABILITY) && GetHelpMenu::shouldLoadConditionalFeature();
    }

    /**
     * Gets the schema.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            'replyTo' => [
                'required'    => true,
                'description' => __('The e-mail address the support team will reply to', 'mwc-dashboard'),
                'type'        => 'string',
                'context'     => ['view', 'edit'],
            ],
            'plugin' => [
                'description' => __('The plugin slug', 'mwc-dashboard'),
                'type'        => ['null', 'string'],
                'context'     => ['view', 'edit'],
            ],
            'subject' => [
                'required'    => true,
                'description' => __('The subject', 'mwc-dashboard'),
                'type'        => 'string',
                'context'     => ['view', 'edit'],
            ],
            'message' => [
                'required'    => true,
                'description' => __('The message', 'mwc-dashboard'),
                'type'        => 'string',
                'context'     => ['view', 'edit'],
            ],
            'reason' => [
                'required'    => true,
                'description' => __('The reason field', 'mwc-dashboard'),
                'type'        => 'string',
                'context'     => ['view', 'edit'],
            ],
            'affectedChannels' => [
                'required'    => false,
                'description' => __('The affected marketplace channels', 'mwc-dashboard'),
                'type'        => 'string',
                'context'     => ['view', 'edit'],
            ],
            'affectedSkus' => [
                'required'    => false,
                'description' => __('The affected marketplace channels', 'mwc-dashboard'),
                'type'        => 'string',
                'context'     => ['view', 'edit'],
            ],
            'createDebugUser' => [
                'description' => __('Whether or not to create a debug user', 'mwc-dashboard'),
                'type'        => 'bool',
                'context'     => ['view', 'edit'],
            ],
        ];
    }

    /**
     * Creates an item.
     *
     * @param WP_REST_Request $request full details about the request
     *
     * @return void
     * @throws Exception
     */
    public function createItem(WP_REST_Request $request) : void
    {
        $parameters = $request->get_params();
        $pluginSlug = SanitizationHelper::input(ArrayHelper::getStringValueForKey($parameters, 'plugin'));
        $affectedChannels = SanitizationHelper::input(ArrayHelper::getStringValueForKey($parameters, 'affectedChannels'));
        $affectedSkus = SanitizationHelper::input(ArrayHelper::getStringValueForKey($parameters, 'affectedSkus'));

        if ($createDebugUser = ArrayHelper::get($parameters, 'createDebugUser', false)) {
            $debugUser = SupportUser::create();
        }

        try {
            $supportRequest = (new SupportRequest)
                ->setFrom(SanitizationHelper::input(ArrayHelper::getStringValueForKey($parameters, 'replyTo')))
                ->setMessage(StringHelper::unslash(SanitizationHelper::input(ArrayHelper::getStringValueForKey($parameters, 'message'), true)))
                ->setReason(SanitizationHelper::input(ArrayHelper::getStringValueForKey($parameters, 'reason')))
                ->setSubject(SanitizationHelper::input(ArrayHelper::getStringValueForKey($parameters, 'subject')))
                ->setSubjectExtension($pluginSlug)
                ->setAffectedChannels($affectedChannels)
                ->setAffectedSkus($affectedSkus);

            $supportRequest->save();

            $supportRequest->send();
        } catch (SupportRequestFailedException $exception) {
            (new Response)
                ->error([$exception->getMessage()], 500)
                ->send();
        }

        (new Response)
            ->setBody([
                'reason'           => $supportRequest->getReason(),
                'replyTo'          => $supportRequest->getFrom(),
                'plugin'           => $pluginSlug,
                'subject'          => $supportRequest->getSubject(),
                'message'          => $supportRequest->getMessage(),
                'affectedChannels' => $affectedChannels,
                'affectedSkus'     => $affectedSkus,
                'createDebugUser'  => $createDebugUser,
                'debugUserId'      => $createDebugUser ? $debugUser->ID : '',
            ])
            ->success(200)
            ->send();
    }
}
