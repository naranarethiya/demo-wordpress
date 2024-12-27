<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Request to patch a remote Poynt Business.
 */
class PatchBusinessRequest extends AbstractBusinessRequest
{
    use CanGetNewInstanceTrait;

    /**
     * PatchBusinessRequest constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setMethod('PATCH');

        parent::__construct();
    }
}
