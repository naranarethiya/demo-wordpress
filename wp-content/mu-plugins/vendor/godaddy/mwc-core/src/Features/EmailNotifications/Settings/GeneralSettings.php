<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Models\Control;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetEmailNotificationDataStoreTrait;
use GoDaddy\WordPress\MWC\Core\Settings\Models\SettingGroup;
use InvalidArgumentException;

/**
 * The general settings group.
 */
class GeneralSettings extends SettingGroup
{
    use CanGetEmailNotificationDataStoreTrait;

    /** @var string ID of the settings group */
    const GROUP_ID = 'email_notifications';

    /** @var string ID of the "Sender name" setting */
    const SETTING_ID_SENDER_NAME = 'sender_name';

    /** @var string ID of the "Sender address" setting */
    const SETTING_ID_SENDER_ADDRESS = 'sender_address';

    /** @var string ID of the delay value setting for delayable emails */
    const SETTING_ID_DELAY_VALUE = 'delay_value';

    /** @var string ID of the delay unit setting for delayable emails */
    const SETTING_ID_DELAY_UNIT = 'delay_unit';

    /**
     * GeneralSettings constructor.
     */
    public function __construct()
    {
        $this->id = $this->name = static::GROUP_ID;

        $this->label = __('Email Notifications', 'mwc-core');
    }

    /**
     * Gets the initial settings.
     *
     * @return EmailNotificationSetting[]
     */
    protected function getInitialSettings() : array
    {
        return [
            // "Sender name" setting
            (new EmailNotificationSetting())
                ->setId(static::SETTING_ID_SENDER_NAME)
                ->setName(static::SETTING_ID_SENDER_NAME)
                ->setLabel(__('Sender name', 'mwc-core'))
                ->setIsRequired(true)
                ->setType(EmailNotificationSetting::TYPE_STRING)
                ->setDefault(SiteRepository::getTitle())
                ->setControl((new Control())
                    ->setType(Control::TYPE_TEXT)
                ),

            // "Sender address" setting
            (new EmailNotificationSetting())
                ->setId(static::SETTING_ID_SENDER_ADDRESS)
                ->setName(static::SETTING_ID_SENDER_ADDRESS)
                ->setLabel(__('Sender address', 'mwc-core'))
                ->setIsRequired(true)
                ->setType(EmailNotificationSetting::TYPE_EMAIL)
                ->setDefault((string) get_option('admin_email'))
                ->setControl((new Control())
                    ->setType(Control::TYPE_EMAIL)
                ),
        ];
    }

    /**
     * Gets the initial settings subgroups, if any.
     *
     * @return ConfigurableContract[]
     */
    protected function getInitialSettingsSubgroups() : array
    {
        try {
            $emailNotifications = $this->getEmailNotificationDataStore()->all();
        } catch (Exception $exception) {
            if (! $exception instanceof SentryException) {
                SentryException::getNewInstance($exception->getMessage(), $exception);
            }

            return [];
        }

        return $this->convertEmailNotificationsIntoSettingsSubgroups($emailNotifications);
    }

    /**
     * Converts a list of {@see EmailNotificationContract} instances into a list of setting groups.
     *
     * @param EmailNotificationContract[] $emailNotifications
     * @return ConfigurableContract[]
     */
    protected function convertEmailNotificationsIntoSettingsSubgroups(array $emailNotifications) : array
    {
        $subgroups = [];

        foreach ($emailNotifications as $notification) {
            try {
                $subgroups[] = $this->convertEmailNotificationIntoSettingGroup($notification);
            } catch (InvalidArgumentException $exception) {
                continue;
            }
        }

        return $subgroups;
    }

    /**
     * Configures new SettingGroup instances.
     *
     * @param EmailNotificationContract $notification
     * @return ConfigurableContract
     * @throws InvalidArgumentException
     */
    protected function convertEmailNotificationIntoSettingGroup(EmailNotificationContract $notification) : ConfigurableContract
    {
        if (! $notification->getId()) {
            throw new InvalidArgumentException('The notification must have a non empty ID.');
        }

        $settingGroup = SettingGroup::getNewInstance();
        $settingGroup->setId($notification->getId())
            ->setName($notification->getName())
            ->setLabel($notification->getLabel());

        $settingGroup->setSettings($notification->getSettings());

        $subSettingGroup = SettingGroup::getNewInstance();
        $subSettingGroup->setId('content')
            ->setName('content')
            ->setLabel(__('Content', 'mwc-core'));

        if ($content = $notification->getContent()) {
            $subSettingGroup->setSettings($content->getSettings());
        }

        $settingGroup->addSettingsSubgroup($subSettingGroup);

        return $settingGroup;
    }

    /**
     * Gets the value or the default value of the sender name setting.
     *
     * Can also return null if there is a problem trying to retrieve the setting object.
     *
     * @return string|null
     */
    public function getSenderName()
    {
        return $this->getSettingValueOrDefault(static::SETTING_ID_SENDER_NAME);
    }

    /**
     * Gets the value or the default value of a setting.
     *
     * Can also return null if there is a problem trying to retrieve the setting object.
     *
     * @return mixed|null
     */
    protected function getSettingValueOrDefault(string $name)
    {
        try {
            $setting = $this->getSetting($name);
        } catch (InvalidArgumentException $exception) {
            return null;
        }

        return $setting->hasValue() ? $setting->getValue() : $setting->getDefault();
    }

    /**
     * Gets the value or the default value of the sender address setting.
     *
     * Can also return null if there is a problem trying to retrieve the setting object.
     *
     * @return string|null
     */
    public function getSenderAddress()
    {
        return $this->getSettingValueOrDefault(static::SETTING_ID_SENDER_ADDRESS);
    }
}
