<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;

/**
 * The Cart Recovery Emails lifecycle handler.
 */
class Lifecycle implements ComponentContract
{
    /** @var int database table version (time of the latest version using YmdHis format) */
    const CART_RECOVERY_EMAILS_DATABASE_VERSION = 20240806215700;

    /** @var string database version option name */
    const CART_RECOVERY_EMAILS_DATABASE_VERSION_OPTION_NAME = 'godaddy_mwc_cart_recovery_emails_database_version';

    /** @var string checkout database table name */
    const CHECKOUT_DATABASE_TABLE_NAME = 'godaddy_mwc_checkout';

    /** @var string opt-out database table name */
    const OPT_OUTS_DATABASE_TABLE_NAME = 'godaddy_mwc_cart_recovery_emails_opt_outs';

    /**
     * Initializes the component.
     *
     * @throws Exception
     */
    public function load()
    {
        try {
            $this->maybeCreateTables();
        } catch (WordPressDatabaseException $exception) {
            new SentryException($exception->getMessage(), $exception);

            // disable the feature for the duration of this request
            $featureName = CartRecoveryEmails::getName();
            Configuration::set("features.{$featureName}.enabled", false);

            // set a transient to temporarily disable the feature, so we don't try to create the tables on every request
            set_transient(CartRecoveryEmails::TRANSIENT_DISABLE_FEATURE, 1, 15 * MINUTE_IN_SECONDS);
        }
    }

    /**
     * Creates the feature tables, if they don't exist yet.
     *
     * @throws BaseException
     */
    protected function maybeCreateTables()
    {
        if (static::CART_RECOVERY_EMAILS_DATABASE_VERSION <= get_option(static::CART_RECOVERY_EMAILS_DATABASE_VERSION_OPTION_NAME, 0)) {
            return;
        }

        if (! DatabaseRepository::tableExists(static::CHECKOUT_DATABASE_TABLE_NAME)) {
            $this->createCheckoutTable();
        }

        if (! DatabaseRepository::tableExists(static::OPT_OUTS_DATABASE_TABLE_NAME)) {
            $this->createOptOutsTable();
        }

        update_option(static::CART_RECOVERY_EMAILS_DATABASE_VERSION_OPTION_NAME, static::CART_RECOVERY_EMAILS_DATABASE_VERSION);
    }

    /**
     * Creates the checkout table.
     *
     * Note: although the max length of an email can be of 320 characters ({@see https://www.rfc-editor.org/errata_search.php?rfc=3696&eid=1690}),
     * WordPress limits it to a VARCHAR(100), and that's why email_address column is defined as that.
     *
     * @throws Exception|BaseException
     */
    protected function createCheckoutTable()
    {
        $tablePrefix = DatabaseRepository::getTablePrefix();

        DatabaseRepository::createTable(
            static::CHECKOUT_DATABASE_TABLE_NAME,
            [
                'id'                 => ['BIGINT(20)', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'session_id'         => ['BIGINT(20)', 'UNSIGNED', 'NOT NULL'],
                'email_address'      => ['VARCHAR(100)', 'NOT NULL'],
                'cart_hash'          => ['VARCHAR(32)', 'NOT NULL'],
                'email_scheduled_at' => ['DATETIME', 'DEFAULT NULL'],
                'updated_at'         => ['DATETIME', 'NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
            ],
            [
                'PRIMARY KEY (id)',
                'INDEX (email_address)',
            ],
            [
                "FOREIGN KEY (session_id) REFERENCES {$tablePrefix}woocommerce_sessions(session_id) ON DELETE CASCADE",
            ]
        );
    }

    /**
     * Creates the opt-outs table.
     *
     * Note: although the max length of an email can be of 320 characters ({@see https://www.rfc-editor.org/errata_search.php?rfc=3696&eid=1690}),
     * WordPress limits it to a VARCHAR(100), and that's why email_address column is defined as that.
     *
     * @throws Exception|BaseException
     */
    protected function createOptOutsTable()
    {
        DatabaseRepository::createTable(
            static::OPT_OUTS_DATABASE_TABLE_NAME,
            [
                'email_address' => ['VARCHAR(100)', 'UNIQUE', 'NOT NULL'],
                'created_at'    => ['DATETIME', 'NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
            ],
            [
                'PRIMARY KEY (email_address)',
            ]
        );
    }
}
