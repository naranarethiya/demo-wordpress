<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\CategoryRequestInputs\ListCategoriesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListCategoriesResponseContract;

interface CanListCategoriesContract
{
    /**
     * @param ListCategoriesInput $input
     * @return ListCategoriesResponseContract
     */
    public function list(ListCategoriesInput $input) : ListCategoriesResponseContract;
}
