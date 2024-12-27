<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Traits\ShouldLoadOnlyIfWooCommerceIsEnabledTrait;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CustomerAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use WC_Customer;

/**
 * A WooCommerce interceptor to hook on customer actions and filters.
 */
class CustomerInterceptor extends AbstractInterceptor
{
    use ShouldLoadOnlyIfWooCommerceIsEnabledTrait;

    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('user_register')
            ->setHandler([$this, 'onWordPressUserRegister'])
            ->execute();

        Register::action()
            ->setGroup('profile_update')
            ->setHandler([$this, 'onWordPressUserProfileUpdate'])
            ->execute();
    }

    /**
     * Saves the native customer model when a {@see WP_User} with role 'customer' is added.
     *
     * @internal
     *
     * @param int|mixed $userId the WordPress user ID
     * @return void
     */
    public function onWordPressUserRegister($userId) : void
    {
        try {
            if ($sourceCustomer = $this->getWooCommerceCustomer($userId)) {
                $this->getNativeCustomer($sourceCustomer)->save();
            }
        } catch (Exception $exception) {
            // since we are in a hook callback context we should catch exceptions instead of throwing
            return;
        }
    }

    /**
     * Updates the native customer model when a {@see WP_User} with role 'customer' is updated.
     *
     * @internal
     *
     * @param int|mixed $userId the WordPress user ID
     * @return void
     */
    public function onWordPressUserProfileUpdate($userId) : void
    {
        try {
            if ($sourceCustomer = $this->getWooCommerceCustomer($userId)) {
                $this->getNativeCustomer($sourceCustomer)->update();
            }
        } catch (Exception $exception) {
            // since we are in a hook callback context we should catch exceptions instead of throwing
            return;
        }
    }

    /**
     * Gets a WooCommerce customer object from a user ID.
     *
     * @param int|mixed $userId
     * @return WC_Customer|null
     * @throws Exception
     */
    protected function getWooCommerceCustomer($userId) : ?WC_Customer
    {
        $user = is_numeric($userId) ? get_user_by('id', (int) $userId) : null;

        return $user && ArrayHelper::contains((array) $user->roles, 'customer')
            ? new WC_Customer($user->ID)
            : null;
    }

    /**
     * Converts a WooCommerce customer object into a native customer object.
     *
     * @param WC_Customer $customer
     * @return Customer
     */
    protected function getNativeCustomer(WC_Customer $customer) : Customer
    {
        return CustomerAdapter::getNewInstance($customer)->convertFromSource();
    }
}
