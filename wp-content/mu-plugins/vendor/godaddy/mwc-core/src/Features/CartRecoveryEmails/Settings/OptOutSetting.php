<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Settings;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ValidationHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Models\AbstractSetting;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataStores\OptOutDataStore;
use InvalidArgumentException;
use ReflectionException;

/**
 * Cart Recovery opt-out setting.
 */
class OptOutSetting extends AbstractSetting
{
    use CanGetNewInstanceTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'godaddy_mwc_cart_recovery_emails_opt_out';
        $this->label = __('Your email and cart will be saved so we can send you an email reminder about this order.', 'mwc-core');
        $this->type = static::TYPE_BOOLEAN;
    }

    /**
     * Saves the opt-out preference for the supplied email address.
     *
     * @param string|null $emailAddress
     * @return bool whether creating the new preference was successful
     * @throws Exception
     */
    public static function create(string $emailAddress = null) : bool
    {
        return static::getNewInstance()->save($emailAddress);
    }

    /**
     * Retrieves the opt-out setting for the supplied email address.
     *
     * @param string $identifier email address
     * @return bool true if the user has opted out, false if not
     * @throws ReflectionException
     */
    public static function get($identifier) : bool
    {
        return OptOutDataStore::getNewInstance()->read($identifier);
    }

    /**
     * Toggles the opt-out setting for the supplied email address.
     *
     * @param string|null $emailAddress
     * @throws InvalidArgumentException|Exception
     */
    public function update(string $emailAddress = null) : bool
    {
        if (static::get($emailAddress)) {
            return $this->delete($emailAddress);
        } else {
            return $this->save($emailAddress);
        }
    }

    /**
     * Deletes the opt-out preference for the supplied email address.
     *
     * @param string|null $emailAddress
     * @return bool whether the opt-out preference was deleted.
     * @throws InvalidArgumentException|Exception
     */
    public function delete(string $emailAddress = null) : bool
    {
        if (! ValidationHelper::isEmail($emailAddress)) {
            throw new InvalidArgumentException('Missing or invalid email address to delete an opt-out preference for.');
        }

        Events::broadcast($this->buildEvent('optOutSetting', 'delete'));

        return OptOutDataStore::getNewInstance()->delete($emailAddress);
    }

    /**
     * Saves the opt-out preference for the supplied email address.
     *
     * @param string|null $emailAddress
     * @return bool whether saving the preference was successful
     * @throws InvalidArgumentException|Exception
     */
    public function save(string $emailAddress = null) : bool
    {
        if (! ValidationHelper::isEmail($emailAddress)) {
            throw new InvalidArgumentException('Missing or invalid email address to save the opt-out preference for.');
        }

        Events::broadcast($this->buildEvent('optOutSetting', 'create'));

        return OptOutDataStore::getNewInstance()->save($emailAddress);
    }
}
