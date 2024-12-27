<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseTableCreationFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseTableDoesNotExistException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use wpdb;

/**
 * Repository handler for WordPress database handling.
 */
class DatabaseRepository
{
    /**
     * Gets the WordPress DataBase handler instance.
     *
     * @return wpdb
     */
    public static function instance() : wpdb
    {
        global $wpdb;

        return $wpdb;
    }

    /**
     * Creates a table.
     *
     * @param string $tableName
     * @param array $columns
     * @param array $indexes
     * @param array $foreignKeys
     * @throws BaseException
     */
    public static function createTable(string $tableName, array $columns, array $indexes = [], array $foreignKeys = [])
    {
        if (static::tableExists($tableName)) {
            throw new WordPressDatabaseException("Table {$tableName} already exists");
        }

        $wpdb = static::instance();

        $collate = $wpdb->has_cap('collation') ? $wpdb->get_charset_collate() : '';

        /* @NOTE do not remove the extra line breaks, they are required by dbDelta */
        $statement = "CREATE TABLE {$tableName} (\n";

        $tableLines = [];
        foreach ($columns as $columnName => $properties) {
            $tableLines[] = "{$columnName} ".implode(' ', $properties);
        }

        $tableLines = ArrayHelper::combine($tableLines, $indexes, $foreignKeys);

        $statement .= implode(",\n", $tableLines);
        $statement .= "\n) {$collate};";

        try {
            WordPressRepository::requireWordPressUpgradeAPI();
        } catch (Exception $exception) {
            throw new WordPressDatabaseException($exception->getMessage(), $exception);
        }

        dbDelta($statement);

        /* @NOTE since dbDelta will not return an error or throw an Exception if the query fails, we need to check if the table was actually created instead */
        if (! static::tableExists($tableName)) {
            throw new WordPressDatabaseTableCreationFailedException("Table {$tableName} was not created, check your PHP error log for more information");
        }
    }

    /**
     * Deletes a table.
     *
     * @param string $tableName
     * @throws WordPressDatabaseException if the operation failed or another database error occurred
     */
    public static function deleteTable(string $tableName)
    {
        if (! static::tableExists($tableName)) {
            throw new WordPressDatabaseTableDoesNotExistException($tableName);
        }

        $wpdb = static::instance();

        $statement = "DROP TABLE IF EXISTS {$tableName};";

        $deleted = $wpdb->query($statement);

        if (false === $deleted) {
            throw new WordPressDatabaseException("Table {$tableName} was not deleted, check your PHP error log for more information");
        }
    }

    /**
     * Determines whether a table exists in the database.
     *
     * @param string $tableName the table name to be checked
     * @return bool
     */
    public static function tableExists(string $tableName) : bool
    {
        return static::instance()->get_var("SHOW TABLES LIKE '{$tableName}'") === $tableName;
    }

    /**
     * Inserts a row in to the database (using WPDB).
     *
     * @link https://developer.wordpress.org/reference/classes/wpdb/#insert-row
     *
     * @param string $table table name
     * @param mixed[] $data data to modify as an array with field name as key
     * @param string[]|string|null $format optional declaration of format for $data
     * @return int the ID of the inserted row
     * @throws WordPressDatabaseException if the operation failed or another database error occurred
     */
    public static function insert(string $table, array $data, $format = null) : int
    {
        $wpdb = static::instance();

        $result = $wpdb->insert($table, $data, $format);

        if ($wpdb->last_error || false === $result || empty($wpdb->insert_id)) {
            throw new WordPressDatabaseException($wpdb->last_error ?? 'Could not insert row.');
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Replaces data in the database (using WPDB).
     *
     * @link https://developer.wordpress.org/reference/classes/wpdb/#replace-row
     *
     * @param string $table table name
     * @param mixed[] $data data to modify as an array with field name as key
     * @param string[]|string|null $format optional declaration of format for $data
     * @return int a count to indicate the number of rows affected
     * @throws WordPressDatabaseException if the operation failed or another database error occurred
     */
    public static function replace(string $table, array $data, $format = null) : int
    {
        $wpdb = static::instance();

        $result = $wpdb->replace($table, $data, $format);

        if ($wpdb->last_error || ! is_numeric($result)) {
            throw new WordPressDatabaseException($wpdb->last_error ?? 'Could not replace row(s).');
        }

        return (int) $result;
    }

    /**
     * Updates data in the database (using WPDB).
     *
     * @link https://developer.wordpress.org/reference/classes/wpdb/#update-rows
     *
     * @param string $table table name
     * @param mixed[] $data data to modify as an array with field name as key
     * @param mixed[] $where conditions to check as an array with field name as key
     * @param string[]|string|null $format optional declaration of format for $data
     * @param string[]|string|null $formatWhere optional declaration of format for $where
     * @return int the number of rows updated
     * @throws WordPressDatabaseException if the operation failed or another database error occurred
     */
    public static function update(string $table, array $data, array $where, $format = null, $formatWhere = null) : int
    {
        $wpdb = static::instance();

        $result = $wpdb->update($table, $data, $where, $format, $formatWhere);

        if ($wpdb->last_error || ! is_numeric($result)) {
            throw new WordPressDatabaseException($wpdb->last_error ?? 'Could not update row(s).');
        }

        return (int) $result;
    }

    /**
     * Deletes data from the database (using WPDB).
     *
     * @link https://developer.wordpress.org/reference/classes/wpdb/#delete-rows
     *
     * @param string $table table name
     * @param array $where conditions to check as an array with field name as key
     * @param string[]|string|null $format optional declaration of format for $where
     * @return int the number of rows updated
     * @throws WordPressDatabaseException if the operation failed or another database error occurred
     */
    public static function delete(string $table, array $where, $format = null) : int
    {
        $wpdb = static::instance();

        $result = $wpdb->delete($table, $where, $format);

        if ($wpdb->last_error || ! is_numeric($result)) {
            throw new WordPressDatabaseException($wpdb->last_error ?? 'Could not delete row(s).');
        }

        return (int) $result;
    }

    /**
     * Gets the WordPress table prefix.
     *
     * @return string normally 'wp_'
     */
    public static function getTablePrefix() : string
    {
        $wpdb = static::instance();

        return is_string($wpdb->prefix) ? $wpdb->prefix : '';
    }

    /**
     * Gets data for a row in the WordPress database.
     *
     * @link https://developer.wordpress.org/reference/classes/wpdb/#select-a-row
     *
     * @param string $query SQL query string
     * @param array $args optional variables for placeholders in the SQL string
     * @return array row results or empty array
     */
    public static function getRow(string $query, array $args = []) : array
    {
        $wpdb = static::instance();

        /** @phpstan-ignore-next-line Parameter #1 $query of method wpdb::prepare() expects literal-string */
        $results = $wpdb->get_row($wpdb->prepare($query, ...$args), ARRAY_A);

        return ArrayHelper::wrap($results);
    }

    /**
     * Gets a result set from the WordPress database.
     *
     * @link https://developer.wordpress.org/reference/classes/wpdb/get_results/
     *
     * @param string $query SQL query string
     * @param array $args optional variables for placeholders in the SQL string
     * @return array dataset or empty array
     */
    public static function getResults(string $query, array $args = []) : array
    {
        $wpdb = static::instance();

        /** @phpstan-ignore-next-line Parameter #1 $query of method wpdb::prepare() expects literal-string */
        $results = $wpdb->get_results($wpdb->prepare($query, ...$args), ARRAY_A);

        return ArrayHelper::wrap($results);
    }
}
