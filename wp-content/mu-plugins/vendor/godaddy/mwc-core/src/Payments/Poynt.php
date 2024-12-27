<?php

namespace GoDaddy\WordPress\MWC\Core\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;
use GoDaddy\WordPress\MWC\Core\Payments\Models\StoreDevice;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Cache\CacheBusinessResponse;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\PoyntStoreDeviceFirstActivatedEvent;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\BusinessGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\StoreDeviceAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\StoreDevicesRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Business;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class Poynt
{
    /** @var array */
    const IN_PERSON_SHIPPING_METHOD_IDS = ['local_pickup', 'local_pickup_plus', 'mwc_local_delivery'];

    /**
     * Determines if Poynt is enabled.
     *
     * @return bool
     */
    public static function isEnabled() : bool
    {
        return (bool) Configuration::get('payments.poynt.enabled', false);
    }

    /**
     * Gets the configured app ID.
     *
     * @return string
     * @throws Exception
     */
    public static function getAppId() : string
    {
        return (string) Configuration::get('payments.poynt.appId', '');
    }

    /**
     * Gets the configured application ID.
     *
     * Note: this represents the merchant's application to process payments, not the developer app ID for API
     * communication.
     *
     * @return string
     * @throws Exception
     */
    public static function getApplicationId() : string
    {
        return (string) Configuration::get('payments.poynt.applicationId', '');
    }

    /**
     * Gets the configured business ID.
     *
     * @return string
     * @throws Exception
     */
    public static function getBusinessId() : string
    {
        return (string) Configuration::get('payments.poynt.businessId', '');
    }

    /**
     * Gets the configured business created at timestamp.
     *
     * This is stored in GMT.
     *
     * @return int
     */
    public static function getBusinessCreatedAt() : int
    {
        return (int) Configuration::get('payments.poynt.businessCreatedAt', 0);
    }

    /**
     * Attempts to get the Business from cache, otherwise gets from API and sets cache before returning.
     *
     * @return Business
     * @throws Exception
     */
    public static function getBusiness() : Business
    {
        if (! $business = CacheBusinessResponse::getInstance()->get()) {
            $business = BusinessGateway::getNewInstance()->get();
            CacheBusinessResponse::getInstance()->set($business);
        }

        return $business;
    }

    /**
     * Gets the GoDaddy Payments Hub URL.
     *
     * @return string
     * @throws Exception
     */
    public static function getHubUrl() : string
    {
        if (Worldpay::shouldLoad()) {
            return Configuration::get('features.worldpay.hqUrl', '');
        }

        return (string) ManagedWooCommerceRepository::isProductionEnvironment() ? Configuration::get('payments.poynt.hub.productionUrl', '') : Configuration::get('payments.poynt.hub.stagingUrl', '');
    }

    /**
     * Checks if GoDaddy Payments is connected.
     *
     * @return bool
     * @throws Exception
     */
    public static function isConnected() : bool
    {
        return
        (bool) Onboarding::canEnablePaymentGateway(Onboarding::getStatus())
        && Poynt::getAppId()
        && Poynt::getBusinessId()
        && Poynt::getPrivateKey();
    }

    /**
     * Gets the GoDaddy Payments private key.
     *
     * @return string
     * @throws Exception
     */
    public static function getPrivateKey() : string
    {
        return (string) Configuration::get('payments.poynt.privateKey', '');
    }

    /**
     * Gets the GoDaddy Payments public key.
     *
     * @return string
     * @throws Exception
     */
    public static function getPublicKey() : string
    {
        return (string) Configuration::get('payments.poynt.publicKey', '');
    }

    /**
     * Gets the configured service ID.
     *
     * @return string
     * @throws Exception
     */
    public static function getServiceId() : string
    {
        return (string) Configuration::get('payments.poynt.serviceId', '');
    }

    /**
     * Gets the site's store ID.
     *
     * This is distinct from the value set with ::getStoreId(), which is a store ID for the connected Poynt terminal.
     *
     * @return string
     */
    public static function getSiteStoreId() : string
    {
        return (string) Configuration::get('payments.poynt.siteStoreId', '');
    }

    /**
     * Gets the Poynt API webhook secret. This secret is passed during Webhook
     * registration calls, and is used by Poynt to sign outgoing webhooks, and
     * by us to verify them.
     *
     * @return string
     */
    public static function getWebhookSecret() : string
    {
        if (! $webhookSecret = Configuration::get('payments.poynt.webhookSecret', '')) {
            $webhookSecret = StringHelper::generateUuid4();
            Configuration::set('payments.poynt.webhookSecret', $webhookSecret);
            update_option('mwc_payments_poynt_webhookSecret', $webhookSecret);
        }

        return (string) $webhookSecret;
    }

    /**
     * Sets the app ID.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setAppId(string $value)
    {
        update_option('mwc_payments_poynt_appId', $value);

        Configuration::set('payments.poynt.appId', $value);
    }

    /**
     * Sets the application ID.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setApplicationId(string $value)
    {
        update_option('mwc_payments_poynt_applicationId', $value);

        Configuration::set('payments.poynt.applicationId', $value);
    }

    /**
     * Sets the business ID.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setBusinessId(string $value)
    {
        update_option('mwc_payments_poynt_businessId', $value);

        Configuration::set('payments.poynt.businessId', $value);
    }

    /**
     * Sets the business created at timestamp.
     *
     * @param int $value
     */
    public static function setBusinessCreatedAt(int $value)
    {
        update_option('mwc_payments_poynt_businessCreatedAt', $value);

        Configuration::set('payments.poynt.businessCreatedAt', $value);
    }

    /**
     * Sets the private key.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setPrivateKey(string $value)
    {
        update_option('mwc_payments_poynt_privateKey', $value);

        Configuration::set('payments.poynt.privateKey', $value);
    }

    /**
     * Sets the public key.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setPublicKey(string $value)
    {
        update_option('mwc_payments_poynt_publicKey', $value);

        Configuration::set('payments.poynt.publicKey', $value);
    }

    /**
     * Sets the service ID.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setServiceId(string $value)
    {
        update_option('mwc_payments_poynt_serviceId', $value);

        Configuration::set('payments.poynt.serviceId', $value);
    }

    /**
     * Sets the site's store ID.
     *
     * This is distinct from the value set with ::setStoreId(), which is a store ID for the connected Poynt terminal.
     *
     * @param string $value
     */
    public static function setSiteStoreId(string $value)
    {
        update_option('mwc_payments_poynt_siteStoreId', $value);

        Configuration::set('payments.poynt.siteStoreId', $value);
    }

    /**
     * Determines if the user has any activated Poynt smart terminal.
     *
     * @param StoreDevice[] $devices
     * @throws Exception
     */
    public static function checkActivatedDevices(array $devices = [])
    {
        if (empty($devices)) {
            $devices = static::getStoreDevices();
        }

        foreach ($devices as $device) {
            /** @var StoreDevice $device */
            if (! $device->isActivePoyntSmartTerminal()) {
                continue;
            }

            if (! static::hasPoyntSmartTerminalActivated()) {
                Events::broadcast(new PoyntStoreDeviceFirstActivatedEvent($device));
            }

            update_option('mwc_payments_payinperson_terminal_activated', true);
            Configuration::set('payments.godaddy-payments-payinperson.hasTerminalActivated', true);

            // @NOTE: Return early here as we have already set the intended cache options
            return;
        }

        update_option('mwc_payments_payinperson_terminal_activated', false);
        Configuration::set('payments.godaddy-payments-payinperson.hasTerminalActivated', false);
    }

    /**
     * Gets the store ID from the devices and saves it.
     *
     * @param StoreDevice[] $devices
     * @throws Exception
     */
    public static function setStoreId(array $devices = [])
    {
        if (! empty(Configuration::get('payments.poynt.storeId'))) {
            return;
        }

        if (empty($devices)) {
            $devices = static::getStoreDevices();
        }

        foreach ($devices as $device) {
            /** @var StoreDevice $device */
            if (! $device->isActivePoyntSmartTerminal()) {
                continue;
            }

            update_option('mwc_payments_poynt_storeId', $device->getStoreId());
            Configuration::set('payments.poynt.storeId', $device->getStoreId());

            return;
        }
    }

    /**
     * Determines if the site has any Poynt smart terminal devices activated in the configurations.
     *
     * @return bool
     */
    public static function hasPoyntSmartTerminalActivated() : bool
    {
        return (bool) Configuration::get('payments.godaddy-payments-payinperson.hasTerminalActivated', false);
    }

    /**
     * Gets the store devices from Poynt API.
     *
     * @return array
     * @throws Exception
     */
    public static function getStoreDevices() : array
    {
        $devices = [];
        $response = (new StoreDevicesRequest())->send();
        $body = $response->getBody();

        if (! empty($body) && $response->getStatus() === 200) {
            foreach ($body as $storeData) {
                /** @var array<string, string> $storeDevice */
                foreach ((array) ArrayHelper::get($storeData, 'storeDevices', []) as $storeDevice) {
                    /* @var StoreDevice[] */
                    $devices[] = (new StoreDeviceAdapter($storeDevice))->convertFromSource();
                }
            }
        }

        return $devices;
    }

    /**
     * Determines whether the site is properly configured to push orders to the Poynt API.
     */
    public static function isSiteReadyToPushOrderDetailsToPoynt() : bool
    {
        // don't send the event the BOPIT feature is disabled
        if (! Configuration::get('features.bopit', false)) {
            return false;
        }

        // bail if status is not connected or suspended
        if (Onboarding::getStatus() !== 'CONNECTED' && Onboarding::getStatus() !== 'SUSPENDED') {
            return false;
        }

        // bail if shop has doesn't have at least one terminal connected
        if (! static::hasPoyntSmartTerminalActivated()) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the supplied order meets the criteria to be pushed to the
     * Poynt API.
     *
     * Note: should this code live elsewhere? Is Poynt in danger of becoming a God object?
     */
    public static function shouldPushOrderDetailsToPoynt(Order $order) : bool
    {
        if (! static::isSiteReadyToPushOrderDetailsToPoynt()) {
            return false;
        }

        // bail if not ordered with our shipping methods
        if (! $order->hasShippingMethod(static::IN_PERSON_SHIPPING_METHOD_IDS)) {
            return false;
        }

        return true;
    }
}
