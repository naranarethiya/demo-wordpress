<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomy;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertDateTimeFromTimestampTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanParseNullableStringPropertyTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCategoryRemoteIdForParentException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;

/**
 * Adapter to convert data into {@see Category} objects.
 */
class CategoryAdapter implements DataSourceAdapterContract
{
    use CanParseNullableStringPropertyTrait;
    use CanConvertDateTimeFromTimestampTrait;

    /** @var CategoriesMappingServiceContract categories mapping service */
    protected CategoriesMappingServiceContract $categoriesMappingService;

    /** @var CategoryMapRepository category map repository */
    protected CategoryMapRepository $categoryMapRepository;

    /**
     * Constructor.
     *
     * @param CategoriesMappingServiceContract $categoriesMappingService
     */
    public function __construct(CategoriesMappingServiceContract $categoriesMappingService, CategoryMapRepository $categoryMapRepository)
    {
        $this->categoriesMappingService = $categoriesMappingService;
        $this->categoryMapRepository = $categoryMapRepository;
    }

    /**
     * Converts a {@see Term} model into a {@see Category} object.
     *
     * @param Term|null $term
     * @return Category
     * @throws AdapterException|MissingCategoryRemoteIdForParentException
     */
    public function convertToSource(Term $term = null) : Category
    {
        if (! $term instanceof Term) {
            throw new AdapterException('Missing required Term object.');
        }

        return new Category([
            'altId'       => $term->getName(),
            'description' => $term->getDescription() ?: null,
            'name'        => $term->getLabel(),
            'parentId'    => $term->getParentId() ? $this->convertParentIdToSource($term->getParentId()) : null,
        ]);
    }

    /**
     * Converts a local WooCommerce parent category ID into a Commerce UUID equivalent.
     *
     * @param int $localParentId
     * @return string|null
     * @throws MissingCategoryRemoteIdForParentException
     */
    protected function convertParentIdToSource(int $localParentId) : ?string
    {
        $remoteParentId = $this->categoriesMappingService->getRemoteId($this->getLocalTermModel($localParentId));

        if (! $remoteParentId) {
            // throwing an exception here prevents us from incorrectly identifying the category as having no parent in Commerce
            throw new MissingCategoryRemoteIdForParentException("Failed to retrieve remote ID for parent category {$localParentId}.");
        }

        return $remoteParentId;
    }

    /**
     * Gets a local WooCommerce {@see Term} model from the provided local ID.
     *
     * @param int $localId
     * @return Term
     */
    protected function getLocalTermModel(int $localId) : Term
    {
        $taxonomy = Taxonomy::getNewInstance()->setName(CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY);

        return Term::getNewInstance($taxonomy)->setId($localId);
    }

    /**
     * Converts a {@see Category} data object into a {see @Term} model.
     *
     * @param ?Category $category
     * @return Term
     * @throws AdapterException|MissingCategoryRemoteIdForParentException
     */
    public function convertFromSource(?Category $category = null) : Term
    {
        if (! $category) {
            throw new AdapterException('Cannot convert a null category to a term.');
        }

        $taxonomy = Taxonomy::getNewInstance()->setName(CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY);

        return Term::getNewInstance($taxonomy)
            ->setName($category->altId ?: '')
            ->setLabel($category->name ?: '')
            ->setDescription($category->description ?: '')
            ->setParentId($category->parentId ? $this->convertParentIdFromSource($category->parentId) : null);
    }

    /**
     * Converts an array of category data into a {@see Category} object.
     *
     * @param array<string, mixed> $data
     * @return Category
     * @throws MissingCategoryRemoteIdException
     */
    public function convertToSourceFromArray(array $data) : Category
    {
        /** @var array<array<string, string>> $externalIdsData */
        $externalIdsData = TypeHelper::array(ArrayHelper::get($data, 'externalIds'), []);

        return new Category([
            'altId' => $this->parseNullableStringProperty($data, 'altId'),
            // @TODO assets will be converted when we start reading assets from the service {agibson 2023-09-06}
            'categoryId'  => $this->getValidCategoryId($data, 'categoryId'),
            'createdAt'   => $this->convertDateTimeFromTimestamp($data, 'createdAt'),
            'depth'       => TypeHelper::int(ArrayHelper::get($data, 'depth'), 0),
            'description' => $this->parseNullableStringProperty($data, 'description'),
            'externalIds' => ExternalIdsAdapter::getNewInstance()->convertToSourceFromArray($externalIdsData),
            'name'        => TypeHelper::string(ArrayHelper::get($data, 'name'), ''),
            'parentId'    => $this->parseNullableStringProperty($data, 'parentId'),
            'sequence'    => TypeHelper::int(ArrayHelper::get($data, 'sequence'), 1),
            'updatedAt'   => $this->convertDateTimeFromTimestamp($data, 'updatedAt'),
        ]);
    }

    /**
     * Get the valid category ID from the response data.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @return string|null
     * @throws MissingCategoryRemoteIdException
     */
    protected function getValidCategoryId(array $data, string $key) : ?string
    {
        if ($value = TypeHelper::string(ArrayHelper::get($data, $key), '')) {
            return $value;
        }

        throw new MissingCategoryRemoteIdException('Category ID is missing from response.');
    }

    /**
     * Converts a Commerce UUID parent category ID into a local WooCommerce equivalent.
     *
     * @param string|null $remoteParentId
     * @return int|null
     * @throws MissingCategoryRemoteIdForParentException
     */
    protected function convertParentIdFromSource(?string $remoteParentId) : ?int
    {
        if (! $remoteParentId) {
            return null;
        }

        $localParentId = $this->categoryMapRepository->getLocalId($remoteParentId);

        if (! $localParentId) {
            // throwing an exception here prevents us from incorrectly identifying the category as having no parent in WooCommerce
            throw new MissingCategoryRemoteIdForParentException("Failed to retrieve local ID for parent category {$remoteParentId}.");
        }

        return $localParentId;
    }
}
