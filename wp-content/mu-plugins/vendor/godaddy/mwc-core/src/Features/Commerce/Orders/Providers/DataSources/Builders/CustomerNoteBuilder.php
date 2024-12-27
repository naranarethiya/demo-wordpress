<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Builders;

use GoDaddy\WordPress\MWC\Common\Builders\Contracts\BuilderContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasOrderIdContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasOrderIdTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\CustomerNote;

/**
 * Builds {@see CustomerNote} instances from content.
 */
class CustomerNoteBuilder implements BuilderContract, HasOrderIdContract
{
    use CanGetNewInstanceTrait;
    use HasOrderIdTrait;

    /**
     * {@inheritDoc}
     */
    public function build(string $content = '') : CustomerNote
    {
        return (new CustomerNote())->setOrderId($this->getOrderId())->setContent($content);
    }
}
