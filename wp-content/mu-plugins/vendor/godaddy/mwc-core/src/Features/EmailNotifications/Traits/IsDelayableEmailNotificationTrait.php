<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Models\Control;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DelayableEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings\GeneralSettings;
use InvalidArgumentException;

/**
 * A trait for email notifications that can be delayed.
 *
 * @see DelayableEmailNotificationContract
 */
trait IsDelayableEmailNotificationTrait
{
    /** @var int */
    protected $defaultDelayValue = 1;

    /** @var string */
    protected $defaultDelayUnit = 'hour';

    /**
     * Gets a setting's value.
     *
     * @param string $name
     * @return int|float|string|bool|array
     * @throws InvalidArgumentException
     */
    abstract public function getSettingValue(string $name);

    /**
     * Updates a setting's value.
     *
     * Will validate a value to be set against the setting type and any options, if set.
     *
     * @param string $name
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    abstract public function updateSettingValue(string $name, $value);

    /**
     * Get the amount of units that a notification should be delayed by.
     *
     * @return int
     * @throws InvalidArgumentException
     */
    public function getDelayValue() : int
    {
        $value = $this->getSettingValue(GeneralSettings::SETTING_ID_DELAY_VALUE);

        return is_numeric($value) ? (int) $value : $this->defaultDelayValue;
    }

    /**
     * Get the units relating to the value above that a notification should be delayed by.
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public function getDelayUnit() : string
    {
        $value = StringHelper::ensureScalar($this->getSettingValue(GeneralSettings::SETTING_ID_DELAY_UNIT));

        return $value ? (string) $value : $this->defaultDelayUnit;
    }

    /**
     * Sets the amount of units that a notification should be delayed by.
     *
     * @param int $value
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setDelayValue(int $value)
    {
        $this->updateSettingValue(GeneralSettings::SETTING_ID_DELAY_VALUE, $value);

        return $this;
    }

    /**
     * Sets the unit relating to the value above that a notification should be delayed by.
     *
     * @param string $value
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setDelayUnit(string $value)
    {
        $this->updateSettingValue(GeneralSettings::SETTING_ID_DELAY_UNIT, $value);

        return $this;
    }

    /**
     * Gets a delay value setting object for the email notification.
     *
     * @return EmailNotificationSetting
     * @throws InvalidArgumentException
     */
    protected function getDelayValueSettingObject() : EmailNotificationSetting
    {
        return (new EmailNotificationSetting())
            ->setId(GeneralSettings::SETTING_ID_DELAY_VALUE)
            ->setName(GeneralSettings::SETTING_ID_DELAY_VALUE)
            ->setType(EmailNotificationSetting::TYPE_INTEGER)
            ->setDefault($this->defaultDelayValue)
            ->setControl(new Control());
    }

    /**
     * Gets a delay unit setting object for the email notification.
     *
     * @return EmailNotificationSetting
     * @throws InvalidArgumentException
     */
    protected function getDelayUnitSettingObject() : EmailNotificationSetting
    {
        return (new EmailNotificationSetting())
            ->setId(GeneralSettings::SETTING_ID_DELAY_UNIT)
            ->setName(GeneralSettings::SETTING_ID_DELAY_UNIT)
            ->setType(EmailNotificationSetting::TYPE_STRING)
            ->setDefault($this->defaultDelayUnit)
            ->setControl(new Control());
    }
}
