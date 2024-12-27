<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;

class ThirdCartRecoveryEmailContent extends CartRecoveryEmailContent
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
        return parent::getContentSettingObject()->setDefault(__('Your shopping cart was reserved a few days ago and it will expire soon. In your cart, you left the following products:', 'mwc-core'));
    }

    /**
     * {@inheritDoc}
     */
    public function getAdditionalContentSettingObject() : EmailNotificationSetting
    {
        return parent::getAdditionalContentSettingObject()->setDefault(__('Ready to complete this purchase before your cart expires? Click the above button to purchase these items.', 'mwc-core'));
    }
}
