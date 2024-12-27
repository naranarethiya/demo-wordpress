<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Interceptor to handle the session expiration time.
 */
class SessionExpirationInterceptor extends AbstractInterceptor
{
    /* @var int time interval in seconds for a session to expire */
    protected $sessionExpiration;

    /* @var int time interval in seconds for a session to become "due to expire" */
    protected $sessionExpiring;

    /**
     * Returns the time interval in seconds for a session to expire.
     *
     * @return int
     */
    public function getSessionExpiration() : int
    {
        return $this->sessionExpiration;
    }

    /**
     * Returns the time interval in seconds for a session to become "due to expire".
     *
     * @return int
     */
    public function getSessionExpiring() : int
    {
        return $this->sessionExpiring;
    }

    /**
     * Initializes the component.
     */
    public function load()
    {
        $this->sessionExpiration = Configuration::get('features.cart_recovery_emails.expired_cart_in_seconds');
        $this->sessionExpiring = Configuration::get('features.cart_recovery_emails.expiring_cart_in_seconds');

        parent::load();
    }

    /**
     * Should implement action and filter hooks.
     * @throws Exception
     */
    public function addHooks()
    {
        Register::filter()
                ->setGroup('wc_session_expiring')
                ->setHandler([$this, 'getSessionExpiring'])
                ->execute();
        Register::filter()
                ->setGroup('wc_session_expiration')
                ->setHandler([$this, 'getSessionExpiration'])
                ->execute();
    }
}
