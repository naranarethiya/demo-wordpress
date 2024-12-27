<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs;

/**
 * Input data object for reading a category.
 */
class ReadCategoryInput extends AbstractCategoryInput
{
    /** @var string this is the remote category UUID */
    public string $categoryId;

    /**
     * Constructor.
     *
     * @param array{
     *     categoryId: string,
     *     storeId: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
