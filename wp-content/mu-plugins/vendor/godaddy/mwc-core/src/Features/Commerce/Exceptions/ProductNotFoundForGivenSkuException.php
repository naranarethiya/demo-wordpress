<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

class ProductNotFoundForGivenSkuException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_PRODUCT_NOT_FOUND_FOR_GIVEN_SKU';
}
