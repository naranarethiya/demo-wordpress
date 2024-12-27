<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;

class SecondCartRecoveryEmailNotification extends CartRecoveryEmailNotification
{
    /**
     * @var int
     */
    protected $position = 2;

    /**
     * {@inheritdoc}
     */
    protected $defaultDelayValue = 1;

    /**
     * {@inheritdoc}
     */
    protected $defaultDelayUnit = 'day';

    /**
     * {@inheritDoc}
     */
    protected function getSubjectSettingObject() : EmailNotificationSetting
    {
        return parent::getSubjectSettingObject()->setDefault("We're still holding the cart for you");
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreviewTextSettingObject() : EmailNotificationSetting
    {
        return parent::getPreviewTextSettingObject()->setDefault('All is not lost');
    }

    /** {@inheritDoc} */
    protected function isCheckoutStatusEligible(Checkout $checkout) : bool
    {
        return $checkout->isRecoverable() || $checkout->isPendingRecovery();
    }
}
