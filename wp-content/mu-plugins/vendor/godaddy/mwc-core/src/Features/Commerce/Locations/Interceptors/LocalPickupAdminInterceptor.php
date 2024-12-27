<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\LocationsIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Notices\AppleGooglePayLocalPickupNotice;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Contact;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Services\LocationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\GooglePayGateway;
use WC_Meta_Data;

/**
 * Interceptor to handle the Local Pickup Admin components.
 *
 * @NOTE: This is documented by documentation/development/features/commerce/locations/local-pickup-admin-interceptor.md
 */
class LocalPickupAdminInterceptor extends AbstractInterceptor
{
    protected LocationsService $locationsService;

    /**
     * @param LocationsService $locationsService
     */
    public function __construct(LocationsService $locationsService)
    {
        $this->locationsService = $locationsService;
    }

    /**
     * Adds the hook to register.
     *
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
                ->setGroup('woocommerce_shipping_instance_form_fields_local_pickup')
                ->setHandler([$this, 'addLocalPickupLocationsField'])
                ->execute();

        Register::action()
                ->setGroup('admin_enqueue_scripts')
                ->setHandler([$this, 'enqueueAssets'])
                ->execute();

        Register::filter()
            ->setGroup('woocommerce_order_item_display_meta_key')
            ->setHandler([$this, 'maybeFormatPickupLocationMetaKey'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_order_item_display_meta_value')
            ->setHandler([$this, 'maybeFormatPickupLocationMetaValue'])
            ->setArgumentsCount(2)
            ->execute();

        Register::action()
                ->setGroup('admin_init')
                ->setHandler([$this, 'maybeEnqueueAppleGooglePayLocalPickupNotice'])
                ->execute();
    }

    /**
     * Adds local pickup locations field to shipping rate instances.
     *
     * @param mixed $instanceFields
     *
     * @return mixed
     */
    public function addLocalPickupLocationsField($instanceFields)
    {
        if (! $this->shouldAddLocalPickupLocationsField()) {
            return $instanceFields;
        }

        try {
            if ($locations = $this->getLocalPickupOptions($this->locationsService->getLocations())) {
                $instanceFields = ArrayHelper::wrap($instanceFields);

                $localPickupField = ['godaddy_commerce_locations' => [
                    'title'    => __('Available Pickup Locations', 'mwc-core'),
                    'type'     => 'multiselect',
                    'class'    => 'wc-enhanced-select',
                    'desc_tip' => esc_html__('Select the location(s) that should be available for local pickup.', 'mwc-core'),
                    'default'  => count($locations) === 1 ? [array_key_first($locations)] : [''],
                    'options'  => $locations,
                ]];

                // Ensures field is loaded under title if it exists
                if (ArrayHelper::exists($instanceFields, 'title')) {
                    return ArrayHelper::insertAfterKey($instanceFields, $localPickupField, 'title');
                }

                // Adds to the end if there is no title
                return ArrayHelper::combine($instanceFields, $localPickupField);
            }
        } catch (CommerceExceptionContract|Exception $exception) {
            new SentryException($exception->getMessage(), $exception);
        }

        return $instanceFields;
    }

    /**
     * Determines if the local pickup locations field should be added during current request.
     *
     * @return bool
     */
    protected function shouldAddLocalPickupLocationsField() : bool
    {
        $currentScreen = WordPressRepository::getCurrentScreen();

        return WordPressRepository::isAjax() || ($currentScreen && 'woocommerce-wc-settings' === $currentScreen->getPageId());
    }

    /**
     * Enqueues the JavaScript file.
     *
     * @internal
     *
     * @return void
     * @throws Exception
     */
    public function enqueueAssets() : void
    {
        Enqueue::script()
            ->setHandle('mwc-local-pickup-shipping-settings')
            ->setSource(WordPressRepository::getAssetsUrl('js/features/commerce/admin/local-pickup-shipping-settings.js'))
            ->setVersion(TypeHelper::string(Configuration::get('mwc.version'), ''))
            ->setCondition([$this, 'shouldEnqueueAssets'])
            ->setDependencies(['jquery'])
            ->setDeferred(true)
            ->execute();
    }

    /**
     * Returns an array of location information needed for multiselect fields.
     *
     * @param Location[] $locations
     *
     * @return array<string, string>
     */
    protected function getLocalPickupOptions(array $locations) : array
    {
        $fieldsLocations = [];

        foreach ($locations as $location) {
            $fieldsLocations[$location->channelId] = $location->address ? $location->address->address1 : $location->alias;
        }

        return $fieldsLocations;
    }

    /**
     * Determines if the assets should be loaded on the current page.
     *
     * @internal
     *
     * @return bool
     */
    public function shouldEnqueueAssets() : bool
    {
        try {
            return WordPressRepository::isCurrentScreen('woocommerce_page_wc-settings')
                && 'shipping' === ArrayHelper::get($_GET, 'tab');
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Formats the pickup location meta display key to a human-friendly string.
     *
     * @param mixed $displayKey
     *
     * @return mixed
     */
    public function maybeFormatPickupLocationMetaKey($displayKey)
    {
        if ($displayKey === 'godaddy_mwc_commerce_location_id') {
            return __('Pickup location', 'mwc-core');
        }

        return $displayKey;
    }

    /**
     * Formats the pickup location meta display value to a human-friendly string.
     *
     * WooCommerce passes $metaObject as a stdClass with properties $key (the location ID meta key) & $value (the location ID).
     *
     * @param mixed $displayValue
     * @param mixed $metaData
     *
     * @return mixed
     */
    public function maybeFormatPickupLocationMetaValue($displayValue, $metaData)
    {
        if (! $metaData instanceof WC_Meta_Data) {
            return $displayValue;
        }

        $metaData = $metaData->get_data();

        // ensure this meta entry is for our location ID
        if (ArrayHelper::get($metaData, 'key') !== 'godaddy_mwc_commerce_location_id') {
            return $displayValue;
        }

        // ensure the meta entry has a value set
        if (! $locationId = TypeHelper::string(ArrayHelper::get($metaData, 'value'), '')) {
            return $displayValue;
        }

        // get the full location data from the ID and format for display
        try {
            $location = $this->locationsService->getLocation($locationId);

            $displayValue = $this->getFormattedLocationMarkup($location) ?: $locationId;
        } catch (CommerceExceptionContract|Exception $exception) {
            SentryException::getNewInstance('An error occurred getting location data for admin display', $exception);
        }

        return $displayValue;
    }

    /**
     * Gets the formatted markup for display in the admin for the given location.
     *
     * @param Location $location
     *
     * @return string
     */
    protected function getFormattedLocationMarkup(Location $location) : string
    {
        $markup = '';

        if ($alias = $location->alias) {
            $markup .= '<p>'.esc_attr($alias).'</p>';
        }

        if ($address = $location->address) {
            $markup .= '<p>'.esc_attr($this->getFormattedAddress($address)).'</p>';
        }

        foreach ($this->getFormattedContacts($location->contacts) as $contact) {
            $markup .= '<p>'.esc_attr($contact).'</p>';
        }

        return $markup;
    }

    /**
     * Get formatted address.
     *
     * TODO: remove this method and replace its usage with MWC-12030 {@cwiseman 2023-05-02}
     *
     * @param Address $address
     *
     * @return string
     */
    protected function getFormattedAddress(Address $address) : string
    {
        $streetAddress = $address->address1;

        if ($address->address2) {
            $streetAddress .= ', '.$address->address2;
        }

        return $streetAddress.', '.$address->city.' '.$address->state.' '.$address->postalCode;
    }

    /**
     * Gets a list of formatted contacts for display from the given contact objects.
     *
     * TODO: remove this method and replace its usage with MWC-12052 {@cwiseman 2023-05-02}
     *
     * @param Contact[] $contacts
     *
     * @return string[]
     */
    protected function getFormattedContacts(array $contacts) : array
    {
        $formattedContacts = [];

        foreach ($contacts as $contact) {
            if (Contact::TYPE_WORK === $contact->type && $contact->phone->phone) {
                $formattedContacts[] = $contact->phone->phone;
            }
        }

        return $formattedContacts;
    }

    /**
     * Enqueues Notice if Apple Pay or Google Pay are enabled and there's at least one pickup location added.
     */
    public function maybeEnqueueAppleGooglePayLocalPickupNotice() : void
    {
        $hasAtLeastOneWalletGatewayEnabled = ApplePayGateway::isEnabled() || GooglePayGateway::isEnabled();

        if ($hasAtLeastOneWalletGatewayEnabled && LocationsIntegration::hasPickupLocationAdded()) {
            Notices::enqueueAdminNotice(AppleGooglePayLocalPickupNotice::getNewInstance());
        }
    }
}
