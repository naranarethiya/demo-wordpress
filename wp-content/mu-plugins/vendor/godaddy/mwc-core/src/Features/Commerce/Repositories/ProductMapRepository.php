<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\SkippedProductsRepository;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * Product map repository.
 */
class ProductMapRepository extends AbstractResourceMapRepository
{
    /** @var string type of resources managed by this repository */
    protected string $resourceType = CommerceResourceTypes::Product;

    /**
     * Gets the local IDs of products that do not yet exist in the mapping table.
     *
     * @param int $limit
     * @return int[]
     */
    public function getUnmappedLocalIds(int $limit = 50) : array
    {
        $results = DatabaseRepository::getResults(
            $this->getUnmappedLocalIdsSqlString(),
            [
                CatalogIntegration::PRODUCT_POST_TYPE,
                $limit,
            ],
        );

        return array_map('intval', array_column($results, 'ID'));
    }

    /**
     * Gets the SQL for the unmapped local IDs query.
     *
     * @return string
     */
    protected function getUnmappedLocalIdsSqlString() : string
    {
        $db = DatabaseRepository::instance();
        $resourceTypeId = $this->getResourceTypeId();

        $mappedLocalIdsSql = TypeHelper::string($db->prepare(
            /* @phpstan-ignore-next-line the only reason it's not a literal string is because we use constants to reference table/column names */
            $this->getMappedLocalIdsForResourceTypeQuery(),
            $resourceTypeId
        ), '');

        $skippedResourcesIdsSql = TypeHelper::string($db->prepare(
            /* @phpstan-ignore-next-line the only reason it's not a literal string is because we use constants to reference table/column names */
            SkippedProductsRepository::getSkippedResourcesIdsQuery(),
            $resourceTypeId
        ), '');

        // Example:
        // SELECT wp_posts.ID
        // FROM wp_posts
        // WHERE wp_posts.post_type = 'product'
        //     AND wp_posts.ID NOT IN(SELECT local_id FROM godaddy_mwc_commerce_map_ids WHERE resource_type_id = 11)
        //     AND wp_posts.ID NOT IN(SELECT local_id FROM godaddy_mwc_commerce_skipped_resources WHERE resource_type_id = 11)
        // LIMIT 0, 50
        return "
            SELECT {$db->posts}.ID
            FROM {$db->posts}
            WHERE {$db->posts}.post_type = %s
                AND {$db->posts}.ID NOT IN({$mappedLocalIdsSql})
                AND {$db->posts}.ID NOT IN({$skippedResourcesIdsSql})
            LIMIT %d
        ";
    }
}
