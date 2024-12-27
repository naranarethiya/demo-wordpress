<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Request to get a remote Poynt Business.
 */
class GetBusinessRequest extends AbstractBusinessRequest
{
    use CanGetNewInstanceTrait;

    /**
     * GetBusinessRequest constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setMethod('GET');

        parent::__construct();
    }
}
