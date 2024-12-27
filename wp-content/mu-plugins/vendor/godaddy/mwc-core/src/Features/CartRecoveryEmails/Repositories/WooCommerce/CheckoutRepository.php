<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Repositories\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\SessionRepository;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataSources\WooCommerce\Adapters\SessionValue\CheckoutAdapter;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;

/**
 * WooCommerce checkout repository.
 */
class CheckoutRepository
{
    /** @var string session key to be used in the WooCommerce session to store the corresponding {@see Checkout} object ID */
    const SESSION_KEY_CHECKOUT_ID = 'checkout_id';

    /**
     * Gets a fresh checkout object based on the WooCommerce checkout data for a session.
     *
     * Does not include information saved in our custom table.
     *
     * @param int|null $wcSessionId
     * @return Checkout|null
     * @throws Exception
     */
    public static function getFromSession(int $wcSessionId = null) : ?Checkout
    {
        if (empty($wcSessionId)) {
            // get from the current session

            try {
                // to make sure any changes to the session are persisted in the database (otherwise WC will only do it during shutdown)
                SessionRepository::getInstance()->save_data();
            } catch (Exception $exception) {
                // current session is not available
                return null;
            }

            $wcSessionKey = SessionRepository::getCustomerId();

            // in some rare cases, the customer ID can unexpectedly return null
            if (empty($wcSessionKey)) {
                return null;
            }

            $wcSession = SessionRepository::getSessionByKey((string) $wcSessionKey);
        } else {
            $wcSession = SessionRepository::getSessionById($wcSessionId);
        }

        if (empty($wcSession)) {
            return null;
        }

        $wcSessionValue = StringHelper::maybeUnserializeRecursively(ArrayHelper::get($wcSession, 'session_value'));
        $wcSessionValue['session_id'] = ArrayHelper::get($wcSession, 'session_id');

        return CheckoutAdapter::getNewInstance($wcSessionValue)->convertFromSource();
    }
}
