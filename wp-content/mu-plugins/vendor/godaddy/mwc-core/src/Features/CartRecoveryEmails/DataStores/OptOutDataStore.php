<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\DataStores;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseTableDoesNotExistException;
use GoDaddy\WordPress\MWC\Common\Helpers\ValidationHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Lifecycle;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\Contracts\DataStoreContract;

/**
 * A data store for handling customers' cart recovery emails opt-out preference.
 */
class OptOutDataStore implements DataStoreContract
{
    use CanGetNewInstanceTrait;

    /**
     * Deletes an opt-out preference record for a given email address.
     *
     * @param string|null $emailAddress
     * @return bool
     * @throws Exception
     */
    public function delete(string $emailAddress = null) : bool
    {
        if (! ValidationHelper::isEmail($emailAddress)) {
            return false;
        }

        return (bool) DatabaseRepository::delete(Lifecycle::OPT_OUTS_DATABASE_TABLE_NAME, [
            'email_address' => $emailAddress,
        ]);
    }

    /**
     * Gets the opt-out preference for a given email address.
     *
     * @param string|null $emailAddress
     * @return bool
     * @throws WordPressDatabaseTableDoesNotExistException
     */
    public function read(string $emailAddress = null) : bool
    {
        if (! ValidationHelper::isEmail($emailAddress)) {
            return false;
        }

        $table = Lifecycle::OPT_OUTS_DATABASE_TABLE_NAME;

        if (! DatabaseRepository::tableExists($table)) {
            throw new WordPressDatabaseTableDoesNotExistException($table);
        }

        $result = DatabaseRepository::getRow("SELECT * FROM {$table} WHERE email_address = %s LIMIT 1", [$emailAddress]);

        return ! empty($result);
    }

    /**
     * Saves the opt-out preference for a given email address.
     *
     * @param string|null $emailAddress
     * @return bool
     * @throws Exception
     */
    public function save(string $emailAddress = null) : bool
    {
        if (! ValidationHelper::isEmail($emailAddress) || $this->read($emailAddress)) {
            return false;
        }

        return (bool) DatabaseRepository::insert(Lifecycle::OPT_OUTS_DATABASE_TABLE_NAME, [
            'email_address' => $emailAddress,
        ]);
    }
}
