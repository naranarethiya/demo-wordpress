<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use WC_Meta_Data;
use WC_Product;

/**
 * Synchronizes product post meta between the database and the cache.
 *
 * WordPress is often unable to store metadata in the postmeta table because our
 * integration with metadata cache makes it think that the data is already stored.
 *
 * The logic in this class updates the entries in the postmeta table to match the
 * information available in the metadata cache for a given product.
 */
class ProductPostMetaSynchronizer
{
    protected ProductPostMetaAggregator $productPostMetaAggregator;

    public function __construct(ProductPostMetaAggregator $productPostMetaAggregator)
    {
        $this->productPostMetaAggregator = $productPostMetaAggregator;
    }

    /**
     * Synchronizes product post meta for the given product between the database and the cache.
     */
    public function syncProductMeta(WC_Product $product, ProductBase $remoteProduct) : void
    {
        $persisted = $this->getMetaFromDatabase($product->get_id());
        $cached = $this->getMetaFromCache($product->get_id());
        $desired = $this->getMetaForProduct($cached, $remoteProduct);

        $entriesToIgnore = $this->getMetaKeysThatWillBeInsertedByWooCommerce($product);

        $entries = $this->prepareMetaForDatabase($persisted, $cached, $desired, $entriesToIgnore);

        $this->deleteMetaFromDatabase($product->get_id(), $entries['delete']);
        $this->updateMetaInDatabase($product->get_id(), $entries['update']);
        $this->insertMetaInDatabase($product->get_id(), $entries['insert']);
    }

    /**
     * Retrieves the metadata for a given post from the database.
     *
     * @return array<string, array<?string>>
     */
    protected function getMetaFromDatabase(int $postId) : array
    {
        $results = DatabaseRepository::getResults(
            "SELECT meta_key, meta_value FROM {$this->getMetaTableNameForSql()} WHERE post_id = %d",
            [$postId]
        );

        $meta = [];

        foreach ($results as $row) {
            $key = TypeHelper::string($row['meta_key'], '');

            if (! isset($meta[$key])) {
                $meta[$key] = [];
            }

            $meta[$key][] = TypeHelper::stringOrNull($row['meta_value']);
        }

        return $meta;
    }

    /**
     * Gets the name of the postmeta table.
     */
    protected function getMetaTableName() : string
    {
        $tableName = function_exists('_get_meta_table') ? _get_meta_table('post') : DatabaseRepository::instance()->postmeta;

        return TypeHelper::string($tableName, '');
    }

    /**
     * Gets the name of the postmeta table escaped to be used in a SQL query.
     */
    protected function getMetaTableNameForSql() : string
    {
        return TypeHelper::string(esc_sql($this->getMetaTableName()), '');
    }

    /**
     * Gets the metadata for a given product combining cached metadata with remote product information.
     *
     * @param array<string, array<?string>> $cached
     * @return array<string, array<?string>>
     */
    protected function getMetaForProduct(array $cached, ProductBase $remoteProduct) : array
    {
        return $this->productPostMetaAggregator->aggregate($cached, $remoteProduct);
    }

    /**
     * Retrieves the metadata for a given post from the cache.
     *
     * @return array<string, array<?string>>
     */
    protected function getMetaFromCache(int $postId) : array
    {
        $cache = TypeHelper::array(wp_cache_get($postId, 'post_meta'), []);
        $meta = [];

        foreach ($cache as $key => $values) {
            if (is_string($key) && is_array($values)) {
                $meta[$key] = array_values(array_filter($values, fn ($value) => is_string($value) || is_null($value)));
            }
        }

        return $meta;
    }

    /**
     * Retrieves a list of meta keys that WooCommerce sees as new metadata.
     *
     * WooCommerce will insert these entries into the database when the product
     * is saved, regardless of what is stored in the postmeta table at that time.
     * In order to avoid inserting duplicate entries, we need to ignore these keys.
     *
     * @return array<string, true>
     */
    protected function getMetaKeysThatWillBeInsertedByWooCommerce(WC_Product $product) : array
    {
        $keys = [];

        foreach (TypeHelper::arrayOf($product->get_meta_data(), WC_Meta_Data::class) as $meta) {
            if (empty($meta->id) && isset($meta->key) && is_string($meta->key)) {
                $keys[$meta->key] = true;
            }
        }

        return $keys;
    }

    /**
     * Compares the metadata from the database and the cache to determine
     * what entries to delete, update, or store.
     *
     * @param array<string, array<?string>> $persisted
     * @param array<string, array<?string>> $cached
     * @param array<string, array<?string>> $desired a combination of cached and remote metadata
     * @param array<string, true> $entriesToIgnore
     * @return array{delete: array<string, array<?string>>, update: array<string, ?string>, insert: array<string, array<?string>>}
     */
    protected function prepareMetaForDatabase(array $persisted, array $cached, array $desired, array $entriesToIgnore) : array
    {
        $entriesToDelete = [];
        $entriesToUpdate = [];
        $entriesToInsert = [];

        // If $cached is empty, the post meta cache was likely deleted recently and hasn't been rebuilt. We can't delete
        // missing entries because we can't determine whether they are needed or not.
        //
        // If $cached is not empty, we delete any entries that are not listed in the desired metadata.
        if ($cached) {
            $entriesToDelete = array_diff_key($persisted, $desired);
        }

        foreach ($desired as $key => $values) {
            if (isset($entriesToIgnore[$key])) {
                continue;
            }

            if (! isset($persisted[$key])) {
                $entriesToInsert[$key] = $values;
                continue;
            }

            if ($persisted[$key] === $values) {
                continue;
            }

            if (count($persisted[$key]) === 1 && count($values) === 1) {
                $entriesToUpdate[$key] = reset($values);
            } else {
                $entriesToDelete[$key] = $persisted[$key];
                $entriesToInsert[$key] = $values;
            }
        }

        return [
            'delete' => $entriesToDelete,
            'update' => $entriesToUpdate,
            'insert' => $entriesToInsert,
        ];
    }

    /**
     * Deletes the specified meta entries from the postmeta table.
     *
     * @param array<string, array<?string>> $entries
     */
    protected function deleteMetaFromDatabase(int $postId, array $entries) : void
    {
        $wpdb = DatabaseRepository::instance();

        foreach ($entries as $key => $values) {
            foreach ($values as $value) {
                $conditions[] = TypeHelper::string($wpdb->prepare('(meta_key = %s AND meta_value = %s)', $key, $value), '');
            }
        }

        if (empty($conditions)) {
            return;
        }

        /** @var literal-string $sql force PHPStan to accept this string as a literal string */
        $sql = "DELETE FROM {$this->getMetaTableNameForSql()} WHERE post_id = %d AND (".implode(' OR ', $conditions).')';

        $wpdb->query(TypeHelper::string($wpdb->prepare($sql, $postId), ''));
    }

    /**
     * Updates the specified meta entries in the postmeta table.
     *
     * @param array<string, ?string> $entries
     */
    protected function updateMetaInDatabase(int $postId, array $entries) : void
    {
        $wpdb = DatabaseRepository::instance();

        foreach ($entries as $key => $value) {
            $wpdb->update($this->getMetaTableName(), ['meta_value' => $value], ['post_id' => $postId, 'meta_key' => $key]);
        }
    }

    /**
     * Inserts the specified meta entries in the postmeta table.
     *
     * @param array<string, array<?string>> $entries
     */
    protected function insertMetaInDatabase(int $postId, array $entries) : void
    {
        $wpdb = DatabaseRepository::instance();

        foreach ($entries as $key => $values) {
            foreach ($values as $value) {
                $assignment = $wpdb->prepare('(%d, %s, %s)', $postId, $key, $value);

                if (! is_string($assignment)) {
                    continue;
                }

                $assignments[] = $assignment;
            }
        }

        if (! empty($assignments)) {
            $wpdb->query("INSERT INTO {$this->getMetaTableNameForSql()} (post_id, meta_key, meta_value) VALUES ".implode(', ', $assignments));
        }
    }
}
