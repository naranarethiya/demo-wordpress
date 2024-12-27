<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;

class SecondCartRecoveryEmailContent extends CartRecoveryEmailContent
{
    /**
     * {@inheritDoc}
     */
    protected function getHeadingSettingObject() : EmailNotificationSetting
    {
        return parent::getHeadingSettingObject()->setDefault(__('Hey {{customer_first_name}}!', 'mwc-core'));
    }

    /**
     * {@inheritDoc}
     */
    protected function getContentSettingObject() : EmailNotificationSetting
    {
        return parent::getContentSettingObject()->setDefault(__('Your shopping cart has been reserved and is still waiting for you! In your cart, you left the following products:', 'mwc-core'));
    }

    /**
     * {@inheritDoc}
     */
    public function getAdditionalContentSettingObject() : EmailNotificationSetting
    {
        return parent::getAdditionalContentSettingObject()->setDefault(__("It's not too late to complete this purchase -- these products are still waiting for you!", 'mwc-core'));
    }
}
