<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Abstract request to interact with a remote Poynt Product.
 */
abstract class AbstractProductRequest extends AbstractResourceRequest
{
    /** @var string */
    const RESOURCE_PLURAL = 'products';

    /**
     * ProductRequest constructor.
     *
     * @param string|null $productId
     * @throws Exception
     */
    public function __construct(string $productId = null)
    {
        parent::__construct(static::RESOURCE_PLURAL, $productId);
    }
}
