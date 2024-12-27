<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Events\Transformers;

use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Core\Events\SettingGroupEvent;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings\GeneralSettings;

/**
 * Transformer to add email notifications data to the corresponding SettingGroupEvent.
 */
class EmailNotificationsSettingsUpdatedEventTransformer extends AbstractEventTransformer
{
    /** @var string[] list of most commonly used free email providers */
    protected $freeEmailProviders = [
        // Google
        'gmail.com',
        // Microsoft
        'outlook.com',
        'live.com',
        'hotmail.com',
        // Apple iCloud
        'icloud.com',
        'privaterelay.appleid.com',
        // Yahoo
        'yahoo.com',
        'yahoo.co',
        // Zoho
        'zohomail.com',
        // GMX
        'gmx.net',
        'gmx.at',
        'gmx.ch',
        // AOL
        'aol.com',
        // ProtonMail
        'protonmail.com',
        // WebMail
        'webmail.co',
        // FastMail
        'fastmail.',
        // Tutanota
        'tutanota.com',
        // Firefox Relay
        'relay.firefox.com',
    ];

    /**
     * Determines if the given email address is from one of the free email providers.
     *
     * @param string $emailAddress
     * @return bool
     */
    protected function isFreeEmailAddress(string $emailAddress) : bool
    {
        // is the address same as site's?
        if (StringHelper::contains($emailAddress, SiteRepository::getDomain())) {
            return false;
        }

        // is the address from the commonly used email providers?
        if (StringHelper::contains($emailAddress, $this->freeEmailProviders)) {
            return true;
        }

        // so it is properly not free
        return false;
    }

    /**
     * Determines whether the event must be transformed or not.
     *
     * @param SettingGroupEvent|EventContract $event
     * @return bool
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof SettingGroupEvent && GeneralSettings::GROUP_ID === ArrayHelper::get($event->getData(), 'resource.id');
    }

    /**
     * Handles and perhaps modifies the event.
     *
     * @param SettingGroupEvent|EventContract $event the event, perhaps modified by the method
     */
    public function handle(EventContract $event)
    {
        $data = $event->getData();

        $emailAddressSetting = ArrayHelper::where(ArrayHelper::get($data, 'resource.settings'), function ($value) {
            return GeneralSettings::SETTING_ID_SENDER_ADDRESS === ArrayHelper::get($value, 'id');
        });

        /* @var $event SettingGroupEvent */
        if (! $emailAddress = ArrayHelper::get(current($emailAddressSetting), 'value')) {
            return;
        }

        ArrayHelper::set($data, 'address_type', $this->isFreeEmailAddress($emailAddress) ? 'free' : 'branded');

        $event->setData($data);
    }
}
