<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\TemplatesRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\API;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce\SettingsDataStore;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Integrations\EmailCustomizerIntegration;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Interceptors\RedirectToNewEmailNotificationPage;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings\GeneralSettings;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\WooCommerce\Admin\EmailSettings;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\WooCommerce\EmailCatcher as WooCommerceEmailCatcher;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\WooCommerce\Roles;

/**
 * Email notifications feature loader.
 */
class EmailNotifications extends AbstractFeature
{
    use HasComponentsTrait;
    /** @var string extensions category name */
    const CATEGORY_ADMIN = 'admin';

    /** @var string extensions category name */
    const CATEGORY_ORDER = 'order';

    /** @var string extensions category name */
    const CATEGORY_CUSTOMER = 'customer';

    /** @var string extensions category name */
    const CATEGORY_EXTENSION = 'extensions';

    /** @var array alphabetically ordered list of components to load */
    protected $componentClasses = [
        API::class,
        EmailCatcher::class,
        EmailCustomizerIntegration::class,
        EmailsPage::class,
        EmailSettings::class,
        RedirectToNewEmailNotificationPage::class,
        Roles::class,
        WooCommerceEmailCatcher::class,
    ];

    /**
     * Gets the feature name, matching the key used in configuration.
     *
     * @return string
     */
    public static function getName() : string
    {
        return 'email_notifications';
    }

    /**
     * Determines whether the feature is active.
     *
     * @deprecated
     *
     * @return bool
     * @throws Exception
     */
    public static function isActive() : bool
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '2.24.1', __CLASS__.'::shouldLoad');

        return static::shouldLoad();
    }

    /**
     * Determines whether we can send the new email notifications instead of WooCommerce standard emails.
     *
     * @return bool
     */
    public static function canSend() : bool
    {
        return parent::shouldLoad() && Configuration::get('email_notifications.enabled', false);
    }

    /**
     * Initializes the feature.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->loadComponents();

        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'maybeToggleFeatureEnabled'])
            ->execute();
    }

    /**
     * Gets the value or the default value of the sender name setting.
     *
     * @return string|null
     */
    public static function getSenderName()
    {
        if (! $generalSettings = static::getGeneralSettings()) {
            return null;
        }

        return $generalSettings->getSenderName();
    }

    /**
     * Gets an instance of the GeneralSettings class from the data store.
     *
     * @return GeneralSettings|null
     */
    public static function getGeneralSettings()
    {
        try {
            return SettingsDataStore::getNewInstance()->read(GeneralSettings::GROUP_ID);
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * Gets the value or the default value of the sender address setting.
     *
     * @return string|null
     */
    public static function getSenderAddress()
    {
        if (! $generalSettings = static::getGeneralSettings()) {
            return null;
        }

        return $generalSettings->getSenderAddress();
    }

    /**
     * Gets the available email notifications categories.
     *
     * @return array associative array of slug identifiers and translatable labels
     */
    public static function getCategories() : array
    {
        return [
            static::CATEGORY_ADMIN     => __('Admin', 'mwc-core'),
            static::CATEGORY_EXTENSION => __('Extensions', 'mwc-core'),
            static::CATEGORY_CUSTOMER  => __('Customer', 'mwc-core'),
            static::CATEGORY_ORDER     => __('Order', 'mwc-core'),
        ];
    }

    /**
     * Determines whether the feature can be loaded automatically.
     *
     * @return bool
     * @throws Exception
     */
    protected function shouldEnableFeatureAutomatically() : bool
    {
        return static::shouldLoad() && empty(TemplatesRepository::getEmailTemplateOverrides());
    }

    /**
     * Toggles the Email Notifications feature to on if conditions are met.
     *
     * @throws Exception
     */
    public function maybeToggleFeatureEnabled()
    {
        if (! static::shouldLoad() || get_option('mwc_email_notifications_enabled', null) !== null) {
            return;
        }

        if ($this->shouldEnableFeatureAutomatically()) {
            static::enable();
        } else {
            static::disable();
        }
    }

    /**
     * Enables the feature.
     *
     * @throws Exception
     */
    public static function enable()
    {
        update_option('mwc_email_notifications_enabled', 'yes');

        Configuration::set('email_notifications.enabled', true);
    }

    /**
     * Disables the feature.
     *
     * @throws Exception
     */
    public static function disable()
    {
        update_option('mwc_email_notifications_enabled', 'no');

        Configuration::set('email_notifications.enabled', false);
    }
}
