<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models;

use DateInterval;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Events\Exceptions\EventTransformFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\Checkout as CommonCheckout;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\SessionRepository;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataStores\WooCommerce\CheckoutDataStore;

/**
 * An object representation of the checkout for cart recovery purposes.
 */
class Checkout extends CommonCheckout
{
    /** @var string default status */
    const STATUS_NEW = 'new';

    /** @var string before a checkout becomes abandoned is considered active / in progress */
    const STATUS_ACTIVE = 'active';

    /** @var string after 15 minutes a checkout becomes abandoned */
    const STATUS_ABANDONED = 'abandoned';

    /** @var string after the email has been presumably sent, the checkout becomes recoverable */
    const STATUS_RECOVERABLE = 'recoverable';

    /** @var string after the user followed a cart recovery link, the checkout is pending recovery */
    const STATUS_PENDING_RECOVERY = 'pending_recovery';

    /** @var string key used in the WooCommerce status to mark a checkout recoverable */
    const STATUS_SESSION_KEY = 'mwc_checkout_recovery_status';

    /** @var int corresponding WooCommerce session identifier */
    protected $wcSessionId;

    /** @var string corresponding WooCommerce session hash */
    protected $wcCartHash;

    /** @var DateTime|null datetime when the cart recovery email is scheduled at */
    protected $emailScheduledAt;

    /** @var CheckoutDataStore related data store */
    protected $checkoutDataStore;

    /**
     * Checkout constructor.
     */
    public function __construct()
    {
        $this->checkoutDataStore = CheckoutDataStore::getNewInstance();
    }

    /**
     * Gets the Session ID.
     *
     * @return int
     */
    public function getWcSessionId() : int
    {
        return $this->wcSessionId ?: 0;
    }

    /**
     * Gets the hash for the associated cart session.
     *
     * @return string
     */
    public function getWcCartHash() : string
    {
        return $this->wcCartHash ?: '';
    }

    /**
     * Gets the checkout status.
     *
     * @return string
     */
    public function getStatus() : string
    {
        // we bail early as the checkout may not have some of its properties set if new
        if ($this->isNew() || ! $this->getUpdatedAt() instanceof DateTime) {
            return self::STATUS_NEW;
        }

        // default status for an active cart in progress
        $status = static::STATUS_ACTIVE;

        $now = new DateTime('now');

        // a cart is considered abandoned after 15 minutes since the last update
        if ((clone $this->getUpdatedAt())->add(new DateInterval('PT15M')) < $now) {
            $status = static::STATUS_ABANDONED;
        }

        $scheduledAt = $this->getEmailScheduledAt();

        // a cart is considered recoverable once the cart recovery email is scheduled;
        // this means the email can be sent now, or it may have been sent already
        if ($scheduledAt && $scheduledAt <= $now) {
            $status = static::STATUS_RECOVERABLE;
        }

        $session = ArrayHelper::get(SessionRepository::getSessionById($this->getWcSessionId()), 'session_value');
        $savedSessionStatus = $session ? ArrayHelper::get(StringHelper::maybeUnserializeRecursively($session), static::STATUS_SESSION_KEY) : null;

        // a cart is considered pending recovery once the user followed a cart recovery link
        if (static::STATUS_PENDING_RECOVERY === $savedSessionStatus) {
            $status = static::STATUS_PENDING_RECOVERY;
        }

        return $status;
    }

    /**
     * Determines if the checkout is currently active.
     *
     * @return bool
     */
    public function isActive() : bool
    {
        return static::STATUS_ACTIVE === $this->getStatus();
    }

    /**
     * Determines if the checkout is abandoned.
     *
     * @return bool
     */
    public function isAbandoned() : bool
    {
        return static::STATUS_ABANDONED === $this->getStatus();
    }

    /**
     * Determines if the checkout is recoverable.
     *
     * @return bool
     */
    public function isRecoverable() : bool
    {
        return static::STATUS_RECOVERABLE === $this->getStatus();
    }

    /**
     * Determines whether the checkout is pending recovery.
     *
     * @return bool
     */
    public function isPendingRecovery() : bool
    {
        return static::STATUS_PENDING_RECOVERY === $this->getStatus();
    }

    /**
     * Gets the time the email was scheduled, or null if it has not been scheduled.
     *
     * @return DateTime|null
     */
    public function getEmailScheduledAt() : ?DateTime
    {
        return $this->emailScheduledAt;
    }

    /**
     * Sets the email address.
     *
     * @param string $value
     * @return CommonCheckout
     */
    public function setEmailAddress(string $value) : CommonCheckout
    {
        if ($this->getEmailAddress() && $value !== $this->getEmailAddress()) {
            // reset the scheduled date, so it schedules the email again with the new email
            $this->setEmailScheduledAt(null);
        }

        return parent::setEmailAddress($value);
    }

    /**
     * Sets the Session ID.
     *
     * @param int $value
     * @return $this
     */
    public function setWcSessionId(int $value) : Checkout
    {
        $this->wcSessionId = $value;

        return $this;
    }

    /**
     * Sets the cart hash.
     *
     * @param string $value
     * @return $this
     */
    public function setWcCartHash(string $value) : Checkout
    {
        $this->wcCartHash = $value;

        return $this;
    }

    /**
     * Sets the email scheduled at date.
     *
     * @param DateTime|null $value
     * @return $this
     */
    public function setEmailScheduledAt(?DateTime $value) : Checkout
    {
        $this->emailScheduledAt = $value;

        return $this;
    }

    /**
     * Creates a new instance of Checkout and saves it to the database.
     *
     * @param array $propertyValues checkout data
     * @return Checkout
     * @throws BaseException
     * @throws EventTransformFailedException
     */
    public static function create(array $propertyValues = []) : Checkout
    {
        parent::create();

        if (! ArrayHelper::has($propertyValues, ['wcSessionId', 'emailAddress'])) {
            throw new BaseException('Checkout::create() requires both WC Session ID and E-mail Address');
        }

        $checkout = static::seed($propertyValues);

        $checkout->save();

        return $checkout;
    }

    /**
     * Deletes a given instance of Checkout from the database.
     *
     * @return bool
     * @throws BaseException
     * @throws EventTransformFailedException
     */
    public function delete() : bool
    {
        parent::delete();

        $result = $this->checkoutDataStore->delete($this->getId());

        Events::broadcast($this->buildEvent('checkout', 'delete'));

        return $result;
    }

    /**
     * Saves a given instance of Checkout to the database.
     *
     * @return Checkout
     * @throws BaseException|Exception
     */
    public function save() : Checkout
    {
        if ($this->isNew()) {
            Events::broadcast($this->buildEvent('checkout', 'create'));
        } else {
            Events::broadcast($this->buildEvent('checkout', 'update'));
        }

        return $this->checkoutDataStore->save($this);
    }
}
