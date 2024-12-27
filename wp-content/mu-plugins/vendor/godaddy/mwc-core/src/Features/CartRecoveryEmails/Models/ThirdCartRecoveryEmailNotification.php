<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;

class ThirdCartRecoveryEmailNotification extends CartRecoveryEmailNotification
{
    /**
     * @var int
     */
    protected $position = 3;

    /**
     * {@inheritdoc}
     */
    protected $defaultDelayValue = 2;

    /**
     * {@inheritdoc}
     */
    protected $defaultDelayUnit = 'day';

    /**
     * {@inheritDoc}
     */
    protected function getSubjectSettingObject() : EmailNotificationSetting
    {
        return parent::getSubjectSettingObject()->setDefault('Hurry, your cart will expire soon!');
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreviewTextSettingObject() : EmailNotificationSetting
    {
        return parent::getPreviewTextSettingObject()->setDefault("Don't miss out");
    }

    /** {@inheritDoc} */
    protected function isCheckoutStatusEligible(Checkout $checkout) : bool
    {
        return $checkout->isRecoverable() || $checkout->isPendingRecovery();
    }
}
