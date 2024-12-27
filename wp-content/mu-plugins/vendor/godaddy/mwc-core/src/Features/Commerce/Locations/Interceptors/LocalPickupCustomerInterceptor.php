<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\SessionRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Exceptions\NoLocationsFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Contact;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Services\LocationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\LineItemAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Shipping_Rate;

/**
 * Interceptor to handle the Local Pickup frontend customer components.
 *
 * @NOTE: This is documented by documentation/development/features/commerce/locations/local-pickup-customer-interceptor.md
 */
class LocalPickupCustomerInterceptor extends AbstractInterceptor
{
    const SELECTED_PICKUP_LOCATION_ID_SESSION_KEY = 'mwc_selected_pickup_location_id';

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
        Register::action()
            ->setGroup('woocommerce_after_shipping_rate')
            ->setHandler([$this, 'maybeAddPickupLocationFields'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_thankyou')
            ->setPriority(1)
            ->setHandler([$this, 'maybeAddPickupLocationToThankYouPage'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_checkout_create_order_shipping_item')
            ->setHandler([$this, 'maybeSetShippingItemLocation'])
            ->execute();

        Register::action()
            ->setGroup('wp_enqueue_scripts')
            ->setHandler([$this, 'enqueueAssets'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_update_cart_action_cart_updated')
            ->setHandler([$this, 'maybeUpdateSessionSelectedLocalPickupLocationIdOnCartUpdated'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_checkout_update_order_review')
            ->setHandler([$this, 'maybeUpdateSessionSelectedLocalPickupLocationId'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_checkout_create_order_line_item')
            ->setHandler([$this, 'maybeSetLineItemLocation'])
            ->setArgumentsCount(4)
            ->execute();
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
            ->setHandle('mwc-checkout-local-pickup-selector')
            ->setSource(WordPressRepository::getAssetsUrl('js/features/commerce/frontend/checkout-local-pickup-selector.js'))
            ->setVersion(TypeHelper::string(Configuration::get('mwc.version'), ''))
            ->setDependencies(['jquery'])
            ->setDeferred(true)
            ->execute();

        Enqueue::style()
            ->setHandle('mwc-checkout-local-pickup-selector-checkout')
            ->setSource(WordPressRepository::getAssetsUrl('css/features/commerce/frontend/checkout-page.css'))
            ->execute();
    }

    /**
     * Adds the pickup location to the line item.
     *
     * @param mixed $item
     * @param mixed $cartItemKey
     */
    public function maybeSetLineItemLocation($item, $cartItemKey) : void
    {
        if (! $item instanceof WC_Order_Item_Product) {
            return;
        }

        if (! $woocommerceInstance = WooCommerceRepository::getInstance()) {
            return;
        }

        if (! $shippingPackages = TypeHelper::array($woocommerceInstance->shipping()->get_packages(), [])) {
            return;
        }

        $cartItemKey = TypeHelper::string($cartItemKey, '');

        // find the item in the chosen shipping packages and set the location meta
        foreach (TypeHelper::array($woocommerceInstance->session->get('chosen_shipping_methods'), []) as $shippingMethodKey => $shippingMethodId) {
            // bail if the line item is not part of this chosen shipping package
            if (! ArrayHelper::get($shippingPackages, "{$shippingMethodKey}.contents.{$cartItemKey}")) {
                continue;
            }

            // get the shipping rate instance
            $shippingMethodId = TypeHelper::string($shippingMethodId, '');
            $shippingRate = ArrayHelper::get($shippingPackages, "{$shippingMethodKey}.rates.{$shippingMethodId}");

            if ($shippingRate instanceof WC_Shipping_Rate && $chosenLocationId = $this->getCheckoutSubmittedPickupLocationId($_POST, $shippingRate->get_instance_id())) {
                $item->update_meta_data(LineItemAdapter::FULFILLMENT_CHANNEL_ID_META_KEY, $chosenLocationId);
            }

            break;
        }
    }

    /**
     * Adds the pickup location fields.
     *
     * @param mixed $method
     */
    public function maybeAddPickupLocationFields($method) : void
    {
        if (! $method instanceof WC_Shipping_Rate) {
            return;
        }

        if ('local_pickup' !== ($methodId = $method->get_method_id())) {
            return;
        }

        $instanceId = (string) $method->get_instance_id();

        if (! $shippingMethodData = WooCommerceRepository::getShippingMethodInstance($methodId, $instanceId)) {
            return;
        }

        // bail if the method does not have any locations configured
        if (! TypeHelper::arrayOfStrings(ArrayHelper::get($shippingMethodData, 'godaddy_commerce_locations', []))) {
            return;
        }

        try {
            if (! $availableLocations = $this->locationsService->getLocationsForShippingMethodInstance($shippingMethodData)) {
                throw new NoLocationsFoundException('No locations found.');
            }

            if (count($availableLocations) === 1) {
                $this->renderSinglePickupLocationMarkup(current($availableLocations), $instanceId);
            } else {
                $this->renderMultiPickupLocationsMarkup($availableLocations, $instanceId);
            }
        } catch (CommerceExceptionContract|Exception $exception) {
            $this->renderNoPickupLocationsAvailable();
        }
    }

    /**
     * Renders the markup when there's only one pickup location available.
     *
     * @param Location $location
     * @param string $instanceId
     */
    protected function renderSinglePickupLocationMarkup(Location $location, string $instanceId) : void
    {
        ?>
        <input
            type="hidden"
            name="mwc-commerce-local-pickup-location-selection-<?php echo esc_attr($instanceId); ?>"
            value="<?php echo esc_attr($location->channelId); ?>"
        />
        <div class="mwc-commerce-local-pickup-location__label">
            <?php if ($address = $location->address) : ?>
                <span class="mwc-commerce-local-pickup-location__title">
                    <?php echo esc_attr($location->alias); ?>
                </span>
                <?php
                $this->renderAddress($address);
            endif; ?>
            <?php $this->renderPhone($location->contacts); ?>
        </div>
        <?php
    }

    /**
     * Renders the markup when there are multiple pickup locations available.
     *
     * @param Location[] $locations
     * @param string $instanceId
     *
     * @throws Exception
     */
    protected function renderMultiPickupLocationsMarkup(array $locations, string $instanceId) : void
    {
        ?>
        <br>
        <div class="mwc-commerce-local-pickup-locations-title">
            <?php esc_html_e('Available Pickup Locations', 'mwc-core') ?>
        </div>
        <div class="mwc-commerce-local-pickup-locations-wrapper">
            <?php
            foreach ($locations as $location) {
                $this->renderPickupLocationField($location, $instanceId);
            }
        ?>
        </div>
        <?php
    }

    /**
     * Adds the no pickup locations available message.
     *
     * @return void
     */
    protected function renderNoPickupLocationsAvailable() : void
    {
        ?>
        <br><span class="title"><?php esc_html_e('No pickup locations available', 'mwc-core') ?></span>
        <div class="mwc-commerce-local-pickup-location__missing">
            <?php esc_html_e('Please choose another shipping method, or contact the store for a pickup location.', 'mwc-core') ?>
        </div>
        <?php
    }

    /**
     * Adds the pickup location fields.
     *
     * @param Location $location
     * @param string $instanceId
     * @throws Exception
     */
    protected function renderPickupLocationField(Location $location, string $instanceId) : void
    {
        $previouslySelectedLocation = SessionRepository::get(static::SELECTED_PICKUP_LOCATION_ID_SESSION_KEY);

        $isChecked = $location->channelId === $previouslySelectedLocation;

        ?>
        <div class="mwc-commerce-local-pickup-location-wrapper <?php echo $isChecked ? 'selected' : '' ?>">
            <input
                type="radio"
                id="mwc-commerce-local-pickup-location-<?php echo esc_attr($instanceId); ?>-<?php echo esc_attr($location->channelId); ?>"
                name="mwc-commerce-local-pickup-location-selection-<?php echo esc_attr($instanceId); ?>"
                class="mwc-commerce-local-pickup-location"
                value="<?php echo esc_attr($location->channelId); ?>"
                <?php echo $isChecked ? 'checked' : '' ?>
            />
            <label
                class="mwc-commerce-local-pickup-location__label"
                for="mwc-commerce-local-pickup-location-<?php echo esc_attr($instanceId); ?>-<?php echo esc_attr($location->channelId); ?>"
            >
                <span class="mwc-commerce-local-pickup-location__title">
                    <?php echo esc_attr($location->alias); ?>
                </span>
                <?php if ($address = $location->address) :
                    $this->renderAddress($address);
                endif; ?>
                <?php $this->renderPhone($location->contacts); ?>
            </label>
        </div>
        <br>
        <?php
    }

    /**
     * Adds the pickup location information to the thank you page.
     *
     * @param Location $location
     */
    protected function renderPickupLocationForThankYouSection(Location $location) : void
    {
        ?>
            <div class="mwc-commerce-local-pickup-location__label">
                <span class="mwc-commerce-local-pickup-location__title">
                    <?php echo esc_attr($location->alias); ?>
                </span>
                <?php if ($address = $location->address) :
                    $this->renderAddress($address);
                endif; ?>
                <?php $this->renderPhone($location->contacts); ?>
            </div>
        <?php
    }

    /**
     * Adds the pickup location address.
     *
     * @param Address $address
     */
    protected function renderAddress(Address $address) : void
    {
        $streetAddress = $address->address1;

        if ($address->address2) {
            $streetAddress .= ', '.$address->address2;
        }

        ?>
        <div class="mwc-commerce-local-pickup-location__address">
            <?php echo esc_html($streetAddress.', '.$address->city.', '.$address->state.' '.$address->postalCode); ?>
        </div>
        <?php
    }

    /**
     * Adds the pickup location phone.
     *
     * @param Contact[] $contacts
     */
    protected function renderPhone(array $contacts) : void
    {
        foreach ($contacts as $contact) {
            if ($contact->type === Contact::TYPE_WORK) {
                ?>
                <div class="mwc-commerce-local-pickup-location__phone">
                    <?php echo esc_html($contact->phone->phone); ?>
                </div>
                <?php
                break;
            }
        }
    }

    /**
     * Sets the shipping item location from checkout form input.
     *
     * @param mixed $item
     */
    public function maybeSetShippingItemLocation($item) : void
    {
        if (! $item instanceof WC_Order_Item_Shipping) {
            return;
        }

        if ('local_pickup' !== $item->get_method_id()) {
            return;
        }

        if (! $chosenLocationId = $this->getCheckoutSubmittedPickupLocationId($_POST, (int) $item->get_instance_id())) {
            return;
        }

        $item->update_meta_data('godaddy_mwc_commerce_location_id', $chosenLocationId);
    }

    /**
     * Adds the pickup location fields.
     *
     * @param int $orderId
     */
    public function maybeAddPickupLocationToThankYouPage($orderId) : void
    {
        $wcOrder = OrdersRepository::get((int) $orderId);

        if (! $wcOrder instanceof \WC_Order) {
            return;
        }

        try {
            $pickupLocations = $this->locationsService->getLocationsForOrder(OrderAdapter::getNewInstance($wcOrder)->convertFromSource());

            if (! empty($pickupLocations)) {
                echo '<section class="mwc-commerce-local-pickup-location-wrapper">';
                echo '<h2 class="woocommerce-column__title">'.__('Pickup Location Information', 'mwc-core').'</h2>';
                foreach ($pickupLocations as $pickupLocation) {
                    ?>
                    <p>
                    <?php $this->renderPickupLocationForThankYouSection($pickupLocation) ?>
                    </p>
                    <?php
                }
                echo '</section>';
            }
        } catch (CommerceExceptionContract|Exception $exception) {
        }
    }

    /**
     * May get a selected pickup location ID in the $_POST data.
     *
     * @return string|null
     */
    protected function maybeGetSelectedPickupLocationId() : ?string
    {
        $locationId = TypeHelper::string(ArrayHelper::get($_POST, 'mwc-commerce-local-pickup-location-selection-id'), '');

        if ($locationId) {
            return SanitizationHelper::input($locationId);
        }

        $postData = TypeHelper::string(ArrayHelper::get($_POST, 'post_data'), '');

        $parsedData = wp_parse_args($postData);

        if (! empty($parsedData) && $localPickupShippingMethodId = $this->maybeGetLocalPickupShippingMethodId($parsedData)) {
            return $this->getCheckoutSubmittedPickupLocationId($parsedData, $localPickupShippingMethodId);
        }

        return null;
    }

    /**
     * May get the local pickup shipping method ID, extracted from the shipping_method key.
     *
     * @param array<string, mixed> $postParsedData
     * @return int|null
     */
    protected function maybeGetLocalPickupShippingMethodId(array $postParsedData) : ?int
    {
        $shippingMethods = ArrayHelper::wrap(ArrayHelper::get($postParsedData, 'shipping_method'));

        foreach ($shippingMethods as $shippingMethod) {
            if (StringHelper::contains($shippingMethod, 'local_pickup:')) {
                return TypeHelper::int(StringHelper::after($shippingMethod, 'local_pickup:'), 0);
            }
        }

        return null;
    }

    /**
     * Executes after the cart is updated: May update the selected local pickup location in the session if any present on the request.
     *
     * @param bool|mixed $cart_updated
     * @return bool|mixed
     */
    public function maybeUpdateSessionSelectedLocalPickupLocationIdOnCartUpdated($cart_updated)
    {
        $this->maybeUpdateSessionSelectedLocalPickupLocationId();

        return $cart_updated;
    }

    /**
     * May update the selected local pickup location in the session if any present on the request.
     */
    public function maybeUpdateSessionSelectedLocalPickupLocationId() : void
    {
        try {
            $locationId = $this->maybeGetSelectedPickupLocationId();

            if (! empty($locationId)) {
                SessionRepository::set(static::SELECTED_PICKUP_LOCATION_ID_SESSION_KEY, $locationId);
            }
        } catch(Exception $e) {
            // catch exceptions in hook callbacks to avoid fatal errors
            SentryException::getNewInstance('Failed to update local pickup location in the session.', $e);
        }
    }

    /**
     * Gets the form-submitted pickup location ID, if any.
     *
     * The resulting value is sanitized.
     *
     * @param array<string, mixed> $formData
     * @param int $shippingMethodInstanceId
     *
     * @return string|null
     */
    protected function getCheckoutSubmittedPickupLocationId(array $formData, int $shippingMethodInstanceId) : ?string
    {
        return SanitizationHelper::input(TypeHelper::string(ArrayHelper::get($formData, "mwc-commerce-local-pickup-location-selection-{$shippingMethodInstanceId}"), '')) ?: null;
    }
}
