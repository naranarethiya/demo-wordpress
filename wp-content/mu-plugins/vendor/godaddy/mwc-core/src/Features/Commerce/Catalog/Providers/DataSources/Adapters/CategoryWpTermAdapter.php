<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\WordPress\WpTerm;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;

/**
 * Adapter to convert a Commerce {@see Category} into a {@see WpTerm} DTO.
 */
class CategoryWpTermAdapter implements DataSourceAdapterContract
{
    /** @var CategoryMapRepository */
    protected CategoryMapRepository $categoryMapRepository;

    /**
     * Constructor.
     *
     * @param CategoryMapRepository $categoryMapRepository
     */
    public function __construct(CategoryMapRepository $categoryMapRepository)
    {
        $this->categoryMapRepository = $categoryMapRepository;
    }

    /**
     * Converts a Commerce {@see Category} into a {@see WpTerm} DTO.
     *
     * @param Category|null $category
     * @return WpTerm
     * @throws AdapterException
     */
    public function convertToSource(?Category $category = null) : WpTerm
    {
        if (! $category) {
            throw new AdapterException('Missing required Category instance.');
        }

        return WpTerm::getNewInstance([
            'name'        => $category->name,
            'description' => $category->description,
            'parent'      => $this->convertParentIdToSource($category->parentId),
        ]);
    }

    /**
     * Converts a parent Category ID to a local source ID.
     *
     * @param string|null $parentId
     * @return int
     */
    protected function convertParentIdToSource(?string $parentId) : int
    {
        if (empty($parentId)) {
            return 0;
        }

        return TypeHelper::int($this->categoryMapRepository->getLocalId($parentId), 0);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource()
    {
        // no-op for now
    }
}
