<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models;

use GoDaddy\WordPress\MWC\Common\Settings\Models\AbstractSetting;
use GoDaddy\WordPress\MWC\Common\Settings\Models\Control;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\DefaultEmailContent;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;
use InvalidArgumentException;

class CartRecoveryEmailContent extends DefaultEmailContent
{
    /** @var string */
    public const SETTING_ID_CONTENT = 'content';

    /** @var string */
    public const SETTING_ID_BUTTON_TEXT = 'buttonText';

    /**
     * Gets the initial settings.
     *
     * @return EmailNotificationSetting[]
     * @throws InvalidArgumentException
     */
    public function getInitialSettings() : array
    {
        return [
            $this->getHeadingSettingObject(),
            $this->getContentSettingObject(),
            $this->getButtonTextSettingObject(),
            $this->getAdditionalContentSettingObject(),
        ];
    }

    /**
     * {@inheritDoc}
     * @throws InvalidArgumentException
     */
    protected function getHeadingSettingObject() : EmailNotificationSetting
    {
        return parent::getHeadingSettingObject()
            ->setIsRequired(true)
            ->setDefault(__('Did you forget something?', 'mwc-core'));
    }

    /**
     * Gets content setting object.
     *
     * @return EmailNotificationSetting
     * @throws InvalidArgumentException
     */
    protected function getContentSettingObject() : EmailNotificationSetting
    {
        return (new EmailNotificationSetting())
            ->setId(static::SETTING_ID_CONTENT)
            ->setName(static::SETTING_ID_CONTENT)
            ->setLabel(__('Content', 'mwc-core'))
            ->setType(AbstractSetting::TYPE_STRING)
            ->setControl((new Control())
                ->setType(Control::TYPE_TEXTAREA)
            )
            ->setDefault(__("Hey there! You left your shopping cart without completing your purchase just a bit ago. It's not too late to complete your purchase! All of the products are still waiting for you.", 'mwc-core'));
    }

    /**
     * Gets button text setting object.
     *
     * @return EmailNotificationSetting
     * @throws InvalidArgumentException
     */
    protected function getButtonTextSettingObject() : EmailNotificationSetting
    {
        return (new EmailNotificationSetting())
            ->setId(static::SETTING_ID_BUTTON_TEXT)
            ->setName(static::SETTING_ID_BUTTON_TEXT)
            ->setLabel(__('Button text', 'mwc-core'))
            ->setType(AbstractSetting::TYPE_STRING)
            ->setControl((new Control())
                ->setType(Control::TYPE_TEXT)
            )
            ->setIsRequired(true)
            ->setDefault(__('Complete your purchase', 'mwc-core'));
    }

    /**
     * {@inheritDoc}
     * @throws InvalidArgumentException
     */
    public function getAdditionalContentSettingObject() : EmailNotificationSetting
    {
        return parent::getAdditionalContentSettingObject()
            ->setDefault(__("Trouble checking out? We're here to help. Please reply to this email if you have any questions or need our assistance.", 'mwc-core'));
    }
}
