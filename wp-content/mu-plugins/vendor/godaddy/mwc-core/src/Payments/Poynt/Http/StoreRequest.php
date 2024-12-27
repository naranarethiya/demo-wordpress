<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Store for businesses request.
 */
class StoreRequest extends AbstractBusinessRequest
{
    use CanGetNewInstanceTrait;

    /** @var string request route */
    protected $route = 'stores';

    /**
     * StoreRequest constructor.
     *
     * @throws Exception
     */
    public function __construct(string $storeId)
    {
        $this->route .= "/{$storeId}";

        parent::__construct();
    }
}
