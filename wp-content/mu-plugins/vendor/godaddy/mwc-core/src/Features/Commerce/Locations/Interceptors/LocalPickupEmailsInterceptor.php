<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Contact;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Providers\DataObjects\Location;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\Services\LocationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Address;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Emails\ReadyForPickupEmail;
use WC_Email;
use WC_Email_Customer_Processing_Order;
use WC_Email_New_Order;
use WC_Order;

/**
 * Interceptor to handle the Local Pickup Email components.
 *
 * @NOTE: This is documented by documentation/development/features/commerce/locations/local-pickup-emails-interceptor.md
 */
class LocalPickupEmailsInterceptor extends AbstractInterceptor
{
    const PICKUP_LOCATIONS_HOOK_PRIORITY = 29; // this is the priority necessary to display the pickup locations above "Pickup Instructions" & below billing details

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
            ->setGroup('woocommerce_email_customer_details')
            ->setPriority(static::PICKUP_LOCATIONS_HOOK_PRIORITY)
            ->setArgumentsCount(4)
            ->setHandler([$this, 'maybeAddPickupLocationDetails'])
            ->execute();
    }

    /**
     * Maybe adds the pickup location details to emails.
     *
     * @param mixed $order
     * @param mixed $sentToAdmin
     * @param mixed $plainText
     * @param mixed $email
     */
    public function maybeAddPickupLocationDetails($order, $sentToAdmin, $plainText, $email) : void
    {
        // sanity check for bad actors
        if (! $order instanceof WC_Order || ! $email instanceof WC_Email) {
            return;
        }

        // only render for specific emails
        if (! $this->shouldRenderPickupLocationsForEmail($email)) {
            return;
        }

        try {
            if (! $locations = $this->getPickupLocationsForOrder($order)) {
                return;
            }

            $this->renderPickupLocations($locations, TypeHelper::bool($plainText, false));
        } catch (CommerceExceptionContract|Exception $exception) {
            SentryException::getNewInstance('An error occurred getting pickup locations for an order email.', $exception);
        }
    }

    /**
     * Determines if pickup locations should be rendered for the given email.
     *
     * @param WC_Email $email
     *
     * @return bool
     */
    protected function shouldRenderPickupLocationsForEmail(WC_Email $email) : bool
    {
        return ArrayHelper::contains([
            WC_Email_New_Order::class,
            WC_Email_Customer_Processing_Order::class,
            ReadyForPickupEmail::class,
        ], get_class($email));
    }

    /**
     * Gets the pickup locations for the given order.
     *
     * @param WC_Order $order
     *
     * @return Location[]
     *
     * @throws CommerceExceptionContract|AdapterException|Exception
     */
    protected function getPickupLocationsForOrder(WC_Order $order) : array
    {
        $nativeOrder = OrderAdapter::getNewInstance($order)->convertFromSource();

        return $this->locationsService->getLocationsForOrder($nativeOrder);
    }

    /**
     * Renders the pickup locations.
     *
     * @param Location[] $locations
     * @param bool $plainText
     */
    protected function renderPickupLocations(array $locations, bool $plainText) : void
    {
        if ($plainText) {
            $this->renderPlainTextPickupLocations($locations);
        } else {
            $this->renderHtmlPickupLocations($locations);
        }
    }

    /**
     * Renders the pickup locations in plain text.
     *
     * @param Location[] $locations
     */
    protected function renderPlainTextPickupLocations(array $locations) : void
    {
        echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
        _e('Pickup Location Information', 'mwc-core');
        echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

        foreach ($locations as $location) {
            if ($alias = $location->alias) {
                echo esc_attr($alias);
                echo "\n";
            }

            if ($address = $location->address) {
                echo esc_attr($this->getFormattedAddress($address));
                echo "\n";
            }

            foreach ($this->getFormattedContacts($location->contacts) as $contact) {
                echo esc_attr($contact);
                echo "\n";
            }

            echo "\n----------------------------------------\n\n";
        }
    }

    /**
     * Renders the pickup locations in HTML.
     *
     * @param Location[] $locations
     */
    protected function renderHtmlPickupLocations(array $locations) : void
    {
        echo '<h2>'.esc_html__('Pickup Location Information', 'mwc-core').'</h2>';

        foreach ($locations as $location) {
            echo '<p>';

            if ($alias = $location->alias) {
                echo '<strong>'.esc_attr($alias).'</strong><br />';
            }

            if ($address = $location->address) {
                echo esc_attr($this->getFormattedAddress($address)).'<br />';
            }

            foreach ($this->getFormattedContacts($location->contacts) as $contact) {
                echo esc_attr($contact).'<br />';
            }

            echo '</p>';
        }
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
     * @param Contact[] $contacts
     *
     * @return string[]
     */
    protected function getFormattedContacts(array $contacts) : array
    {
        return $this->locationsService->getFormattedContacts($contacts);
    }
}
