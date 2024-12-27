<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataStores\WooCommerce;

use DateTime;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseTableDoesNotExistException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Cart;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Collections\CheckoutCollection;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Lifecycle;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Repositories\WooCommerce\CheckoutRepository;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\Contracts\DataStoreContract;

/**
 * Checkout datastore class.
 */
class CheckoutDataStore implements DataStoreContract
{
    use CanGetNewInstanceTrait;

    /**
     * Deletes a record from the data store.
     *
     * @param int|null $id
     * @return bool
     * @throws BaseException
     */
    public function delete(int $id = null) : bool
    {
        if (null === $id) {
            throw new BaseException('Checkout ID is missing.');
        }

        return (bool) DatabaseRepository::delete(Lifecycle::CHECKOUT_DATABASE_TABLE_NAME, ['id' => $id]);
    }

    /**
     * Reads an existing (already saved) checkout record from the data store.
     *
     * Includes data from our custom table and from the session.
     *
     * @param int|null $id
     * @return Checkout|null
     * @throws WordPressDatabaseTableDoesNotExistException|BaseException|Exception
     */
    public function read(int $id = null) : ?Checkout
    {
        if (null === $id) {
            throw new BaseException('Checkout ID is missing.');
        }

        $wpdb = DatabaseRepository::instance();
        $table = Lifecycle::CHECKOUT_DATABASE_TABLE_NAME;

        if (! DatabaseRepository::tableExists($table)) {
            throw new WordPressDatabaseTableDoesNotExistException($table);
        }

        $result = $wpdb->get_row(
            $wpdb->prepare("
            SELECT *
            FROM {$table}
            WHERE id = %d
            LIMIT 1
        ", (int) $id),
            ARRAY_A);

        if (empty($result)) {
            return null;
        }

        return $this->getPopulatedObject((array) $result);
    }

    /**
     * Gets the latest checkout record (already saved) for a given email address.
     *
     * Includes data from our custom table and from the session.
     *
     * @param string $emailAddress
     * @return Checkout|null
     * @throws WordPressDatabaseTableDoesNotExistException|BaseException|Exception
     */
    public function findLatestByEmailAddress(string $emailAddress) : ?Checkout
    {
        $wpdb = DatabaseRepository::instance();
        $table = Lifecycle::CHECKOUT_DATABASE_TABLE_NAME;

        if (! DatabaseRepository::tableExists($table)) {
            throw new WordPressDatabaseTableDoesNotExistException($table);
        }

        $result = $wpdb->get_row(
            $wpdb->prepare("
            SELECT *
            FROM {$table}
            WHERE email_address = %s
            ORDER BY updated_at DESC
            LIMIT 1
        ", $emailAddress),
            ARRAY_A);

        if (empty($result)) {
            return null;
        }

        return $this->getPopulatedObject((array) $result);
    }

    /**
     * Gets all the latest checkout records (already saved) for a given email address.
     *
     * @param string $emailAddress
     * @return Checkout[]
     * @throws WordPressDatabaseTableDoesNotExistException|BaseException|Exception
     */
    protected function findAllLatestByEmailAddress(string $emailAddress) : array
    {
        $wpdb = DatabaseRepository::instance();
        $table = Lifecycle::CHECKOUT_DATABASE_TABLE_NAME;

        if (! DatabaseRepository::tableExists($table)) {
            throw new WordPressDatabaseTableDoesNotExistException($table);
        }

        $sql = $wpdb->prepare("
            SELECT *
            FROM {$table}
            WHERE email_address = %s
            ORDER BY updated_at DESC
        ", $emailAddress);

        /** @var array<string, mixed>[] $results */
        $results = is_string($sql) ? $wpdb->get_results($sql, ARRAY_A) : [];

        if (! ArrayHelper::accessible($results)) {
            return [];
        }

        $checkouts = [];
        foreach ($results as $rowData) {
            $checkouts[] = $this->getPopulatedObject($rowData);
        }

        return $checkouts;
    }

    /**
     * Gets a Checkout object from the session data (if possible) or a brand-new Checkout object, populated with the
     * data from the database row.
     *
     * @param array<string, mixed> $databaseRowData
     * @return Checkout
     * @throws WordPressDatabaseTableDoesNotExistException|BaseException|Exception
     */
    protected function getPopulatedObject(array $databaseRowData) : Checkout
    {
        $checkout = null;
        $sessionId = ArrayHelper::get($databaseRowData, 'session_id', 0);

        if (! empty($sessionId)) {
            // try to instantiate a checkout object with the information from the session
            $checkout = CheckoutRepository::getFromSession($sessionId);
        }

        if (empty($checkout)) {
            $checkout = $this->buildEmptyCheckout();
        }

        // populates the object with the data from the database row and returns it
        $checkout = $this->readFromCustomTable($checkout, $databaseRowData);

        /*
         * If the cart has no line items, then this indicates that the WooCommerce cart record no longer exists,
         * is empty, or is otherwise invalid. We need to explicitly wipe the cart hash value, otherwise we can end
         * up handling an outdated cart hash later down the line and send out emails for empty carts.
         */
        if (empty($checkout->getCart()->getLineItems())) {
            $checkout->setWcCartHash('');
        }

        return $checkout;
    }

    /**
     * Builds a checkout instance with an empty cart.
     *
     * @return Checkout
     */
    protected function buildEmptyCheckout() : Checkout
    {
        /** @var Checkout $checkout */
        $checkout = (new Checkout())
            ->setCart(new Cart())
            ->setCreatedAt(new DateTime());

        return $checkout;
    }

    /**
     * Sets the object properties based on the values from our custom table.
     *
     * @param Checkout $checkout
     * @param array<string, mixed> $data data from a row in our custom table
     * @return Checkout
     * @throws Exception
     */
    protected function readFromCustomTable(Checkout $checkout, array $data) : Checkout
    {
        $scheduledAt = ArrayHelper::get($data, 'email_scheduled_at');

        if (! empty($sessionId = ArrayHelper::get($data, 'session_id'))) {
            $checkout->setWcSessionId($sessionId);
        }

        return $checkout->setId(ArrayHelper::get($data, 'id'))
            ->setEmailAddress(ArrayHelper::get($data, 'email_address', ''))
            ->setWcCartHash(ArrayHelper::get($data, 'cart_hash', ''))
            ->setEmailScheduledAt($scheduledAt ? new DateTime($scheduledAt) : null)
            ->setUpdatedAt(new DateTime(ArrayHelper::get($data, 'updated_at', 'now')));
    }

    /**
     * Saves a record to the data store.
     *
     * @param Checkout|null $checkout
     * @return Checkout
     * @throws WordPressDatabaseTableDoesNotExistException
     * @throws BaseException
     */
    public function save(Checkout $checkout = null) : Checkout
    {
        if (null === $checkout) {
            throw new BaseException('Checkout object is missing.');
        }

        $utcTime = new DateTime();
        $utcTime->setTimezone(new DateTimeZone('UTC'));

        $checkout->setUpdatedAt($utcTime);

        $args = [
            'session_id'         => $checkout->getWcSessionId(),
            'email_address'      => $checkout->getEmailAddress(),
            'cart_hash'          => $checkout->getWcCartHash(),
            'email_scheduled_at' => $checkout->getEmailScheduledAt() ? $checkout->getEmailScheduledAt()->format('Y-m-d H:i:s') : null,
            'updated_at'         => $checkout->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        if ($checkout->isNew()) {
            // insert new checkout (ID will be set automatically)
            $result = DatabaseRepository::insert(Lifecycle::CHECKOUT_DATABASE_TABLE_NAME, $args);
            if ($result) {
                $checkout->setId($result);
            }
        } elseif (empty(static::read($checkout->getId()))) {
            // insert checkout with the same ID back (WC session was deleted and checkout was cascade deleted)
            DatabaseRepository::insert(Lifecycle::CHECKOUT_DATABASE_TABLE_NAME, ArrayHelper::combine($args, [
                'id' => $checkout->getId(),
            ]));
        } else {
            // update existing checkout
            DatabaseRepository::update(Lifecycle::CHECKOUT_DATABASE_TABLE_NAME, $args, [
                'id' => $checkout->getId(),
            ]);
        }

        return $checkout;
    }

    /**
     * Returns a CheckoutCollection filtered by the supplied email address.
     *
     * @param string $emailAddress
     * @return CheckoutCollection
     */
    public function findAllByEmailAddress(string $emailAddress) : CheckoutCollection
    {
        try {
            $checkouts = $this->findAllLatestByEmailAddress($emailAddress);
        } catch (WordPressDatabaseTableDoesNotExistException|BaseException|Exception $exception) {
            $checkouts = [];
        }

        return CheckoutCollection::seed($checkouts);
    }
}
