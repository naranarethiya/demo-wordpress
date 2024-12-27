<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Url;
use GoDaddy\WordPress\MWC\Common\Http\Url\Exceptions\InvalidUrlException;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\TemplatesRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Settings\Traits\CanUpdateSettingsUsingRequestDataTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Email\DataSources\Adapters\ScheduledEmailAdapter;
use GoDaddy\WordPress\MWC\Core\Email\RenderableEmail;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Contracts\CheckoutEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataStores\WooCommerce\CheckoutDataStore;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\API;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\ConditionalEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DelayableEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataSources\WooCommerce\EmailNotificationAdapter;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailBuilder;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailPreviewBuilder;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Events\EmailNotificationSentEvent;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailNotificationNotAvailableException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailNotificationNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\HasEmailTemplateOverridesException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\MissingParameterException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\DefaultEmailContent;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings\GeneralSettings;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetEmailNotificationDataStoreTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetWooCommerceSettingsDataStoreTrait;
use GoDaddy\WordPress\MWC\Core\Settings\Models\SettingGroup;
use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API controller for email notifications.
 */
class EmailNotificationsController extends AbstractController implements ComponentContract
{
    use CanGetNewInstanceTrait;
    use CanGetEmailNotificationDataStoreTrait;
    use CanGetWooCommerceSettingsDataStoreTrait;
    use CanUpdateSettingsUsingRequestDataTrait;

    /** @var string */
    protected $route = 'email-notifications';

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
        $patternEmailNotificationId = '(?P<emailNotificationId>[a-zA-Z0-9_-]+)';

        register_rest_route($this->namespace, '/'.$this->route, [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItems'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'updateGeneralSettings'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/(?!categories$)(?!senders$){$patternEmailNotificationId}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItem'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'updateItem'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/{$patternEmailNotificationId}/preview", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItemPreview'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/{$patternEmailNotificationId}/data", [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'getItemData'],
                'permission_callback' => [$this, 'getItemDataPermissionsCheck'],
                'args'                => [
                    'email_address' => [
                        'required' => true,
                        'type'     => 'string',
                        'format'   => 'email',
                    ],
                ],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/{$patternEmailNotificationId}/reset", [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'resetItem'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/{$patternEmailNotificationId}/send", [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'sendItem'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/{$patternEmailNotificationId}/should-send", [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'shouldSendItem'],
                'permission_callback' => [$this, 'getItemDataPermissionsCheck'],
                'args'                => [
                    'email_address' => [
                        'required' => true,
                        'type'     => 'string',
                        'format'   => 'email',
                    ],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/'.$this->route.'/categories', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getCategories'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
        ]);
    }

    /**
     * Retrieves the full URL for a given endpoint.
     *
     * @param string $emailNotificationId ID of the notification.
     * @param string $path route path (e.g. "should-send" or "data").
     * @param array $queryArgs optional query args to append to the URL.
     * @return string
     */
    public function getRoute(string $emailNotificationId, string $path, array $queryArgs = []) : string
    {
        $route = SiteRepository::getRestUrl(WordPressRepository::getCurrentBlogId(), "{$this->namespace}/{$this->route}/{$emailNotificationId}/{$path}");

        try {
            return Url::fromString($route)->addQueryParameters($queryArgs);
        } catch (InvalidUrlException $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);

            return '';
        }
    }

    /**
     * Gets a list of email notifications.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getItems(WP_REST_Request $request)
    {
        try {
            $emailNotifications = $this->getEmailNotificationDataStore()->all();

            if ($query = $request->get_param('query')) {
                $query = json_decode($query, true);
                if ($categories = $this->getFilterCategories($query)) {
                    $emailNotifications = $this->filterItems($emailNotifications, $categories);
                }
            }

            $response = ['emailNotifications' => $this->prepareItems($emailNotifications)];
        } catch (BaseException $exception) {
            $response = new WP_Error($exception->getCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Determines if the current user has permissions to issue requests to get items.
     *
     * @return bool
     */
    public function getItemsPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
    }

    /**
     * Determines if the current user or external service has permissions to issue requests to get item data.
     *
     * @param WP_REST_Request|null $request
     * @return bool|WP_Error
     */
    public function getItemDataPermissionsCheck(WP_REST_Request $request = null)
    {
        if (API::hasAPIAccess()) {
            return true;
        } else {
            return API::serviceHasAPIAccess($request);
        }
    }

    /**
     * Gets an email notification.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getItem(WP_REST_Request $request)
    {
        try {
            $emailNotification = $this->getEmailNotificationDataStore()->read(SanitizationHelper::input($request->get_param('emailNotificationId')));

            $response = ['emailNotification' => $this->prepareItem($emailNotification)];
        } catch (BaseException $exception) {
            $response = new WP_Error($exception->getCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets the preview of an email notification.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getItemPreview(WP_REST_Request $request)
    {
        try {
            $emailNotification = $this->getEmailNotificationDataStore()->read(SanitizationHelper::input($request->get_param('emailNotificationId')));

            if (! $emailNotification->isEditable()) {
                throw new BaseException(__('Cannot generate a preview for an email notification that is not editable.', 'mwc-core'));
            }

            $preview = $this->getEmailPreviewBuilder($emailNotification)->build();

            $response = [
                'preview' => [
                    $preview->getBodyFormat() => $preview->getBody(),
                    'variables'               => $preview->getVariables(),
                ],
            ];
        } catch (BaseException $exception) {
            $response = new WP_Error($exception->getCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets the data for an email notification.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function getItemData(WP_REST_Request $request)
    {
        try {
            $emailNotification = $this->maybeAdjustEmailNotificationObjectForRequest(
                $this->getEmailNotificationDataStore()->read(SanitizationHelper::input($request->get_param('emailNotificationId'))),
                $request
            );

            $response = $this->buildItemData($this->getEmailBuilder($emailNotification)
                ->setRecipients(ArrayHelper::wrap(SanitizationHelper::input($request->get_param('email_address'))))
                ->build());
        } catch (Exception $exception) {
            $response = new WP_Error($exception->getCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Builds the email notification data.
     *
     * @param RenderableEmail $renderableEmail
     * @return array
     * @throws Exception
     */
    protected function buildItemData(RenderableEmail $renderableEmail) : array
    {
        return ScheduledEmailAdapter::getNewInstance($renderableEmail)->convertFromSource();
    }

    /**
     * Returns an email builder instance for the supplied email notification.
     *
     * @param EmailNotificationContract $emailNotification
     * @return EmailBuilder
     * @throws Exception
     */
    protected function getEmailBuilder(EmailNotificationContract $emailNotification) : EmailBuilder
    {
        return new EmailBuilder($emailNotification);
    }

    /**
     * Gets a list of email notification categories.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getCategories(WP_REST_Request $request)
    {
        $categories = [];

        foreach (EmailNotifications::getCategories() as $identifier => $label) {
            $categories[] = [
                'id'   => $identifier,
                'name' => $label,
            ];
        }

        return rest_ensure_response(['categories' => $categories]);
    }

    /**
     * Gets an instance of the email preview builder.
     *
     * @param EmailNotificationContract $emailNotification
     * @return EmailPreviewBuilder
     */
    protected function getEmailPreviewBuilder(EmailNotificationContract $emailNotification) : EmailPreviewBuilder
    {
        return new EmailPreviewBuilder($emailNotification);
    }

    /**
     * Gets an array of arrays with data representing the given email notification objects.
     *
     * @param EmailNotificationContract[] $emailNotifications email notification objects
     * @return array
     */
    protected function prepareItems(array $emailNotifications) : array
    {
        return array_map(function (EmailNotificationContract $emailNotification) {
            return $this->prepareItem($emailNotification);
        }, $emailNotifications);
    }

    /**
     * Gets an array with data representing the given email notification object.
     *
     * @param EmailNotificationContract $emailNotification email notification object
     * @return array
     */
    protected function prepareItem(EmailNotificationContract $emailNotification) : array
    {
        $isDelayable = false;
        $sendDelay = null;
        $sendDelayUnit = null;

        if ($emailNotification instanceof DelayableEmailNotificationContract) {
            $isDelayable = true;
            $sendDelay = $emailNotification->getDelayValue();
            $sendDelayUnit = $emailNotification->getDelayUnit();
        }

        return [
            'id'                    => $emailNotification->getId(),
            'name'                  => $emailNotification->getName(),
            'label'                 => $emailNotification->getLabel(),
            'description'           => $emailNotification->getDescription(),
            'template'              => $emailNotification->getTemplate() ? $emailNotification->getTemplate()->getId() : null,
            'categories'            => $emailNotification->getCategories(),
            'status'                => $emailNotification->isEnabled() ? 'enabled' : 'disabled',
            'isManual'              => $emailNotification->isManual(),
            'isSentToAdministrator' => $emailNotification->isSentToAdministrator(),
            'isEditable'            => $emailNotification->isEditable(),
            'isDelayable'           => $isDelayable,
            'sendDelay'             => $sendDelay,
            'sendDelayUnit'         => $sendDelayUnit,
            'placeholders'          => $emailNotification->getPlaceholders(),
            'legacySettingsUrl'     => EmailNotificationAdapter::getLegacySettingsUrl($emailNotification),
        ];
    }

    /**
     * Updates the general email notification settings.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function updateGeneralSettings(WP_REST_Request $request)
    {
        try {
            $settings = ArrayHelper::wrap($request->get_param('settings'));

            if (empty($settings)) {
                throw new MissingParameterException(__('The settings parameter is required.', 'mwc-core'));
            }

            $this->toggleFeatureEnabledSetting($settings);
            $this->updateGeneralSettingsGroup($settings);

            return rest_ensure_response(null);
        } catch (Exception $exception) {
            return $this->getSettingsUpdateError($exception->getMessage(), $exception->getCode() ?: 400);
        }
    }

    /**
     * Determines if the current user has permissions to issue update requests.
     *
     * @return bool
     */
    public function updateItemPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
    }

    /**
     * Updates and returns the general settings group.
     *
     * @param array $settings
     * @return GeneralSettings|ConfigurableContract
     * @throws InvalidArgumentException
     */
    protected function updateGeneralSettingsGroup(array $settings) : GeneralSettings
    {
        /* @var SettingGroup $settingsGroup */
        $settingsGroup = $this->getWooCommerceSettingsDataStore()->read(GeneralSettings::GROUP_ID);

        // update the sender address setting value if it comes through sanitized
        if ($senderAddress = SanitizationHelper::input(ArrayHelper::get($settings, 'sender_address', ''))) {
            $settingsGroup->updateSettingValue(GeneralSettings::SETTING_ID_SENDER_ADDRESS, $senderAddress);
        }

        // update the sender name setting value if it comes through sanitized
        if ($senderName = SanitizationHelper::input(ArrayHelper::get($settings, 'sender_name', ''))) {
            $settingsGroup->updateSettingValue(GeneralSettings::SETTING_ID_SENDER_NAME, $senderName);
        }

        $settingsGroup->update();

        return $this->getWooCommerceSettingsDataStore()->save($settingsGroup);
    }

    /**
     * Updates the feature to enabled or disabled.
     *
     * This is not an exposed setting, but the email notifications can be toggled with a PUT settings request containing an `enabled=yes|no` value
     *
     * @see EmailNotificationsController::updateGeneralSettings()
     *
     * @param array $settings
     * @throws Exception
     */
    protected function toggleFeatureEnabledSetting(array $settings)
    {
        $enable = ArrayHelper::get($settings, 'enabled', null);

        if (true === $enable) {
            EmailNotifications::enable();
        } elseif (false === $enable) {
            EmailNotifications::disable();
        }
    }

    /**
     * Sends an email notification.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function sendItem(WP_REST_Request $request)
    {
        try {
            $emailNotification = $this->getEmailNotificationDataStore()->read(SanitizationHelper::input($request->get_param('emailNotificationId')));
            $recipients = array_unique(ArrayHelper::wrap($request->get_param('to')));

            if (! $emailNotification->isEditable()) {
                throw new BaseException(__('Cannot send a test email for an email notification that is not editable.', 'mwc-core'));
            }

            try {
                // try to update the email notification settings using the submitted variables
                // this allows each email notification to apply custom formatting if necessary
                //
                // Example: adding <p> tags to the value of the additionalContent setting
                $this->updateEmailNotificationSettingsWithoutSaving(
                    $emailNotification,
                    $this->getVariablesForTestEmail($request)
                );
            } catch (InvalidArgumentException $exception) {
                // ignore any errors that occur trying to update the settings
                // we don't intend to save those changes
            }

            if ($request->get_param('isTest')) {
                $this->getEmailPreviewBuilder($emailNotification)->build()
                    ->setTo($recipients)
                    // reset delay so that test emails are sent immediately
                    ->setSendAt(null)
                    // reset conditions so that test emails are always sent
                    ->setConditions([])
                    ->send();

                $response = ['sent' => true];
            } else {
                $response = ['sent' => false];
            }
        } catch (EmailNotificationNotFoundException $exception) {
            $response = new WP_Error($exception->getCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        } catch (Exception $exception) {
            $response = new WP_Error($exception->getCode() ?: 400, $exception->getMessage(), [
                'status' => $exception->getCode() ?: 400,
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets information about if an email notification should be sent and when.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function shouldSendItem(WP_REST_Request $request)
    {
        try {
            $emailNotification = $this->getEmailNotificationDataStore()->read(SanitizationHelper::input($request->get_param('emailNotificationId')));
            $emailNotification = $this->maybeAdjustEmailNotificationObjectForRequest($emailNotification, $request);

            $response = [
                'sendNow' => true, // by default all email notifications should be sent immediately
                'retryAt' => null, // applies only to delayable email notifications
            ];

            $response = $this->handleConditionalEmailNotifications($emailNotification, $response);

            // if sendNow is false at this point, it means this is a conditional email, its conditions are not met,
            // and it should not be sent at all, so we should not set a retryAt value
            if ($response['sendNow']) {
                $response = $this->handleDelayableEmailNotifications($emailNotification, $response);
            }

            $this->mayBroadcastEmailNotificationSentEvent($response, $emailNotification);
        } catch (BaseException|Exception $exception) {
            $response = new WP_Error($exception->getCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Broadcast email notification sent event if email was sent right away.
     *
     * @param array<string, mixed> $response
     * @param EmailNotificationContract $emailNotification
     * @return void
     */
    protected function mayBroadcastEmailNotificationSentEvent(array $response, EmailNotificationContract $emailNotification) : void
    {
        if (true === ArrayHelper::get($response, 'sendNow', false)) {
            Events::broadcast(new EmailNotificationSentEvent($emailNotification));
        }
    }

    /**
     * Handles conditional email notifications.
     *
     * May set the sendNow property in the response.
     *
     * @param EmailNotificationContract $emailNotification
     * @param array $response
     * @return array
     */
    protected function handleConditionalEmailNotifications(EmailNotificationContract $emailNotification, array $response) : array
    {
        if (is_a($emailNotification, ConditionalEmailNotificationContract::class)) {
            $response['sendNow'] = $emailNotification->shouldSend();
        }

        return $response;
    }

    /**
     * Handles delayable email notifications.
     *
     * May set the retryAt property and override sendNow in the response.
     *
     * @param EmailNotificationContract $emailNotification
     * @param array $response
     * @return array
     */
    protected function handleDelayableEmailNotifications(EmailNotificationContract $emailNotification, array $response) : array
    {
        if (is_a($emailNotification, DelayableEmailNotificationContract::class)) {
            $sendAt = $emailNotification->sendAt();

            if ($sendAt) {
                $response['retryAt'] = $sendAt->getTimestamp();

                if ($sendAt->getTimestamp() > time()) {
                    $response['sendNow'] = false;
                }
            } else {
                $response['sendNow'] = false;
            }
        }

        return $response;
    }

    /**
     * Gets the variables that will be used to update the configuration of a test email.
     *
     * @param WP_REST_Request $request
     * @return array
     */
    protected function getVariablesForTestEmail(WP_REST_Request $request) : array
    {
        $variables = ArrayHelper::wrap($request->get_param('variables'));

        if ($subject = ArrayHelper::get($variables, 'subject')) {
            $variables['subject'] = "[TEST] {$subject}";
        }

        return $variables;
    }

    /**
     * Updates an email notification's settings.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function updateItem(WP_REST_Request $request)
    {
        try {
            if (! EmailNotifications::canSend() && ! empty(TemplatesRepository::getEmailTemplateOverrides())) {
                throw new HasEmailTemplateOverridesException(__("The site has email template overrides and hasn't enabled the Email Notifications feature.", 'mwc-core'));
            }

            /* @var EmailNotification $emailNotification */
            $emailNotification = $this->getEmailNotificationDataStore()->read(SanitizationHelper::input($request->get_param('emailNotificationId')));

            if ($settings = ArrayHelper::wrap($request->get_param('settings'))) {
                $this->updateEmailNotificationSettings($emailNotification, $settings);
            }

            if ($emailNotification instanceof DelayableEmailNotificationContract) {
                $this->maybeUpdateDelayableSettings($emailNotification, $request);
            }

            $this->getEmailNotificationDataStore()->save($emailNotification);

            $response = ['emailNotification' => $this->prepareItem($emailNotification)];

            Events::broadcast(new ModelEvent($emailNotification, 'email-notification', 'update'));

            // TODO: combine these to catch statements when PHP 7.1 is required {@cwiseman 2021-09-14}
        } catch (HasEmailTemplateOverridesException $exception) {
            $response = $this->getSettingsUpdateError($exception->getMessage(), $exception->getCode());
        } catch (EmailNotificationNotFoundException $exception) {
            $response = $this->getSettingsUpdateError($exception->getMessage(), $exception->getCode());
        } catch (InvalidArgumentException $exception) {
            $response = $this->getSettingsUpdateError($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Exception $exception) {
            $response = new WP_Error(400, $exception->getMessage(), [
                'status' => 400,
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Resets an email notification's settings to defaults.
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     * @throws Exception
     */
    public function resetItem(WP_REST_Request $request)
    {
        try {
            $emailNotification = $this->getEmailNotificationDataStore()->read(SanitizationHelper::input($request->get_param('emailNotificationId')));

            // update email notification settings to defaults
            // TODO: add support for subgroups (right now only top level settings are reset) {dmagalhaes 2021-10-08}
            foreach ($emailNotification->getSettings() as $setting) {
                // we can call updateSettingValue() even if the default is null: that would clear the value automatically
                $emailNotification->updateSettingValue($setting->getId(), $setting->getDefault());
            }

            // content settings currently don't define defaults so let's clear the value and
            // let the EmailNotificationAdapter load defaults from WooCommerce
            if ($emailContent = $emailNotification->getContent()) {
                foreach ($emailContent->getSettings() as $setting) {
                    $setting->clearValue();
                }
            }

            $this->getEmailNotificationDataStore()->save($emailNotification);

            // return empty response if successful
            $response = null;
        } catch (EmailNotificationNotFoundException|EmailNotificationNotAvailableException $exception) {
            $response = $this->getSettingsUpdateError($exception->getMessage(), $exception->getCode());
        } catch (InvalidArgumentException $exception) {
            $response = $this->getSettingsUpdateError($exception->getMessage(), $exception->getCode() ?: 400);
        }

        return rest_ensure_response($response);
    }

    /**
     * Updates the given email notification's settings with the given values.
     *
     * @param EmailNotificationContract $emailNotification
     * @param array $settings
     * @return EmailNotificationContract
     * @throws InvalidArgumentException
     */
    protected function updateEmailNotificationSettings(EmailNotificationContract $emailNotification, array $settings) : EmailNotificationContract
    {
        // TODO: needs to be replaced with better flexible sanitization approach {nmolham 2021-10-15}
        $recipients = ArrayHelper::get($settings, 'recipients');
        if ($recipients && is_string($recipients)) {
            ArrayHelper::set($settings, 'recipients', array_map('trim', explode(',', $recipients)));
        }

        $this->updateEmailNotificationSettingsWithoutSaving($emailNotification, $settings);

        return $emailNotification;
    }

    /**
     * Updates the given email notification's delayable settings.
     *
     * @param DelayableEmailNotificationContract $emailNotification
     * @param WP_REST_Request $request
     * @return void
     */
    protected function maybeUpdateDelayableSettings(DelayableEmailNotificationContract $emailNotification, WP_REST_Request $request)
    {
        if ($value = $request->get_param('sendDelay')) {
            $emailNotification->setDelayValue((int) $value);
        }

        if ($unit = $request->get_param('sendDelayUnit')) {
            $emailNotification->setDelayUnit(SanitizationHelper::input($unit));
        }
    }

    /**
     * Updates the given email notification's settings with the given values.
     *
     * @param EmailNotificationContract $emailNotification
     * @param array $settings
     * @throws InvalidArgumentException
     */
    protected function updateEmailNotificationSettingsWithoutSaving(EmailNotificationContract $emailNotification, array $settings)
    {
        foreach ($settings as $settingId => $settingValue) {
            $this->updateEmailNotificationSetting($emailNotification, SanitizationHelper::input($settingId), $settingValue);
        }
    }

    /**
     * Updates the given notification's setting with the given value.
     *
     * This loops through the given settings and first attempts to update a setting on the notification object for each
     * key. If the notification object does not have a matching setting, then the same is attempted on the content object.
     * If neither have a matching setting, the InvalidArgumentException is allowed through.
     *
     * @param EmailNotificationContract $emailNotification
     * @param string $settingId
     * @param mixed $settingValue
     * @return EmailNotificationContract
     * @throws InvalidArgumentException
     */
    protected function updateEmailNotificationSetting(EmailNotificationContract $emailNotification, string $settingId, $settingValue) : EmailNotificationContract
    {
        try {
            // first check the content object for a matching setting and bail early if found, after updating
            if ($content = $emailNotification->getContent()) {
                $this->updateSettingValue($content, $settingId, $settingValue);

                return $emailNotification;
            }
        } catch (InvalidArgumentException $e) {
            // ignore the exception to try to update the setting in the email notification object
        }

        // try and update the main notification's setting
        $this->updateSettingValue($emailNotification, $settingId, $settingValue);

        return $emailNotification;
    }

    /**
     * Determines whether the given configurable has a setting with the given setting ID.
     *
     * @param ConfigurableContract $configurable
     * @param string $settingId
     * @return bool
     */
    protected function configurableHasSetting(ConfigurableContract $configurable, string $settingId) : bool
    {
        foreach ($configurable->getSettings() as $setting) {
            if ($settingId === $setting->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Maybe adjusts the email notification object based on request parameters.
     *
     * This is used, for example, to retrieve the latest checkout object for a given email address specified in the request.
     *
     * @TODO perhaps we need a better way to handle this {unfulvio 2022-03-09}
     *
     * @param EmailNotificationContract $emailNotification
     * @param WP_REST_Request $request
     * @return EmailNotificationContract
     * @throws Exception
     */
    protected function maybeAdjustEmailNotificationObjectForRequest(EmailNotificationContract $emailNotification, WP_REST_Request $request) : EmailNotificationContract
    {
        if ($emailNotification instanceof CheckoutEmailNotificationContract) {
            $checkout = CheckoutDataStore::getNewInstance()->findLatestByEmailAddress($request->get_param('email_address') ?: '');

            if ($checkout) {
                $emailNotification->setCheckout($checkout);
            }
        }

        return $emailNotification;
    }

    /**
     * Filters the categories.
     *
     * @internal
     *
     * @param EmailNotificationContract[] $emailNotifications email notification objects
     * @param array $categories
     * @return array $filteredEmailNotifications
     */
    private function filterItems(array $emailNotifications, array $categories) : array
    {
        $filteredEmailNotifications = [];
        foreach ($emailNotifications as $emailNotification) {
            if (count(array_intersect(ArrayHelper::wrap($emailNotification->getCategories()), $categories)) > 0) {
                $filteredEmailNotifications[] = $emailNotification;
            }
        }

        return $filteredEmailNotifications;
    }

    /**
     * Gets the categories from the filters' parameter.
     *
     * @param array|null $query
     * @return array $categories
     */
    private function getFilterCategories(array $query = null) : array
    {
        return ArrayHelper::get($query, 'filters.categories.in', []);
    }

    /**
     * Gets the WordPress response error to return when there is a failed settings update.
     *
     * @param string $message
     * @param int $statusCode
     * @return WP_Error
     */
    protected function getSettingsUpdateError(string $message, int $statusCode) : WP_Error
    {
        return new WP_Error('mwc_core_email_notifications_update_settings_error', $message, [
            'status' => $statusCode,
        ]);
    }

    /**
     * Gets the schema for REST email notification items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'emailNotification',
            'type'       => 'object',
            'properties' => [
                'id' => [
                    'description' => __('Unique email notification ID.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'name' => [
                    'description' => __('Unique email notification name (matches the ID).', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'label' => [
                    'description' => __('Email notification label.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'description' => [
                    'description' => __('Email notification description.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'template' => [
                    'description' => __('ID of the template used by this email notification.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'categories' => [
                    'description' => __('A list of categories that the email notification belongs to.', 'mwc-core'),
                    'type'        => 'array',
                    'items'       => [
                        'type' => 'string',
                    ],
                    'context'  => ['view', 'edit'],
                    'readonly' => true,
                ],
                'status' => [
                    'description' => __('Email notification status', 'mwc-core'),
                    'type'        => 'string',
                    'enum'        => ['enabled', 'disabled'],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isManual' => [
                    'description' => __('Whether the email notification can only be sent manually.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isSentToAdministrator' => [
                    'description' => __('Whether the email notification will be sent to administrators.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'placeholders' => [
                    'description' => __('A list of placeholders that are available for the email notification.', 'mwc-core'),
                    'type'        => 'array',
                    'items'       => [
                        'type' => 'string',
                    ],
                    'context'  => ['view', 'edit'],
                    'readonly' => true,
                ],
                'legacySettingsUrl' => [
                    'description' => __('URL for the WooCommerce email settings screen for the email notification', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }

    /**
     * Gets the schema for REST email notification categories provided by the controller.
     *
     * @return array
     */
    public function getCategoriesSchema() : array
    {
        return [
            [
                '$schema'    => 'http://json-schema.org/draft-04/schema#',
                'title'      => 'categories',
                'type'       => 'object',
                'properties' => [
                    'id' => [
                        'description' => __('Unique email notification category ID.', 'mwc-core'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'name' => [
                        'description' => __('Email notification category name.', 'mwc-core'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * Gets a list of HTML tags allowed in the value of the given setting.
     *
     * @param SettingContract $setting
     * @return array
     */
    protected function getAllowedHtmlTagsForSetting(SettingContract $setting) : array
    {
        if ($setting->getName() !== DefaultEmailContent::SETTING_ID_ADDITIONAL_CONTENT) {
            return [];
        }

        return [
            'a' => [
                'href'  => true,
                'style' => true,
            ],
            'b' => [
                'style' => true,
            ],
            'br' => [
                'style' => true,
            ],
            'del' => [
                'style' => true,
            ],
            'em' => [
                'style' => true,
            ],
            'ins' => [
                'style' => true,
            ],
            'ol' => [
                'style' => true,
            ],
            's' => [
                'style' => true,
            ],
            'small' => [
                'style' => true,
            ],
            'strike' => [
                'style' => true,
            ],
            'strong' => [
                'style' => true,
            ],
            'sub' => [
                'style' => true,
            ],
            'sup' => [
                'style' => true,
            ],
            'u' => [
                'style' => true,
            ],
            'ul' => [
                'style' => true,
            ],
        ];
    }
}
