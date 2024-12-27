<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\ReadProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

/**
 * Contract for providers that can read products.
 */
interface CanReadProductsContract
{
    /**
     * Reads a product from a corresponding input.
     *
     * @param ReadProductInput $input
     * @return ProductBase
     * @throws BaseException|CommerceExceptionContract|Exception
     */
    public function read(ReadProductInput $input) : ProductBase;
}
