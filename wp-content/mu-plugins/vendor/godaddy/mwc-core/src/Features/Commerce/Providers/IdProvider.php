<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\CanGenerateIdContract;

class IdProvider implements CanGenerateIdContract
{
    /**
     * {@inheritDoc}
     */
    public function generateId() : string
    {
        return StringHelper::generateUuid4();
    }
}
