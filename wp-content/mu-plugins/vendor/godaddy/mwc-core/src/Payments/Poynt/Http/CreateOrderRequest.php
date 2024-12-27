<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Request to create a remote Poynt Order.
 */
class CreateOrderRequest extends AbstractResourceRequest
{
    use CanGetNewInstanceTrait;

    /** @var string */
    const RESOURCE_PLURAL = 'orders';

    /**
     * CreateOrderRequest constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setMethod('POST');
        parent::__construct(static::RESOURCE_PLURAL);
    }
}
